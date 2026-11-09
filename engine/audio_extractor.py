#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DownloadPro - FFmpeg Audio Extraction Engine
Extracts audio from local video files (MP4, MKV, WebM, AVI, MOV) into MP3, WAV, M4A, FLAC.
"""

import sys
import os
import io
import json
import subprocess
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

def extract_audio(input_file: str, output_format: str = "mp3", bitrate: str = "320k", output_dir: str = "") -> dict:
    if not os.path.exists(input_file):
        return {"success": False, "error": f"Input file not found: {input_file}"}

    base_name = os.path.splitext(os.path.basename(input_file))[0]
    out_ext = output_format.lower().strip().replace(".", "")
    if not out_ext:
        out_ext = "mp3"

    if not output_dir:
        output_dir = os.path.dirname(input_file)
    os.makedirs(output_dir, exist_ok=True)

    output_file = os.path.join(output_dir, f"{base_name}.{out_ext}")
    # Handle filename collision
    counter = 1
    while os.path.exists(output_file):
        output_file = os.path.join(output_dir, f"{base_name}_{counter}.{out_ext}")
        counter += 1

    cmd = [FFMPEG_PATH, "-y", "-i", input_file, "-vn"]

    if out_ext == "mp3":
        cmd.extend(["-c:a", "libmp3lame", "-b:a", bitrate])
    elif out_ext == "m4a":
        cmd.extend(["-c:a", "aac", "-b:a", bitrate])
    elif out_ext == "wav":
        cmd.extend(["-c:a", "pcm_s16le"])
    elif out_ext == "flac":
        cmd.extend(["-c:a", "flac"])
    elif out_ext == "aac":
        cmd.extend(["-c:a", "aac", "-b:a", bitrate])
    else:
        cmd.extend(["-c:a", "libmp3lame", "-b:a", bitrate])

    cmd.append(output_file)

    proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, encoding='utf-8', errors='replace')

    if proc.returncode != 0:
        return {
            "success": False,
            "error": f"FFmpeg failed with code {proc.returncode}: {proc.stderr[-400:]}"
        }

    file_size = os.path.getsize(output_file) if os.path.exists(output_file) else 0

    return {
        "success": True,
        "input_file": input_file,
        "output_file": output_file,
        "filename": os.path.basename(output_file),
        "format": out_ext.upper(),
        "bitrate": bitrate,
        "size_bytes": file_size,
        "size_formatted": f"{file_size / (1024*1024):.2f} MB"
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        res = {"success": False, "error": "Usage: audio_extractor.py <input_video> [format] [bitrate] [output_dir]"}
    else:
        inp = sys.argv[1]
        fmt = sys.argv[2] if len(sys.argv) > 2 else "mp3"
        br = sys.argv[3] if len(sys.argv) > 3 else "320k"
        out_d = sys.argv[4] if len(sys.argv) > 4 else ""
        res = extract_audio(inp, fmt, br, out_d)

    sys.stdout.write(json.dumps(res, ensure_ascii=True, indent=2))
    sys.stdout.write("\n")
    sys.stdout.flush()
