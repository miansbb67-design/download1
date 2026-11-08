# FlowFetch Pro - Advanced Video & Audio Downloader Platform

> A modern, production-grade, IDM-inspired Video and Audio Downloader web application engineered with PHP 8.2+, Python 3.11, FFmpeg 7.1, Node.js, and MySQL.

---

## 🌟 Key Highlights & Architectural Overview

FlowFetch Pro is an enterprise-caliber media extraction suite delivering the power and performance of native download managers (like Internet Download Manager) straight to the web browser.

- **4K Ultra HD & 60 FPS Extraction**: Supports 2160p (4K UHD), 1440p (2K), 1080p (FHD), 720p (HD), 480p, and 360p video streams with automatic audio-video stitching via server-side FFmpeg.
- **Complete Batch Playlist Downloader**: Inspects playlist metadata, durations, and thumbnails. Supports individual track selection, "Select All", and automatic organization into dedicated virtual folders.
- **Dedicated Audio Extractor**: Extracts audio in pristine quality with options for MP3 (up to 320 kbps), lossless FLAC, studio WAV, M4A/AAC, and OGG Vorbis.
- **Local Video-to-Audio Transcoder**: Drag-and-drop video upload zone with real-time FFmpeg transcoding progress.
- **IDM-Inspired Download Engine**: Real-time 8-segment stream visualization, transfer speed metrics (MB/s), elapsed time, ETA countdown, and Pause/Resume/Cancel controls.
- **RFC 7233 Resumable Downloads**: The direct file delivery endpoint supports HTTP 206 Partial Content (HTTP Range headers), allowing desktop download accelerators like IDM to resume broken downloads.
- **Virtual Folder Management**: Organize downloads into custom color-coded folders with search and category filters.
- **Native Local Directory Picker**: Integrated with the modern HTML5 File System Access API (`showDirectoryPicker()`).
- **Complete Authentication & User Roles**: User registration, login, profile management, and an Administrator Command Center.
- **Advanced Security**: Built-in SSRF protection (private IP / loopback firewall), CSRF token validation, XSS sanitization, rate limiting, and automated 48-hour storage retention cleanup.

---

## 🛠️ Technology Stack

| Layer | Technologies |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, FontAwesome 6, GSAP 3.12 (GreenSock), Dark/Light Modes |
| **Backend** | PHP 8.2+ OOP MVC Architecture, RESTful JSON APIs, HTTP Range Streaming |
| **Media Engine** | Python 3.11, `yt-dlp` (2026.10), FFmpeg 7.1 Essentials |
| **Companion Daemon** | Node.js (Zero-dependency SSE Progress Broadcaster & Retention Worker) |
| **Database** | MySQL 8.0+ / MariaDB 10.4+, PDO with Prepared Statements |

---

## 🗄️ Database Architecture (14 Relational Tables)

1. `users` — Authentication credentials, role (`admin`, `user`), status (`active`, `suspended`), tokens.
2. `user_profiles` — Avatars, preferred resolution (e.g. 2160p), preferred format, dark mode state.
3. `folders` — Virtual directory taxonomy with custom hex colors and icons.
4. `download_jobs` — Master record for every download stream with progress, speed, ETA, and state.
5. `download_items` — Sub-streams or playlist entry tasks associated with a job.
6. `playlists` — Batch playlist jobs with source metadata, item counts, and folder links.
7. `playlist_items` — Track-level playlist records with durations and selection states.
8. `downloaded_files` — Catalog of completed files on disk with MIME types, sizes, and expiration timestamps.
9. `conversion_jobs` — FFmpeg transcoding queue tracking video-to-audio progress.
10. `supported_sources` — Platform registry (YouTube, Vimeo, SoundCloud, TikTok, Direct Streams) with resolution caps.
11. `user_settings` — Key-value user preferences.
12. `notifications` — Real-time alerts and user event broadcasts.
13. `admin_settings` — Platform-wide configurations (storage retention hours, rate limits, upload caps, guest permissions).
14. `system_logs` — Auditing logs with levels (`INFO`, `WARNING`, `ERROR`), client IPs, and JSON context.

---

## 🚀 Quick Setup & Installation (XAMPP on Windows / Linux)

### 1. Requirements
- XAMPP with PHP 8.2+ and MySQL / MariaDB
- Python 3.10+ (with `yt-dlp` installed: `pip install yt-dlp`)
- FFmpeg 7.x installed and added to System PATH
- Node.js (v18+)

### 2. Database Migration
Ensure MySQL is running in XAMPP (port 3306), then run:
```bash
php database/migrate.php
```
This automatically creates the `download1_db` database, runs `database/schema.sql`, and seeds initial settings and accounts.

### 3. Pre-Configured Accounts
- **Administrator**: `admin@download1.local` / `Admin@123456`
- **Standard User**: `user@download1.local` / `User@123456`

### 4. Running the Companion Worker (Optional for Live SSE)
```bash
node server/worker.js
```
The companion worker runs on `http://127.0.0.1:3001` and provides real-time Server-Sent Events (SSE) and retention cleanup.

### 5. Accessing the Platform
Visit in your web browser:
```
http://localhost/download1/
```

---

## 🔒 Security & Compliance

- **SSRF Protection**: Every target URL is validated before DNS resolution. Access to loopback addresses (`127.0.0.1`, `::1`), private subnets (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), and cloud metadata IP (`169.254.169.254`) is strictly blocked.
- **Rate Limiting**: Protects media extraction APIs against abuse.
- **Safe Media Policy**: FlowFetch strictly processes authorized, public domain, royalty-free, or permitted user media. DRM bypass and access restriction evasion are prohibited.

