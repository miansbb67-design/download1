#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DownloadPro - Media Downloader Engine
Downloads streams and video links using yt-dlp with FFmpeg merging.
Reports real-time progress to stdout and progress status file.
"""

import sys
import os
import io
import json
import time
import re

# Enforce UTF-8 standard I/O
if sys.platform.startswith('win'):
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

FFMPEG_PATH = r"C:\Users\FineTechShop\python311\Scripts\ffmpeg.exe"
if not os.path.exists(FFMPEG_PATH):
    FFMPEG_PATH = "ffmpeg"

def download_media(url: str, output_dir: str, format_id: str = "best", is_audio_only: bool = False, custom_title: str = "", progress_file: str = "") -> dict:
    import yt_dlp

    os.makedirs(output_dir, exist_ok=True)
    
    last_update = [0]

    def ytdl_hook(d):
        if d['status'] == 'downloading':
            total = d.get('total_bytes') or d.get('total_bytes_estimate') or 0
            downloaded = d.get('downloaded_bytes') or 0
            speed = d.get('speed') or 0
            eta = d.get('eta') or 0
            percent = (downloaded / total * 100) if total > 0 else 0

            # Only write progress periodically to prevent excessive I/O
            now = time.time()
            if now - last_update[0] >= 0.5:
                last_update[0] = now
                prog_data = {
                    "status": "downloading",
                    "percent": round(percent, 1),
                    "downloaded_bytes": downloaded,
                    "total_bytes": total,
                    "speed_bytes_sec": round(speed, 1),
                    "eta_seconds": eta
                }
                if progress_file:
                    try:
                        with open(progress_file, 'w', encoding='utf-8') as f:
                            json.dump(prog_data, f)
                    except Exception:
                        pass
                # Also print single-line JSON for realtime pipe reading
                sys.stdout.write(json.dumps(prog_data, ensure_ascii=True) + "\n")
                sys.stdout.flush()

        elif d['status'] == 'finished':
            prog_data = {
                "status": "processing",
                "percent": 99.0,
                "message": "Processing and converting media..."
            }
            if progress_file:
                try:
                    with open(progress_file, 'w', encoding='utf-8') as f:
                        json.dump(prog_data, f)
                except Exception:
                    pass

    ydl_opts = {
        'ffmpeg_location': FFMPEG_PATH,
        'progress_hooks': [ytdl_hook],
        'outtmpl': os.path.join(output_dir, '%(title)s.%(ext)s'),
        'noplaylist': True,
        'quiet': True,
        'no_warnings': True,
        'socket_timeout': 30
    }

    if is_audio_only:
        ydl_opts['format'] = 'bestaudio/best'
        ydl_opts['postprocessors'] = [{
            'key': 'FFmpegExtractAudio',
            'preferredcodec': 'mp3',
            'preferredquality': '320',
        }]
    else:
        if format_id and format_id not in ['best', 'direct']:
            ydl_opts['format'] = f"{format_id}+bestaudio/best"
        else:
            ydl_opts['format'] = 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best'
        ydl_opts['merge_output_format'] = 'mp4'

    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=True)
        final_filename = ydl.prepare_filename(info)
        
        # If converted to mp3
        if is_audio_only:
            final_filename = os.path.splitext(final_filename)[0] + ".mp3"
        elif ydl_opts.get('merge_output_format') == 'mp4':
            final_filename = os.path.splitext(final_filename)[0] + ".mp4"

        final_size = os.path.getsize(final_filename) if os.path.exists(final_filename) else 0

        result = {
            "success": True,
            "status": "completed",
            "file_path": final_filename,
            "filename": os.path.basename(final_filename),
            "size_bytes": final_size,
            "title": info.get('title', os.path.basename(final_filename)),
            "duration": info.get('duration', 0)
        }

        if progress_file:
            try:
                with open(progress_file, 'w', encoding='utf-8') as f:
                    json.dump({"status": "completed", "percent": 100, "file_path": final_filename}, f)
            except Exception:
                pass

        return result

if __name__ == "__main__":
    if len(sys.argv) < 3:
        output = {"success": False, "error": "Usage: downloader.py <url> <output_dir> [format_id] [is_audio: 0|1] [progress_file]"}
    else:
        url = sys.argv[1]
        out_dir = sys.argv[2]
        fmt_id = sys.argv[3] if len(sys.argv) > 3 else "best"
        is_aud = sys.argv[4] == "1" if len(sys.argv) > 4 else False
        p_file = sys.argv[5] if len(sys.argv) > 5 else ""

        try:
            output = download_media(url, out_dir, fmt_id, is_aud, "", p_file)
        except Exception as e:
            output = {"success": False, "error": str(e)}

    sys.stdout.write(json.dumps(output, ensure_ascii=True, indent=2))
    sys.stdout.write("\n")
    sys.stdout.flush()
