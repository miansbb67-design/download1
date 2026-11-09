# DownloadPro – Advanced IDM-Style Video & Audio Downloader

**DownloadPro** is a modern, high-performance download manager application inspired by **Internet Download Manager (IDM)**. It features a dark-themed UI, multi-chunk resumable HTTP downloads, video stream inspection (supporting resolutions up to 4K 2160p), audio conversion, local FFmpeg audio extraction, and full UTF-8 Unicode encoding safety.

![Theme](assets/css/style.css)

---

## 🚀 Key Features

1. **IDM-Caliber Download Engine**:
   - Multi-chunk HTTP Range request support (`206 Partial Content`).
   - Pause, resume, stop, and retry downloads seamlessly.
   - Real-time download speed calculation (smoothed moving average) and ETA estimation.
   - Queue management with configurable maximum concurrent connections.

2. **Video & Stream Downloader**:
   - URL inspection with format and resolution detection.
   - Select video quality up to 4K Ultra HD (2160p, 1440p, 1080p, 720p, 480p, 360p).
   - Video metadata preview: thumbnail, title, duration, source, and estimated size.

3. **Audio Downloader & High-Bitrate Formats**:
   - Extract direct audio streams in MP3, M4A (Apple AAC), WAV, or FLAC.
   - Audio bitrate presets: 320 kbps (Ultra Studio), 256 kbps (High), 192 kbps, and 128 kbps.

4. **Audio Extraction from Local Video (FFmpeg 7.1 Integration)**:
   - Import local video files (`.mp4`, `.mkv`, `.webm`, `.avi`, `.mov`).
   - Lossless and high-bitrate conversion using integrated FFmpeg.

5. **Windows UTF-8 Unicode Encoding Fix**:
   - Completely handles non-ASCII characters (Hindi, Punjabi, Arabic, Urdu, Cyrillic, Chinese, and emojis).
   - Resolves previous Windows console `UnicodeEncodeError: 'charmap' codec can't encode characters` permanently.

6. **Organized Categories & Status Filters**:
   - Categories: `All Downloads`, `Videos`, `Music & Audio`, `Documents`, `Programs (.exe)`, `Compressed`.
   - Status Filters: `In Progress`, `Completed`, `Paused`, `Failed`.
   - Real-time search filter and instant item counts.

---

## 🎨 Design & Palette

- **Theme**: IDM Dark Charcoal & Deep Midnight Navy (`#070d19` / `#09101f` / `#0d1629`).
- **Primary Accent**: Cyan Blue (`#0ea5e9` / `#0284c7`).
- **Success / Completed**: Emerald Green (`#10b981`).
- **Paused / Warning**: Amber Gold (`#f59e0b`).
- **Stopped / Error**: Crimson Red (`#ef4444`).
- **Stats Dashboard**: 5 live cards displaying *Total Downloads*, *Completed*, *Active / Queue*, *Current Speed*, and *Total Data*.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3 (Modern Glassmorphism), JavaScript (ES6+), Plus Jakarta Sans typography.
- **Backend / API**: PHP 8.2+ with PDO SQLite & MySQL support, cURL with HTTP Range streaming.
- **Media Engine**: Python 3.11 with `yt-dlp` and FFmpeg 7.1 Essentials.
- **Desktop Runtime**: Electron.js with native file pickers, tray integration, and shell opening.

---

## 💻 Running the Application

### Option 1: In XAMPP (Recommended Web Server)
1. Open XAMPP Control Panel.
2. Ensure the repository is located at `c:\xampp\htdocs\download1`.
3. Open your browser and navigate to:
   ```
   http://localhost/download1
   ```

### Option 2: Standalone Built-in PHP Server
Run from the project root directory:
```bash
php -S 127.0.0.1:8000
```
Then visit `http://127.0.0.1:8000` in any web browser.

### Option 3: Desktop Electron Application
```bash
npm install
npm start
```

---

## 📁 Project Structure

```
download1/
├── api/
│   ├── downloads.php       # Downloads CRUD & status polling
│   ├── extract_audio.php   # FFmpeg local video audio extraction
│   ├── open.php            # File/Folder explorer integration
│   ├── probe.php           # Media metadata inspection
│   └── settings.php        # User preferences management
├── app/
│   ├── Core/
│   │   ├── Database.php    # PDO SQLite / MySQL database manager
│   │   ├── Response.php    # JSON response formatter
│   │   └── Security.php    # URL validation and sanitization
│   └── Services/
│       ├── DownloadEngine.php # Core chunked download logic
│       └── MediaEngine.php    # Python & FFmpeg process wrappers
├── assets/
│   ├── css/style.css       # Complete dark IDM stylesheet
│   └── js/app.js           # Interactive UI, polling & events
├── electron/
│   ├── main.js             # Electron main window & tray
│   └── preload.js          # Desktop IPC bridge
├── engine/
│   ├── audio_extractor.py  # FFmpeg audio conversion script
│   ├── direct_downloader.php # Range chunked download worker
│   ├── downloader.py       # Stream download worker
│   └── extractor.py        # UTF-8 safe metadata probe
├── storage/
│   ├── database/           # SQLite database store
│   ├── downloads/          # Default download destination
│   └── temp/               # Temporary progress chunks
├── index.php               # Main UI entry point
├── package.json            # Electron & scripts configuration
└── README.md
```

---

## 🔒 Security & Safe Downloads

- Validates URLs against safe HTTP/HTTPS schemes.
- Disinfects filenames to prevent path traversal (`../`) attacks.
- Operates strictly on authorized public streams and direct media links.
