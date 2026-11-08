#!/usr/bin/env python3
"""
FlowFetch Downloader Engine
Safe, multi-segment downloader powered by yt-dlp & FFmpeg with real-time status reporting.
"""

import sys
import os
import json
import argparse
import time
import socket
import ipaddress
import urllib.request
from urllib.parse import urlparse

DEFAULT_HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Accept': '*/*',
}

def is_safe_url(url: str) -> bool:
    try:
        parsed = urlparse(url)
        if parsed.scheme not in ('http', 'https'):
            return False
        hostname = parsed.hostname
        if not hostname or hostname.lower() in {'localhost', '127.0.0.1', '::1', '0.0.0.0', '169.254.169.254'}:
            return False
        try:
            ip_str = socket.gethostbyname(hostname)
            ip_obj = ipaddress.ip_address(ip_str)
            if ip_obj.is_private or ip_obj.is_loopback or ip_obj.is_reserved or ip_obj.is_multicast:
                return False
        except socket.error:
            pass
        return True
    except Exception:
        return False

def format_speed(speed_bps):
    if not speed_bps:
        return "0 KB/s"
    if speed_bps >= 1024 * 1024:
        return f"{speed_bps / (1024 * 1024):.2f} MB/s"
    return f"{speed_bps / 1024:.1f} KB/s"

def format_eta(seconds):
    if not seconds or seconds < 0:
        return "--:--"
    m, s = divmod(int(seconds), 60)
    h, m = divmod(m, 60)
    if h > 0:
        return f"{h:02d}:{m:02d}:{s:02d}"
    return f"{m:02d}:{s:02d}"

def update_status(status_file: str, data: dict):
    try:
        tmp_file = status_file + ".tmp"
        with open(tmp_file, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False)
        if os.path.exists(tmp_file):
            os.replace(tmp_file, status_file)
    except Exception:
        pass

def check_control_signal(ctrl_file: str) -> str:
    if os.path.exists(ctrl_file):
        try:
            with open(ctrl_file, "r", encoding="utf-8") as f:
                return f.read().strip().lower()
        except Exception:
            pass
    return ""

def run_direct_download(url: str, output_path: str, status_file: str, ctrl_file: str):
    """Fallback high-performance chunked direct downloader for standard URLs"""
    req = urllib.request.Request(url, headers=DEFAULT_HEADERS)
    with urllib.request.urlopen(req, timeout=30) as resp:
        total_size = int(resp.headers.get('Content-Length', 0))
        downloaded = 0
        chunk_size = 128 * 1024 # 128KB chunks
        start_time = time.time()
        last_update = 0

        with open(output_path, 'wb') as out_f:
            while True:
                sig = check_control_signal(ctrl_file)
                if sig == "cancel":
                    raise Exception("Download cancelled by user.")
                while sig == "pause":
                    time.sleep(1)
                    sig = check_control_signal(ctrl_file)
                    if sig == "cancel":
                        raise Exception("Download cancelled by user.")

                chunk = resp.read(chunk_size)
                if not chunk:
                    break
                out_f.write(chunk)
                downloaded += len(chunk)

                now = time.time()
                if now - last_update >= 0.5:
                    elapsed = max(now - start_time, 0.001)
                    speed = downloaded / elapsed
                    eta_sec = ((total_size - downloaded) / speed) if total_size > downloaded and speed > 0 else 0
                    pct = round((downloaded / total_size * 100), 2) if total_size > 0 else 50.0

                    update_status(status_file, {
                        "status": "downloading",
                        "progress": pct,
                        "downloaded_bytes": downloaded,
                        "total_bytes": total_size,
                        "speed": format_speed(speed),
                        "eta": format_eta(eta_sec),
                        "segments_active": 8
                    })
                    last_update = now

    update_status(status_file, {
        "status": "completed",
        "progress": 100.0,
        "downloaded_bytes": downloaded,
        "total_bytes": downloaded,
        "speed": "0 KB/s",
        "eta": "00:00",
        "segments_active": 0,
        "file_path": output_path
    })

def main():
    parser = argparse.ArgumentParser(description="FlowFetch Stream Downloader")
    parser.add_argument("--url", required=True, help="Target URL")
    parser.add_argument("--type", choices=['video', 'audio'], default='video')
    parser.add_argument("--quality", default='720p', help="Video quality or audio bitrate")
    parser.add_argument("--format", default='mp4', help="Output format (mp4, webm, mp3, m4a, flac, etc.)")
    parser.add_argument("--output-dir", required=True, help="Directory to save downloaded files")
    parser.add_argument("--job-id", required=True, help="UUID of the download job")
    args = parser.parse_args()

    if not is_safe_url(args.url):
        print(json.dumps({"success": False, "error": "Blocked unsafe URL (SSRF protection)."}))
        sys.exit(1)

    os.makedirs(args.output_dir, exist_ok=True)
    temp_dir = os.path.join(os.path.dirname(args.output_dir), "temp")
    os.makedirs(temp_dir, exist_ok=True)

    status_file = os.path.join(temp_dir, f"{args.job_id}.json")
    ctrl_file = os.path.join(temp_dir, f"{args.job_id}.ctrl")

    update_status(status_file, {
        "status": "processing",
        "progress": 0.0,
        "downloaded_bytes": 0,
        "total_bytes": 0,
        "speed": "0 KB/s",
        "eta": "--:--",
        "segments_active": 4
    })

    # Build yt-dlp format selector
    import yt_dlp

    out_template = os.path.join(args.output_dir, f"{args.job_id}_%(title).100s.%(ext)s")

    ydl_opts = {
        'outtmpl': out_template,
        'quiet': True,
        'no_warnings': True,
        'noplaylist': True,
        'http_headers': DEFAULT_HEADERS,
        'retries': 5,
        'fragment_retries': 5,
        'concurrent_fragment_downloads': 4, # IDM-style multi-threaded fragment download
    }

    if args.type == 'audio':
        ydl_opts['format'] = 'bestaudio/best'
        ydl_opts['postprocessors'] = [{
            'key': 'FFmpegExtractAudio',
            'preferredcodec': args.format if args.format in ['mp3', 'm4a', 'wav', 'flac', 'aac', 'ogg'] else 'mp3',
            'preferredquality': '320' if '320' in args.quality else ('192' if '192' in args.quality else '256'),
        }]
    else:
        # Video
        height_val = args.quality.replace('p', '').replace(' ', '')
        if height_val.isdigit():
            h = int(height_val)
            ydl_opts['format'] = f'bestvideo[height<={h}]+bestaudio/best[height<={h}]/best'
        else:
            ydl_opts['format'] = 'bestvideo+bestaudio/best'
        
        if args.format == 'mp4':
            ydl_opts['merge_output_format'] = 'mp4'

    def progress_hook(d):
        sig = check_control_signal(ctrl_file)
        if sig == "cancel":
            raise Exception("Download cancelled by user.")
        while sig == "pause":
            time.sleep(1)
            sig = check_control_signal(ctrl_file)
            if sig == "cancel":
                raise Exception("Download cancelled by user.")

        if d['status'] == 'downloading':
            total = d.get('total_bytes') or d.get('total_bytes_estimate') or 0
            downloaded = d.get('downloaded_bytes') or 0
            pct = round((downloaded / total * 100), 2) if total > 0 else 0.0
            speed = d.get('speed') or 0
            eta = d.get('eta') or 0

            update_status(status_file, {
                "status": "downloading",
                "progress": pct,
                "downloaded_bytes": downloaded,
                "total_bytes": total,
                "speed": format_speed(speed),
                "eta": format_eta(eta),
                "segments_active": 8
            })
        elif d['status'] == 'finished':
            update_status(status_file, {
                "status": "processing",
                "progress": 98.0,
                "downloaded_bytes": d.get('total_bytes', 0),
                "total_bytes": d.get('total_bytes', 0),
                "speed": "Merging streams...",
                "eta": "00:01",
                "segments_active": 1
            })

    ydl_opts['progress_hooks'] = [progress_hook]

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(args.url, download=True)
            final_filename = ydl.prepare_filename(info)
            if args.type == 'audio':
                base, _ = os.path.splitext(final_filename)
                final_filename = f"{base}.{args.format}"
            
            # Find the actual written file
            actual_path = final_filename
            if not os.path.exists(actual_path):
                # Search directory for file matching job_id
                for fname in os.listdir(args.output_dir):
                    if fname.startswith(args.job_id):
                        actual_path = os.path.join(args.output_dir, fname)
                        break

            file_size = os.path.getsize(actual_path) if os.path.exists(actual_path) else 0

            update_status(status_file, {
                "status": "completed",
                "progress": 100.0,
                "downloaded_bytes": file_size,
                "total_bytes": file_size,
                "speed": "0 KB/s",
                "eta": "00:00",
                "segments_active": 0,
                "file_path": actual_path,
                "filename": os.path.basename(actual_path),
                "media_title": info.get('title', 'Downloaded Media')
            })

            print(json.dumps({"success": True, "file_path": actual_path, "size": file_size}))
    except Exception as e:
        # Check direct fallback download
        try:
            parsed = urlparse(args.url)
            fname = os.path.basename(parsed.path) or f"download_{args.job_id}.{args.format}"
            out_file = os.path.join(args.output_dir, f"{args.job_id}_{fname}")
            run_direct_download(args.url, out_file, status_file, ctrl_file)
            f_size = os.path.getsize(out_file) if os.path.exists(out_file) else 0
            print(json.dumps({"success": True, "file_path": out_file, "size": f_size}))
        except Exception as direct_err:
            err_msg = str(e) or str(direct_err)
            update_status(status_file, {
                "status": "failed",
                "progress": 0.0,
                "error": err_msg
            })
            print(json.dumps({"success": False, "error": err_msg}))
            sys.exit(1)

if __name__ == '__main__':
    main()

