#!/usr/bin/env python3
"""
FlowFetch Video-to-Audio Converter Engine
Direct FFmpeg audio transcoding engine with real-time progress parsing.
"""

import sys
import io
import os
import json
import argparse
import subprocess
import re
import time

# Ensure Windows subprocess piping handles non-ASCII characters safely
if hasattr(sys.stdout, 'buffer'):
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'buffer'):
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

def parse_time_to_seconds(time_str):
    try:
        parts = time_str.split(':')
        if len(parts) == 3:
            return float(parts[0]) * 3600 + float(parts[1]) * 60 + float(parts[2])
    except Exception:
        pass
    return 0.0

def get_media_duration(ffmpeg_bin, input_path):
    cmd = [ffmpeg_bin, "-i", input_path]
    p = subprocess.Popen(cmd, stderr=subprocess.PIPE, stdout=subprocess.PIPE, universal_newlines=True)
    _, stderr = p.communicate()
    
    match = re.search(r"Duration:\s*(\d{2}:\d{2}:\d{2}\.\d+)", stderr)
    if match:
        return parse_time_to_seconds(match.group(1))
    return 0.0

def update_status(status_file, data):
    try:
        tmp_file = status_file + ".tmp"
        with open(tmp_file, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False)
        if os.path.exists(tmp_file):
            os.replace(tmp_file, status_file)
    except Exception:
        pass

def main():
    parser = argparse.ArgumentParser(description="FFmpeg Video to Audio Converter")
    parser.add_argument("--input", required=True, help="Input video file path")
    parser.add_argument("--output", required=True, help="Output audio file path")
    parser.add_argument("--format", default="mp3", choices=['mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg'])
    parser.add_argument("--bitrate", default="192k", help="Bitrate (128k, 192k, 256k, 320k)")
    parser.add_argument("--job-id", required=True, help="Job UUID")
    parser.add_argument("--ffmpeg-bin", default="ffmpeg", help="Path to ffmpeg executable")
    args = parser.parse_args()

    if not os.path.exists(args.input):
        print(json.dumps({"success": False, "error": f"Input file not found: {args.input}"}))
        sys.exit(1)

    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    temp_dir = os.path.join(os.path.dirname(os.path.dirname(args.output)), "temp")
    os.makedirs(temp_dir, exist_ok=True)
    status_file = os.path.join(temp_dir, f"{args.job_id}.json")

    duration = get_media_duration(args.ffmpeg_bin, args.input)

    update_status(status_file, {
        "status": "processing",
        "progress": 5.0,
        "speed": "Transcoding...",
        "eta": "Calculating..."
    })

    # Build ffmpeg command based on format
    cmd = [args.ffmpeg_bin, "-y", "-i", args.input, "-vn"]

    if args.format == 'mp3':
        cmd.extend(["-c:a", "libmp3lame", "-b:a", args.bitrate])
    elif args.format == 'm4a' or args.format == 'aac':
        cmd.extend(["-c:a", "aac", "-b:a", args.bitrate])
    elif args.format == 'wav':
        cmd.extend(["-c:a", "pcm_s16le"])
    elif args.format == 'flac':
        cmd.extend(["-c:a", "flac"])
    elif args.format == 'ogg':
        cmd.extend(["-c:a", "libvorbis", "-b:a", args.bitrate])

    cmd.extend(["-progress", "pipe:1", args.output])

    try:
        proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, universal_newlines=True)
        
        last_progress = 5.0
        while True:
            line = proc.stdout.readline()
            if not line and proc.poll() is not None:
                break
            
            if line:
                line = line.strip()
                if line.startswith("out_time_ms="):
                    val = line.split("=")[1]
                    if val.isdigit() and duration > 0:
                        cur_sec = int(val) / 1000000.0
                        pct = min(round((cur_sec / duration) * 100, 2), 99.0)
                        if pct > last_progress:
                            last_progress = pct
                            update_status(status_file, {
                                "status": "processing",
                                "progress": pct,
                                "speed": "Converting with FFmpeg",
                                "eta": f"{max(0, int(duration - cur_sec))}s remaining"
                            })

        proc.wait()
        if proc.returncode == 0 and os.path.exists(args.output):
            out_size = os.path.getsize(args.output)
            update_status(status_file, {
                "status": "completed",
                "progress": 100.0,
                "output_file": args.output,
                "file_size": out_size,
                "speed": "Done",
                "eta": "00:00"
            })
            print(json.dumps({"success": True, "output": args.output, "size": out_size}))
        else:
            raise Exception("FFmpeg process terminated with error code.")

    except Exception as e:
        update_status(status_file, {
            "status": "failed",
            "progress": 0.0,
            "error": str(e)
        })
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()

