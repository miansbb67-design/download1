<?php
$pageTitle = 'FlowFetch - High-Speed Video & Audio Downloader (IDM Inspired)';
require __DIR__ . '/layouts/header.php';
?>

<!-- 3. Hero Section with Large Downloader Input -->
<section class="hero-section text-center">
    <div class="container position-relative">
        <div class="hero-badge mx-auto">
            <i class="fa-solid fa-bolt"></i> IDM-Inspired Multi-Segment Engine
        </div>

        <h1 class="hero-title">
            The Fastest Way to Download <br>
            <span>Video & Audio in 4K Quality</span>
        </h1>

        <p class="hero-subtitle">
            Extract high-fidelity streams, complete playlists, and transcode audio at maximum speed. Clean, secure, and powered by server-side FFmpeg 7.1.
        </p>

        <!-- Downloader Card -->
        <div class="downloader-card">
            <div class="input-container">
                <i class="fa-solid fa-link input-icon"></i>
                <input type="text" class="url-input" id="mainMediaUrl" placeholder="Paste authorized video or playlist URL (e.g. YouTube, Vimeo, direct MP4)..." autocomplete="off">
                
                <!-- 4. Paste URL functionality -->
                <button type="button" class="paste-btn" id="pasteClipboardBtn" onclick="FlowDownloader.pasteFromClipboard('mainMediaUrl')">
                    <i class="fa-regular fa-clipboard"></i> Paste URL
                </button>

                <!-- 5. Download / Inspect Button -->
                <button type="button" class="btn btn-primary-custom action-btn" id="analyzeMediaBtn" onclick="FlowDownloader.analyzeUrl('mainMediaUrl')">
                    <i class="fa-solid fa-arrow-down-to-line"></i> <span>Download</span>
                </button>
            </div>

            <!-- 6. Supported Media Source Information -->
            <div class="sources-bar" id="sources">
                <span class="source-pill"><i class="fa-brands fa-youtube text-danger"></i> YouTube</span>
                <span class="source-pill"><i class="fa-brands fa-vimeo-v text-info"></i> Vimeo</span>
                <span class="source-pill"><i class="fa-brands fa-soundcloud text-warning"></i> SoundCloud</span>
                <span class="source-pill"><i class="fa-brands fa-tiktok text-dark"></i> TikTok</span>
                <span class="source-pill"><i class="fa-brands fa-dailymotion text-primary"></i> Dailymotion</span>
                <span class="source-pill"><i class="fa-solid fa-file-video text-success"></i> Direct MP4 / WebM</span>
                <span class="source-pill"><i class="fa-solid fa-podcast text-secondary"></i> Podcast RSS</span>
            </div>
        </div>

        <!-- 7. Media Inspector Card (Appears dynamically upon analysis) -->
        <div class="inspector-card text-start" id="mediaInspectorCard">
            <div class="inspector-header">
                <div class="inspector-thumb-wrap">
                    <img src="" alt="Thumbnail" class="inspector-thumb" id="inspectorThumb">
                    <span class="inspector-duration" id="inspectorDuration">00:00</span>
                </div>
                <div class="inspector-info">
                    <h4 id="inspectorTitle">Analyzing Media...</h4>
                    <div class="inspector-meta mb-2">
                        <span><i class="fa-regular fa-user me-1"></i> <span id="inspectorAuthor">Source</span></span>
                        <span><i class="fa-regular fa-eye me-1"></i> <span id="inspectorViews">0</span> Views</span>
                        <span><i class="fa-solid fa-globe me-1"></i> <span id="inspectorExtractor">Web Media</span></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <label class="form-label mb-0 small text-muted fw-bold">Save into Folder:</label>
                        <select class="form-select form-select-sm w-auto" id="inspectorFolderSelect">
                            <option value="">Default (Unfiled)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Format Selector Tabs -->
            <div class="format-tabs">
                <ul class="nav nav-tabs nav-tabs-custom" id="mediaFormatTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="video-tab" data-bs-toggle="tab" data-bs-target="#tab-video" type="button" role="tab">
                            <i class="fa-solid fa-film me-1"></i> Video Streams
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="audio-tab" data-bs-toggle="tab" data-bs-target="#tab-audio" type="button" role="tab">
                            <i class="fa-solid fa-headphones me-1"></i> Audio Only
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="mediaFormatTabContent">
                <!-- Video Stream Formats Grid -->
                <div class="tab-pane fade show active" id="tab-video" role="tabpanel">
                    <div class="format-grid" id="videoFormatsGrid">
                        <!-- Filled by JS -->
                    </div>
                </div>

                <!-- Audio Stream Formats Grid -->
                <div class="tab-pane fade" id="tab-audio" role="tabpanel">
                    <div class="format-grid" id="audioFormatsGrid">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. Dedicated Audio Downloader Section -->
<section class="py-5" id="audio-downloader" style="background: #FFFFFF;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-3">
                    <i class="fa-solid fa-music me-1"></i> Studio Grade Audio
                </span>
                <h2 class="display-6 fw-bold mb-3">Direct Audio Extractor & High-Bitrate Ripper</h2>
                <p class="text-muted mb-4">
                    Extract crystal-clear sound streams directly from authorized videos and music platforms without video bloat. Choose between pristine 320 kbps MP3, studio WAV, lossless FLAC, AAC, or OGG.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1rem;">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Up to 320 kbps</h6>
                                <small class="text-muted">Maximum fidelity MP3</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1rem;">
                                <i class="fa-solid fa-compact-disc"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Lossless FLAC & WAV</h6>
                                <small class="text-muted">Bit-perfect extraction</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 bg-light-mint" style="border-radius: var(--radius-md);">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-headphones text-primary-custom me-2"></i>Quick Audio Grabber</h5>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Media or Track URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="audioOnlyUrl" placeholder="Paste link from SoundCloud, YouTube, or Podcast...">
                            <button class="btn btn-outline-secondary" type="button" onclick="FlowDownloader.pasteFromClipboard('audioOnlyUrl')"><i class="fa-regular fa-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted fw-bold">Format</label>
                            <select class="form-select" id="quickAudioFormat">
                                <option value="mp3" selected>MP3 (.mp3)</option>
                                <option value="m4a">M4A / AAC (.m4a)</option>
                                <option value="wav">WAV (.wav)</option>
                                <option value="flac">FLAC (.flac)</option>
                                <option value="ogg">OGG (.ogg)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted fw-bold">Bitrate</label>
                            <select class="form-select" id="quickAudioBitrate">
                                <option value="320k" selected>320 kbps (Extreme)</option>
                                <option value="256k">256 kbps (High)</option>
                                <option value="192k">192 kbps (Standard)</option>
                                <option value="128k">128 kbps (Compact)</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary-custom w-100 py-2" onclick="FlowDownloader.quickAudioDownload()">
                        <i class="fa-solid fa-download me-1"></i> Extract & Download Audio
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 9. Playlist Downloader Section (MAJOR FEATURE) -->
<section class="py-5" id="playlist-downloader" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-list-check me-1"></i> Batch Processing
            </span>
            <h2 class="display-6 fw-bold">Complete Playlist Downloader</h2>
            <p class="text-muted">
                Analyze complete playlists, select individual videos or download the entire batch with one click into a dedicated folder.
            </p>
        </div>

        <div class="downloader-card mb-4">
            <div class="input-container">
                <i class="fa-solid fa-rectangle-list input-icon"></i>
                <input type="text" class="url-input" id="playlistUrlInput" placeholder="Paste full playlist URL (e.g. YouTube Playlist, Vimeo Album)..." autocomplete="off">
                <button type="button" class="paste-btn" onclick="FlowDownloader.pasteFromClipboard('playlistUrlInput')">
                    <i class="fa-regular fa-clipboard"></i> Paste
                </button>
                <button type="button" class="btn btn-primary-custom action-btn" id="analyzePlaylistBtn" onclick="FlowPlaylist.analyzePlaylist()">
                    <i class="fa-solid fa-magnifying-glass"></i> <span>Fetch Playlist</span>
                </button>
            </div>
        </div>

        <!-- Playlist Interactive Table Wrap -->
        <div class="playlist-table-wrap d-none" id="playlistTableWrap">
            <div class="playlist-toolbar">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="FlowPlaylist.toggleSelectAll(this)" checked>
                        <label class="form-check-label fw-bold" for="selectAllCheckbox">Select All (<span id="plSelectedCount">0</span>/<span id="plTotalCount">0</span>)</label>
                    </div>
                    <span class="text-muted">|</span>
                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 380px;" id="plTitleDisplay">Playlist Name</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm w-auto" id="plBatchQuality">
                        <option value="1080p">1080p Full HD</option>
                        <option value="720p" selected>720p HD</option>
                        <option value="480p">480p SD</option>
                        <option value="360p">360p Mobile</option>
                    </select>
                    <div class="form-check form-check-inline mb-0 ms-2">
                        <input class="form-check-input" type="checkbox" id="plCreateFolderCheck" checked>
                        <label class="form-check-label small fw-bold" for="plCreateFolderCheck">Dedicated Folder</label>
                    </div>
                    <button type="button" class="btn btn-primary-custom btn-sm" onclick="FlowPlaylist.startBatchDownload()">
                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> Download Selected
                    </button>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 60px;">#</th>
                            <th style="width: 120px;">Thumbnail</th>
                            <th>Title & Author</th>
                            <th style="width: 100px;">Duration</th>
                            <th style="width: 120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="playlistTableBody">
                        <!-- Dynamically populated entries -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- 10. 4K Video Quality Section -->
<section class="py-5" id="quality-4k" style="background: #FFFFFF;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-2 fw-bold mb-3">
                    <i class="fa-solid fa-sparkles me-1"></i> Ultra HD 2160p
                </span>
                <h2 class="display-6 fw-bold mb-3">Crystal-Clear 4K UHD Video Downloads</h2>
                <p class="text-muted mb-4">
                    FlowFetch inspects the source server's raw bitrate streams to preserve original color grading, 60fps frame rates, and sharp textures up to 3840x2160 resolution.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge badge-4k p-2 px-3 fw-bold">4K 2160p</span>
                    <span class="badge badge-4k p-2 px-3 fw-bold">2K 1440p</span>
                    <span class="badge badge-fhd p-2 px-3 fw-bold">FHD 1080p</span>
                    <span class="badge badge-hd p-2 px-3 fw-bold">HD 720p</span>
                    <span class="badge bg-light text-dark p-2 px-3 fw-bold">480p SD</span>
                    <span class="badge bg-light text-dark p-2 px-3 fw-bold">360p</span>
                </div>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="fa-solid fa-check text-primary-custom me-2"></i> Automatically stitches video + high bitrate audio streams with FFmpeg</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-primary-custom me-2"></i> Only displays resolutions genuinely offered by the target media</li>
                    <li><i class="fa-solid fa-check text-primary-custom me-2"></i> Clean MP4 output compatible with all smart TVs, phones, and editing software</li>
                </ul>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="p-4 rounded-4 bg-dark text-white text-center position-relative overflow-hidden shadow-lg" style="background: linear-gradient(135deg, #1A2420 0%, #0F1714 100%);">
                    <div class="py-5">
                        <i class="fa-solid fa-display display-1 text-secondary-custom mb-3"></i>
                        <h4 class="fw-bold">4K UHD Resolution Ready</h4>
                        <p class="text-muted small">Up to 3840 x 2160 pixels @ 60 FPS</p>
                        <div class="badge bg-secondary px-3 py-2">H.264 / AV1 / VP9 Auto-Detection</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 11. Video to Audio Converter (Real FFmpeg Integration) -->
<section class="py-5" id="converter-section" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-repeat me-1"></i> Local Video Transcoder
            </span>
            <h2 class="display-6 fw-bold">Video-to-Audio Converter</h2>
            <p class="text-muted">
                Upload any video file from your computer and convert it directly into high-fidelity MP3, WAV, AAC, M4A, FLAC, or OGG using server-side FFmpeg.
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                    <!-- Drag and Drop Zone -->
                    <div class="converter-dropzone" id="converterDropzone" onclick="document.getElementById('videoFileInput').click()">
                        <input type="file" id="videoFileInput" class="d-none" accept="video/mp4,video/webm,video/x-matroska,video/quicktime,video/x-msvideo" onchange="FlowConverter.handleFileSelect(this.files)">
                        <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                        <h5 class="fw-bold mb-1">Drag and drop your video file here</h5>
                        <p class="text-muted small mb-0">Supports MP4, WebM, MKV, MOV, AVI up to 500 MB</p>
                    </div>

                    <!-- File Details and Conversion Options -->
                    <div class="mt-4 d-none" id="converterOptionsPanel">
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-file-video text-primary-custom fs-3"></i>
                                <div>
                                    <div class="fw-bold" id="convFilename">video.mp4</div>
                                    <small class="text-muted" id="convFilesize">0 MB</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="FlowConverter.reset()"><i class="fa-solid fa-trash"></i></button>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-bold">Output Audio Format</label>
                                <select class="form-select" id="convTargetFormat">
                                    <option value="mp3" selected>MP3 (Standard Audio)</option>
                                    <option value="wav">WAV (Uncompressed PCM)</option>
                                    <option value="m4a">M4A (Apple AAC)</option>
                                    <option value="flac">FLAC (Lossless Hi-Res)</option>
                                    <option value="ogg">OGG (Vorbis)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-bold">Audio Bitrate</label>
                                <select class="form-select" id="convTargetBitrate">
                                    <option value="320k" selected>320 kbps (High Fidelity)</option>
                                    <option value="256k">256 kbps (Studio)</option>
                                    <option value="192k">192 kbps (Standard)</option>
                                    <option value="128k">128 kbps (Lightweight)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Progress Bar Container -->
                        <div class="d-none mb-3" id="convProgressWrap">
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span id="convStatusText">Converting audio with FFmpeg...</span>
                                <span id="convProgressPct">0%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="convProgressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Download Converted Result -->
                        <div class="d-none text-center p-3 bg-soft-mint rounded-3 mb-3" id="convResultWrap">
                            <i class="fa-solid fa-circle-check text-primary-custom fs-2 mb-2"></i>
                            <h6 class="fw-bold mb-3">Conversion Completed Successfully!</h6>
                            <a href="#" class="btn btn-primary-custom" id="convDownloadLink" download>
                                <i class="fa-solid fa-download me-1"></i> Download Converted Audio
                            </a>
                        </div>

                        <button type="button" class="btn btn-primary-custom w-100 py-2" id="startConversionBtn" onclick="FlowConverter.startTranscode()">
                            <i class="fa-solid fa-gears me-1"></i> Start FFmpeg Conversion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 12. How It Works Section -->
<section class="py-5" id="how-it-works" style="background: #FFFFFF;">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-lightbulb me-1"></i> Simple Workflow
            </span>
            <h2 class="display-6 fw-bold">How FlowFetch Works</h2>
            <p class="text-muted">Download authorized media in 3 effortless steps without bloat or confusing menus.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon-box mx-auto">
                        <i class="fa-solid fa-paste"></i>
                    </div>
                    <h5 class="fw-bold">1. Paste Media URL</h5>
                    <p class="text-muted small">
                        Paste the video or playlist link into the downloader input bar. Click the handy Paste button to grab it straight from your clipboard.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon-box mx-auto">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <h5 class="fw-bold">2. Select Quality & Format</h5>
                    <p class="text-muted small">
                        Review the probed resolutions up to 4K 2160p or select audio formats (MP3, WAV, FLAC). Pick your destination folder.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon-box mx-auto">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <h5 class="fw-bold">3. Multi-Segment Download</h5>
                    <p class="text-muted small">
                        The IDM-inspired engine splits streams into parallel chunks for maximum throughput with pause, resume, and instant save.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 13. Features Section -->
<section class="py-5" id="features" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-cube me-1"></i> Production Features
            </span>
            <h2 class="display-6 fw-bold">Engineered for Performance</h2>
            <p class="text-muted">A complete suite of download management tools built with enterprise-grade security.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-network-wired"></i>
                    </div>
                    <h5 class="fw-bold">IDM-Speed Multi-Threading</h5>
                    <p class="text-muted small mb-0">Splits heavy video streams into concurrent segments to maximize bandwidth utilization and avoid throttled connections.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-folder-tree"></i>
                    </div>
                    <h5 class="fw-bold">Virtual Folder Manager</h5>
                    <p class="text-muted small mb-0">Organize your downloads into custom categorized folders, move items, and search file history effortlessly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-shield-virus"></i>
                    </div>
                    <h5 class="fw-bold">SSRF Protected & Secure</h5>
                    <p class="text-muted small mb-0">Protects internal infrastructure with DNS resolution checks, rejecting private RFC 1918 and loopback subnets.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-circle-play"></i>
                    </div>
                    <h5 class="fw-bold">Resumable HTTP Range</h5>
                    <p class="text-muted small mb-0">Completed files support HTTP 206 Partial Content, allowing native download managers like IDM to pause and resume seamlessly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h5 class="fw-bold">Batch Playlist Processing</h5>
                    <p class="text-muted small mb-0">Select all or pick specific videos from massive playlists and download them sequentially into automatically created folders.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h5 class="fw-bold">Automated Retention Cleanup</h5>
                    <p class="text-muted small mb-0">Automated retention cron purges temporary files older than 48 hours to preserve disk performance and user privacy.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 14. Supported Formats Table -->
<section class="py-5" id="supported-formats" style="background: #FFFFFF;">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-file-audio me-1"></i> Formats & Codecs
            </span>
            <h2 class="display-6 fw-bold">Supported Formats & Containers</h2>
            <p class="text-muted">Compatible with all major audio-video containers and modern codec standards.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive shadow-sm rounded-4">
                    <table class="table table-bordered mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Extension</th>
                                <th>Supported Resolutions / Bitrates</th>
                                <th>Compatible Codecs</th>
                                <th>Recommended Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-film text-primary-custom me-2"></i>Video</td>
                                <td><span class="badge bg-primary">MP4</span></td>
                                <td>360p, 480p, 720p HD, 1080p FHD, 1440p 2K, 2160p 4K</td>
                                <td>H.264 / AVC, AAC</td>
                                <td>Universal (PC, iPhone, Android, TVs)</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-film text-info me-2"></i>Video</td>
                                <td><span class="badge bg-info">WebM</span></td>
                                <td>Up to 4K UHD 60fps</td>
                                <td>VP9, AV1, Opus</td>
                                <td>Modern Browsers, Linux, YouTube</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-headphones text-success me-2"></i>Audio</td>
                                <td><span class="badge bg-success">MP3</span></td>
                                <td>128 kbps, 192 kbps, 256 kbps, 320 kbps</td>
                                <td>MPEG-1 Audio Layer III</td>
                                <td>All Audio Players, Car Stereos</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-headphones text-warning me-2"></i>Audio</td>
                                <td><span class="badge bg-warning text-dark">WAV</span></td>
                                <td>1411 kbps (16-bit / 44.1kHz PCM)</td>
                                <td>Linear PCM Uncompressed</td>
                                <td>Studio DAWs, Audacity, Editors</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-headphones text-secondary me-2"></i>Audio</td>
                                <td><span class="badge bg-secondary">M4A / AAC</span></td>
                                <td>Up to 256 kbps</td>
                                <td>Advanced Audio Codec</td>
                                <td>Apple Music, Mac, iPod, QuickTime</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-start"><i class="fa-solid fa-headphones text-danger me-2"></i>Audio</td>
                                <td><span class="badge bg-danger">FLAC</span></td>
                                <td>Lossless Compression (100% Bit-Perfect)</td>
                                <td>Free Lossless Audio Codec</td>
                                <td>Audiophile Headsets, Hi-Res DAPs</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 15. Frequently Asked Questions -->
<section class="py-5" id="faq" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                <i class="fa-solid fa-question me-1"></i> Knowledge Base
            </span>
            <h2 class="display-6 fw-bold">Frequently Asked Questions</h2>
            <p class="text-muted">Everything you need to know about downloading, formatting, and file limits.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion shadow-sm rounded-4 overflow-hidden" id="faqAccordion">
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Can I download 4K videos at 60 FPS?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes! Whenever the source video offers a 2160p (4K) stream, FlowFetch detects it and automatically merges the high-definition video track with the highest available audio stream using server-side FFmpeg.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How does the Playlist Downloader work?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Paste the URL of any authorized public playlist. FlowFetch inspects all entries, displays thumbnails and durations, and lets you select specific items or check "Select All". You can assign a custom folder for the playlist downloads.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Can I pause and resume my downloads?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes. During server processing, you can use the IDM modal Pause/Resume controls. Once ready on disk, the download link supports HTTP 206 Partial Content, meaning browser downloads and external tools like IDM can resume broken transfers.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                What is the retention policy for downloaded files?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                To maintain privacy and optimal server storage, downloaded media is retained for 48 hours before automated cleanup. You can save files directly to your device via your browser.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Does FlowFetch download copyrighted or protected DRM media?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                No. FlowFetch strictly operates on authorized, public domain, royalty-free, or permitted media through standard APIs. It does not bypass DRM encryption or paywalled access controls.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 16. Contact Section -->
<section class="py-5" id="contact" style="background: #FFFFFF;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: var(--radius-md);">
                    <div class="text-center mb-4">
                        <span class="badge bg-soft-mint text-primary-custom px-3 py-2 fw-bold mb-2">
                            <i class="fa-solid fa-envelope me-1"></i> Get In Touch
                        </span>
                        <h3 class="fw-bold">Contact Support & Feedback</h3>
                        <p class="text-muted small">Have an inquiry regarding supported media platforms or enterprise deployment?</p>
                    </div>

                    <form id="contactForm" onsubmit="FlowApp.handleContactSubmit(event)">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Your Name</label>
                                <input type="text" class="form-control" name="name" required placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Email Address</label>
                                <input type="email" class="form-control" name="email" required placeholder="name@domain.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Subject</label>
                            <input type="text" class="form-control" name="subject" required placeholder="Question or Feature Request">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Message</label>
                            <textarea class="form-control" name="message" rows="4" required placeholder="Describe your inquiry..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100 py-2">
                            <i class="fa-solid fa-paper-plane me-1"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layouts/footer.php'; ?>
