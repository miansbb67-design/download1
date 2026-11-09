#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DownloadPro - Advanced Media Metadata Extractor
Handles URL inspection, direct stream analysis, and yt-dlp metadata extraction.
Fully UTF-8 safe on Windows to prevent any charmap / cp1252 UnicodeEncodeError.
"""

import sys
import os
import io
import json
import re
import urllib.request
import urllib.parse
import ssl
from typing import Dict, Any, List

# Enforce UTF-8 on Windows standard I/O
if sys.platform.startswith('win'):
    try:
        if hasattr(sys.stdout, 'reconfigure'):
            sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        if hasattr(sys.stderr, 'reconfigure'):
            sys.stderr.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

def format_size(bytes_val: int) -> str:
    """Format bytes into human readable string (KB, MB, GB)."""
    if not bytes_val or bytes_val <= 0:
        return "Unknown size"
    units = ["B", "KB", "MB", "GB", "TB"]
    i = 0
    size = float(bytes_val)
    while size >= 1024.0 and i < len(units) - 1:
        size /= 1024.0
        i += 1
    return f"{size:.1f} {units[i]}"

def format_duration(seconds: float) -> str:
    """Format seconds into HH:MM:SS or MM:SS."""
    if not seconds or seconds <= 0:
        return "00:00"
    secs = int(seconds)
    hours = secs // 3600
    minutes = (secs % 3600) // 60
    rem_secs = secs % 60
    if hours > 0:
        return f"{hours:02d}:{minutes:02d}:{rem_secs:02d}"
    return f"{minutes:02d}:{rem_secs:02d}"

def detect_category(filename: str, content_type: str = "") -> str:
    """Categorize file into Videos, Music & Audio, Documents, Programs, Compressed, or Other."""
    filename_lower = filename.lower()
    ct = content_type.lower()
    
    video_exts = [".mp4", ".mkv", ".webm", ".avi", ".mov", ".flv", ".wmv", ".m4v", ".ts"]
    audio_exts = [".mp3", ".wav", ".m4a", ".aac", ".flac", ".ogg", ".wma", ".opus"]
    doc_exts = [".pdf", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".txt", ".csv"]
    prog_exts = [".exe", ".msi", ".dmg", ".pkg", ".apk", ".deb", ".rpm"]
    comp_exts = [".zip", ".rar", ".7z", ".tar", ".gz", ".bz2", ".iso", ".xz"]
    
    if any(filename_lower.endswith(ext) for ext in video_exts) or "video" in ct:
        return "Videos"
    if any(filename_lower.endswith(ext) for ext in audio_exts) or "audio" in ct:
        return "Music & Audio"
    if any(filename_lower.endswith(ext) for ext in doc_exts) or "document" in ct or "pdf" in ct:
        return "Documents"
    if any(filename_lower.endswith(ext) for ext in prog_exts) or "octet-stream" in ct and filename_lower.endswith(".exe"):
        return "Programs (.exe)"
    if any(filename_lower.endswith(ext) for ext in comp_exts) or "compressed" in ct or "zip" in ct:
        return "Compressed"
    return "Other Files"

def probe_direct_url(url: str) -> Dict[str, Any]:
    """Perform HTTP HEAD / Range probe on direct file or media link."""
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Accept": "*/*",
        "Range": "bytes=0-1024"
    }
    
    req = urllib.request.Request(url, headers=headers, method="GET")
    resp = urllib.request.urlopen(req, context=ctx, timeout=12)
    
    headers_dict = dict(resp.headers)
    content_length = 0
    accept_ranges = False
    content_range = headers_dict.get("Content-Range", "")
    if content_range:
        match = re.search(r"/(\d+)", content_range)
        if match:
            content_length = int(match.group(1))
            accept_ranges = True
    elif "Content-Length" in headers_dict:
        content_length = int(headers_dict["Content-Length"])
    
    if "bytes" in headers_dict.get("Accept-Ranges", "").lower():
        accept_ranges = True
        
    content_type = headers_dict.get("Content-Type", "application/octet-stream").split(";")[0].strip()
    
    # Extract filename from Content-Disposition or URL
    filename = ""
    disposition = headers_dict.get("Content-Disposition", "")
    if disposition:
        fn_match = re.search(r'filename\*?=(?:UTF-8\'\')?["\']?([^"\';\r\n]+)["\']?', disposition, re.IGNORECASE)
        if fn_match:
            filename = urllib.parse.unquote(fn_match.group(1).strip())
            
    if not filename:
        parsed_url = urllib.parse.urlparse(url)
        path = parsed_url.path
        filename = os.path.basename(path)
        if filename:
            filename = urllib.parse.unquote(filename)
        else:
            filename = "downloaded_file"
            
    # Add extension if missing
    if "." not in filename:
        if "video/mp4" in content_type:
            filename += ".mp4"
        elif "audio/mpeg" in content_type or "audio/mp3" in content_type:
            filename += ".mp3"
        elif "application/pdf" in content_type:
            filename += ".pdf"
        elif "application/zip" in content_type:
            filename += ".zip"
        else:
            filename += ".bin"

    category = detect_category(filename, content_type)
    
    return {
        "success": True,
        "is_stream": False,
        "url": url,
        "title": filename,
        "filename": filename,
        "content_type": content_type,
        "size_bytes": content_length,
        "size_formatted": format_size(content_length),
        "is_resumable": accept_ranges,
        "category": category,
        "thumbnail": None,
        "duration": "N/A",
        "formats": [{
            "format_id": "direct",
            "quality": "Direct Source",
            "resolution": "Original",
            "ext": filename.split(".")[-1] if "." in filename else "bin",
            "size_bytes": content_length,
            "size_formatted": format_size(content_length),
            "url": url,
            "note": "Direct server stream (Resumable)" if accept_ranges else "Direct download"
        }],
        "audio_formats": []
    }

def probe_with_ytdlp(url: str) -> Dict[str, Any]:
    """Inspect YouTube, Vimeo, Facebook, Twitter and social video links using yt-dlp."""
    import yt_dlp
    
    ydl_opts = {
        'quiet': True,
        'no_warnings': True,
        'skip_download': True,
        'extract_flat': False,
        'noplaylist': True,
        'ignoreerrors': True,
        'socket_timeout': 15,
    }
    
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=False)
        if not info:
            raise Exception("Failed to retrieve media information from source.")
            
        title = info.get("title", "Online Media Stream")
        duration = info.get("duration", 0)
        thumbnail = info.get("thumbnail", "")
        uploader = info.get("uploader", "Web Stream")
        webpage_url = info.get("webpage_url", url)
        
        # Categorize available video formats
        raw_formats = info.get("formats", [])
        video_formats_map = {}
        audio_formats_list = []
        
        target_resolutions = [
            ("2160p", 2160, "4K Ultra HD"),
            ("1440p", 1440, "2K QHD"),
            ("1080p", 1080, "1080p Full HD"),
            ("720p", 720, "720p HD"),
            ("480p", 480, "480p Standard"),
            ("360p", 360, "360p Mobile")
        ]
        
        for fmt in raw_formats:
            fmt_id = fmt.get("format_id", "")
            ext = fmt.get("ext", "mp4")
            height = fmt.get("height")
            filesize = fmt.get("filesize") or fmt.get("filesize_approx") or 0
            vcodec = fmt.get("vcodec", "none")
            acodec = fmt.get("acodec", "none")
            
            # Pure audio format
            if vcodec == "none" and acodec != "none":
                abr = fmt.get("abr") or 128
                audio_formats_list.append({
                    "format_id": fmt_id,
                    "ext": ext if ext in ["mp3", "m4a", "wav", "flac", "ogg"] else "mp3",
                    "bitrate": f"{int(abr)} kbps",
                    "quality": f"{int(abr)} kbps Audio",
                    "size_bytes": filesize,
                    "size_formatted": format_size(filesize) if filesize else "Estimated",
                    "url": fmt.get("url", "")
                })
            
            # Video format
            if height:
                for res_key, res_h, res_name in target_resolutions:
                    if height >= res_h - 20 and height <= res_h + 20:
                        if res_key not in video_formats_map or (filesize > video_formats_map[res_key].get("size_bytes", 0)):
                            video_formats_map[res_key] = {
                                "format_id": fmt_id,
                                "resolution": res_name,
                                "height": height,
                                "ext": "mp4",
                                "size_bytes": filesize,
                                "size_formatted": format_size(filesize) if filesize else "Calculated during download",
                                "url": fmt.get("url", ""),
                                "note": "High Quality MP4"
                            }
                            
        # Sort video formats from highest to lowest resolution
        sorted_videos = []
        for res_key, res_h, res_name in target_resolutions:
            if res_key in video_formats_map:
                sorted_videos.append(video_formats_map[res_key])
                
        # If no specific heights matched, provide standard best
        if not sorted_videos:
            best_filesize = info.get("filesize") or info.get("filesize_approx") or 0
            sorted_videos.append({
                "format_id": "best",
                "resolution": "Best Available Quality (Auto 1080p/720p)",
                "height": 1080,
                "ext": "mp4",
                "size_bytes": best_filesize,
                "size_formatted": format_size(best_filesize) if best_filesize else "Optimized",
                "url": url,
                "note": "Best MP4 Stream"
            })
            
        # Audio formats standard presets if none gathered
        if not audio_formats_list:
            audio_formats_list = [
                {"format_id": "ba-320", "ext": "mp3", "bitrate": "320 kbps", "quality": "Ultra HD MP3 (320 kbps)", "size_bytes": 0, "size_formatted": "High Fidelity"},
                {"format_id": "ba-256", "ext": "mp3", "bitrate": "256 kbps", "quality": "High Quality MP3 (256 kbps)", "size_bytes": 0, "size_formatted": "Standard"},
                {"format_id": "ba-192", "ext": "mp3", "bitrate": "192 kbps", "quality": "Medium Quality MP3 (192 kbps)", "size_bytes": 0, "size_formatted": "Efficient"},
                {"format_id": "ba-m4a", "ext": "m4a", "bitrate": "256 kbps", "quality": "Apple AAC Audio (M4A)", "size_bytes": 0, "size_formatted": "Apple Standard"}
            ]

        safe_filename = re.sub(r'[\\/*?:"<>|]', "_", title).strip() + ".mp4"
        
        return {
            "success": True,
            "is_stream": True,
            "url": webpage_url,
            "title": title,
            "filename": safe_filename,
            "thumbnail": thumbnail,
            "duration": format_duration(duration),
            "uploader": uploader,
            "category": "Videos",
            "is_resumable": True,
            "size_bytes": sorted_videos[0].get("size_bytes", 0),
            "size_formatted": sorted_videos[0].get("size_formatted", "Dynamic"),
            "formats": sorted_videos,
            "audio_formats": audio_formats_list
        }

def inspect_url(url: str) -> Dict[str, Any]:
    """Smart inspection: tries yt-dlp first for video platforms, falls back to direct HTTP probe."""
    url = url.strip()
    if not url:
        return {"success": False, "error": "URL cannot be empty"}
        
    # Check if URL belongs to known video/social domain
    domain_match = re.search(r'(?:https?://)?(?:www\.)?([^/]+)', url)
    host = domain_match.group(1).lower() if domain_match else ""
    
    stream_hosts = ["youtube.com", "youtu.be", "vimeo.com", "facebook.com", "fb.watch", 
                    "twitter.com", "x.com", "instagram.com", "tiktok.com", "dailymotion.com", 
                    "soundcloud.com", "twitch.tv", "reddit.com"]
                    
    is_stream_platform = any(h in host for h in stream_hosts)
    
    if is_stream_platform:
        try:
            return probe_with_ytdlp(url)
        except Exception as e:
            # If yt-dlp fails, try direct HTTP probe as fallback
            try:
                return probe_direct_url(url)
            except Exception as e2:
                return {
                    "success": False, 
                    "error": f"Failed to parse media metadata: {str(e)}"
                }
    else:
        # Non-streaming host: Try direct probe first, then yt-dlp if direct fails
        try:
            return probe_direct_url(url)
        except Exception as e:
            try:
                return probe_with_ytdlp(url)
            except Exception as e2:
                return {
                    "success": False, 
                    "error": f"Failed to connect to media host: {str(e)}"
                }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        output = {"success": False, "error": "Usage: extractor.py <URL>"}
    else:
        target_url = sys.argv[1]
        try:
            output = inspect_url(target_url)
        except Exception as e:
            output = {"success": False, "error": str(e)}

    # Crucial: Use ensure_ascii=True to guarantee 100% ASCII-compatible output
    # Non-ASCII characters (e.g. Hindi, Arabic, Urdu, emojis) are encoded as \uXXXX.
    # This prevents ANY 'charmap' / cp1252 UnicodeEncodeError on Windows!
    sys.stdout.write(json.dumps(output, ensure_ascii=True, indent=2))
    sys.stdout.write("\n")
    sys.stdout.flush()
