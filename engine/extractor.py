#!/usr/bin/env python3
"""
FlowFetch Media Metadata Extractor
Safe, SSRF-guarded media information extractor powered by yt-dlp & direct stream prober.
"""

import sys
import io
import os
import json
import argparse
import socket
import ipaddress
import urllib.request
from urllib.parse import urlparse

# Ensure Windows console or subprocess piping never crashes on non-ASCII characters
if hasattr(sys.stdout, 'buffer'):
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'buffer'):
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

DEFAULT_HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,video/*,audio/*,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.9',
}

def is_safe_url(url: str) -> tuple[bool, str]:
    """Validate URL to prevent SSRF against internal infrastructure."""
    try:
        parsed = urlparse(url)
        if parsed.scheme not in ('http', 'https'):
            return False, f"Unsupported protocol: {parsed.scheme}. Only HTTP/HTTPS allowed."
        
        hostname = parsed.hostname
        if not hostname:
            return False, "Invalid hostname in URL."
            
        # Block literal forbidden hosts
        blocked_literals = {'localhost', '127.0.0.1', '::1', '0.0.0.0', '169.254.169.254'}
        if hostname.lower() in blocked_literals:
            return False, "Access to localhost or internal metadata addresses is forbidden."
            
        # Resolve IP and verify not private / loopback / multicast
        try:
            ip_str = socket.gethostbyname(hostname)
            ip_obj = ipaddress.ip_address(ip_str)
            if ip_obj.is_private or ip_obj.is_loopback or ip_obj.is_reserved or ip_obj.is_multicast:
                return False, f"Access to private/local IP address ({ip_str}) is forbidden."
        except socket.error:
            pass
            
        return True, ""
    except Exception as e:
        return False, f"URL validation error: {str(e)}"

def format_duration(seconds: int) -> str:
    """Format seconds into HH:MM:SS or MM:SS."""
    if not seconds:
        return "00:00"
    m, s = divmod(int(seconds), 60)
    h, m = divmod(m, 60)
    if h > 0:
        return f"{h:02d}:{m:02d}:{s:02d}"
    return f"{m:02d}:{s:02d}"

def format_bytes(num_bytes: int) -> str:
    """Format bytes into human readable MB / GB."""
    if not num_bytes:
        return "Unknown"
    for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
        if abs(num_bytes) < 1024.0:
            return f"{num_bytes:3.1f} {unit}"
        num_bytes /= 1024.0
    return f"{num_bytes:.1f} PB"

def probe_direct_stream(url: str) -> dict | None:
    """Fallback prober for direct media files (.mp4, .mp3, etc.)"""
    try:
        req = urllib.request.Request(url, headers=DEFAULT_HEADERS, method='HEAD')
        with urllib.request.urlopen(req, timeout=8) as resp:
            content_type = resp.headers.get('Content-Type', '').lower()
            content_length = int(resp.headers.get('Content-Length', 0))
            
            parsed = urlparse(url)
            filename = os.path.basename(parsed.path) or "direct_media"
            ext = os.path.splitext(filename)[1].replace('.', '').lower() or 'mp4'

            is_video = 'video' in content_type or ext in ['mp4', 'webm', 'mkv', 'mov', 'avi']
            is_audio = 'audio' in content_type or ext in ['mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg']

            if not is_video and not is_audio:
                return None

            video_formats = []
            if is_video:
                video_formats.append({
                    "quality": "1080p",
                    "label": "1080p Full HD (Direct Stream)",
                    "badge": "Direct",
                    "format_id": "direct",
                    "ext": ext,
                    "fps": 30,
                    "filesize": content_length,
                    "filesize_human": format_bytes(content_length) if content_length else "Direct Stream",
                    "tbr": 0
                })

            audio_formats = [
                {"format": "mp3", "label": "MP3 (High Quality 320 kbps)", "bitrate": "320k", "ext": "mp3", "filesize": int(content_length * 0.2) if content_length else 0, "filesize_human": format_bytes(int(content_length * 0.2)) if content_length else "Auto"},
                {"format": "m4a", "label": "M4A / AAC (Apple Audio)", "bitrate": "256k", "ext": "m4a", "filesize": int(content_length * 0.15) if content_length else 0, "filesize_human": format_bytes(int(content_length * 0.15)) if content_length else "Auto"},
                {"format": "wav", "label": "WAV (Lossless PCM Audio)", "bitrate": "1411k", "ext": "wav", "filesize": int(content_length * 0.8) if content_length else 0, "filesize_human": format_bytes(int(content_length * 0.8)) if content_length else "Auto"}
            ]

            return {
                "success": True,
                "is_playlist": False,
                "data": {
                    "id": "direct_" + hex(abs(hash(url)))[2:10],
                    "title": filename.replace('_', ' ').replace('-', ' ').title(),
                    "uploader": parsed.netloc,
                    "thumbnail": "assets/img/direct-video.svg",
                    "duration": "Direct Stream",
                    "duration_seconds": 0,
                    "views": 0,
                    "upload_date": "",
                    "source_url": url,
                    "video_formats": video_formats,
                    "audio_formats": audio_formats,
                    "extractor": "Direct Media Link"
                }
            }
    except Exception:
        return None

def extract_media(url: str, playlist_limit: int = 50) -> dict:
    safe, msg = is_safe_url(url)
    if not safe:
        return {"success": False, "error": msg}

    import yt_dlp

    ydl_opts = {
        'quiet': True,
        'no_warnings': True,
        'skip_download': True,
        'extract_flat': 'in_playlist',
        'socket_timeout': 15,
        'noplaylist': False,
        'http_headers': DEFAULT_HEADERS,
    }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            if not info:
                # Try fallback direct probe
                direct_info = probe_direct_stream(url)
                if direct_info:
                    return direct_info
                return {"success": False, "error": "Unable to extract media information from this URL."}

            # Check if this is a playlist
            if 'entries' in info:
                entries = []
                raw_entries = list(info.get('entries', []))[:playlist_limit]
                for idx, entry in enumerate(raw_entries):
                    if not entry:
                        continue
                    entry_duration = entry.get('duration') or 0
                    entries.append({
                        "index": idx + 1,
                        "id": entry.get('id', str(idx + 1)),
                        "title": entry.get('title') or f"Track {idx + 1}",
                        "duration": format_duration(entry_duration),
                        "duration_seconds": entry_duration,
                        "thumbnail": entry.get('thumbnail') or (entry.get('thumbnails', [{}])[-1].get('url') if entry.get('thumbnails') else ''),
                        "url": entry.get('url') or entry.get('webpage_url') or f"https://www.youtube.com/watch?v={entry.get('id')}",
                        "uploader": entry.get('uploader') or info.get('uploader') or "Unknown"
                    })

                return {
                    "success": True,
                    "is_playlist": True,
                    "data": {
                        "id": info.get('id', ''),
                        "title": info.get('title') or "Untitled Playlist",
                        "uploader": info.get('uploader') or "Unknown",
                        "thumbnail": info.get('thumbnails', [{}])[-1].get('url') if info.get('thumbnails') else (entries[0]['thumbnail'] if entries else ''),
                        "total_items": len(entries),
                        "entries": entries,
                        "source_url": url
                    }
                }

            # Single Video or Audio item
            raw_formats = info.get('formats', [])
            duration_sec = info.get('duration') or 0
            
            # Map of resolution -> stream format info
            video_resolutions = {}
            target_resolutions = [
                (2160, '2160p 4K', '4K UHD'),
                (1440, '1440p 2K', '2K QHD'),
                (1080, '1080p FHD', 'Full HD'),
                (720, '720p HD', 'HD'),
                (480, '480p SD', 'SD'),
                (360, '360p', 'Mobile')
            ]

            # Collect best video stream per resolution
            for f in raw_formats:
                height = f.get('height')
                if not height:
                    continue
                vcodec = f.get('vcodec', 'none')
                if vcodec == 'none':
                    continue
                
                # Check target resolution match
                for target_h, label, badge in target_resolutions:
                    if height >= target_h - 20 and height <= target_h + 40:
                        filesize = f.get('filesize') or f.get('filesize_approx')
                        if not filesize and duration_sec and f.get('tbr'):
                            filesize = int(f.get('tbr') * 1000 / 8 * duration_sec)
                            
                        # Keep best bitrate format
                        if target_h not in video_resolutions or (f.get('tbr', 0) > video_resolutions[target_h].get('tbr', 0)):
                            video_resolutions[target_h] = {
                                "quality": f"{target_h}p",
                                "label": label,
                                "badge": badge,
                                "format_id": f.get('format_id'),
                                "ext": "mp4",
                                "fps": f.get('fps') or 30,
                                "filesize": filesize or 0,
                                "filesize_human": format_bytes(filesize) if filesize else "Available",
                                "tbr": f.get('tbr') or 0
                            }

            # Ensure we sort from highest to lowest resolution
            available_video_formats = []
            for target_h, label, badge in target_resolutions:
                if target_h in video_resolutions:
                    available_video_formats.append(video_resolutions[target_h])

            # If no resolutions mapped (e.g. direct mp4 link or unknown platform), fallback
            if not available_video_formats and info.get('ext') in ['mp4', 'webm', 'mkv', 'mov']:
                filesize = info.get('filesize') or info.get('filesize_approx') or 0
                available_video_formats.append({
                    "quality": f"{info.get('height', 720)}p",
                    "label": f"{info.get('height', 720)}p HD",
                    "badge": "Standard",
                    "format_id": info.get('format_id', 'best'),
                    "ext": info.get('ext', 'mp4'),
                    "fps": info.get('fps') or 30,
                    "filesize": filesize,
                    "filesize_human": format_bytes(filesize) if filesize else "Standard Size",
                    "tbr": 0
                })

            # Available Audio Formats
            audio_formats = [
                {"format": "mp3", "label": "MP3 (High Quality 320 kbps)", "bitrate": "320k", "ext": "mp3"},
                {"format": "mp3", "label": "MP3 (Standard 192 kbps)", "bitrate": "192k", "ext": "mp3"},
                {"format": "m4a", "label": "M4A / AAC (Apple Audio)", "bitrate": "256k", "ext": "m4a"},
                {"format": "wav", "label": "WAV (Uncompressed Studio Audio)", "bitrate": "1411k", "ext": "wav"},
                {"format": "flac", "label": "FLAC (Lossless Hi-Res Audio)", "bitrate": "lossless", "ext": "flac"},
                {"format": "ogg", "label": "OGG Vorbis (High Fidelity)", "bitrate": "192k", "ext": "ogg"},
            ]

            # Approximate audio size based on duration and bitrate
            for af in audio_formats:
                kbps = 192
                if '320' in af['bitrate']: kbps = 320
                elif '128' in af['bitrate']: kbps = 128
                elif '256' in af['bitrate']: kbps = 256
                elif '1411' in af['bitrate']: kbps = 1411
                if duration_sec:
                    approx_size = int((kbps * 1000 / 8) * duration_sec)
                    af['filesize_human'] = format_bytes(approx_size)
                    af['filesize'] = approx_size
                else:
                    af['filesize_human'] = "Auto"
                    af['filesize'] = 0

            return {
                "success": True,
                "is_playlist": False,
                "data": {
                    "id": info.get('id', ''),
                    "title": info.get('title') or "Untitled Video",
                    "uploader": info.get('uploader') or "Web Media Source",
                    "thumbnail": info.get('thumbnail') or (info.get('thumbnails', [{}])[-1].get('url') if info.get('thumbnails') else ''),
                    "duration": format_duration(duration_sec),
                    "duration_seconds": duration_sec,
                    "views": info.get('view_count', 0),
                    "upload_date": info.get('upload_date', ''),
                    "source_url": url,
                    "video_formats": available_video_formats,
                    "audio_formats": audio_formats,
                    "extractor": info.get('extractor_key', 'Generic')
                }
            }

    except Exception as e:
        direct_info = probe_direct_stream(url)
        if direct_info:
            return direct_info
        return {"success": False, "error": str(e)}

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="FlowFetch Safe Media Metadata Extractor")
    parser.add_argument("--url", required=True, help="Media URL to inspect")
    parser.add_argument("--playlist-limit", type=int, default=50, help="Max entries to return for playlists")
    args = parser.parse_args()

    result = extract_media(args.url, args.playlist_limit)
    print(json.dumps(result, ensure_ascii=True, indent=2))
