<?php
use App\Core\Auth;
use App\Core\Security;

$currentUser = Auth::user();
$csrfToken = Security::getCsrfToken();
$pageTitle = 'DownloadPro v1.0 IDM Edition - Advanced Video & Audio Downloader';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="DownloadPro IDM Edition - High-speed multi-threaded video and audio downloader. Extract 4K videos, playlists, and convert media with FFmpeg.">
    <link rel="icon" type="image/svg+xml" href="public/assets/img/logo.svg">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- App Styles -->
    <link rel="stylesheet" href="public/css/app.css">
    <link rel="stylesheet" href="public/css/dark-mode.css">

    <!-- GSAP Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
</head>
<body class="dark-mode">

<div class="idm-app-wrapper">

    <!-- Top Window / Action Toolbar (Exact Match with Screenshot) -->
    <header class="idm-topbar">
        <div class="d-flex align-items-center gap-3">
            <a class="idm-brand" href="./">
                <div class="brand-icon" style="width: 32px; height: 32px; font-size: 1rem; border-radius: 6px;">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <span>Download<span class="text-primary-custom">Pro</span></span>
                <span class="idm-version-badge">v1.0 IDM Edition</span>
            </a>
        </div>

        <!-- Action Buttons -->
        <div class="idm-actions-toolbar">
            <button type="button" class="idm-btn-tool btn-add-url" onclick="FlowIDM.openAddUrlModal()">
                <i class="fa-solid fa-plus"></i> Add URL
            </button>
            <button type="button" class="idm-btn-tool btn-resume" onclick="FlowIDM.resumeAll()">
                <i class="fa-solid fa-play"></i> Resume All
            </button>
            <button type="button" class="idm-btn-tool btn-pause" onclick="FlowIDM.pauseAll()">
                <i class="fa-solid fa-pause"></i> Pause All
            </button>
            <button type="button" class="idm-btn-tool btn-stop" onclick="FlowIDM.stopAll()">
                <i class="fa-solid fa-stop"></i> Stop All
            </button>
            <button type="button" class="idm-btn-tool" onclick="FlowIDM.openVideoDownloader()">
                <i class="fa-solid fa-video text-primary-custom"></i> Video Downloader
            </button>
            <button type="button" class="idm-btn-tool" onclick="FlowIDM.openExtractAudioModal()">
                <i class="fa-solid fa-scissors text-info"></i> Extract Audio
            </button>
            <button type="button" class="idm-btn-tool" onclick="FlowIDM.cleanCompleted()">
                <i class="fa-solid fa-trash-can text-muted"></i> Clean Completed
            </button>
            <button type="button" class="idm-btn-tool" onclick="FlowIDM.openSettingsModal()">
                <i class="fa-solid fa-gear text-secondary"></i> Settings
            </button>
        </div>

        <!-- Right: Theme & Account -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light rounded-circle p-2 text-muted border-0 shadow-none" id="themeToggleBtn" title="Toggle Light / Dark mode">
                <i class="fa-solid fa-sun text-warning"></i>
            </button>

            <?php if ($currentUser): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-custom dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user me-1"></i> <?= htmlspecialchars($currentUser['name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><a class="dropdown-item py-2" href="dashboard"><i class="fa-solid fa-gauge-high me-2 text-primary-custom"></i> Dashboard</a></li>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li><a class="dropdown-item py-2 text-danger" href="admin"><i class="fa-solid fa-user-shield me-2"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="FlowApp.logout()"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login" class="btn btn-sm btn-outline-custom">Log In</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Application Layout: Sidebar + Workspace -->
    <div class="idm-main-layout">

        <!-- Left Navigation & Filter Sidebar (Exact Match with Screenshot) -->
        <aside class="idm-sidebar">
            <!-- 1. NAVIGATION -->
            <div>
                <div class="idm-sidebar-section-title">NAVIGATION</div>
                <div class="idm-nav-item active" id="navItemDashboard" onclick="FlowIDM.switchTab('dashboard')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-gauge-high text-primary-custom"></i>
                        <span>Dashboard</span>
                    </div>
                </div>
                <div class="idm-nav-item" id="navItemVideo" onclick="FlowIDM.openAddUrlModal('video')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-video"></i>
                        <span>Video Downloader</span>
                    </div>
                </div>
                <div class="idm-nav-item" id="navItemAudio" onclick="FlowIDM.openAddUrlModal('audio')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-music"></i>
                        <span>Audio Downloader</span>
                    </div>
                </div>
                <div class="idm-nav-item" id="navItemExtraction" onclick="FlowIDM.openExtractAudioModal()">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-repeat"></i>
                        <span>Audio Extraction</span>
                    </div>
                </div>
                <div class="idm-nav-item" id="navItemSettings" onclick="FlowIDM.openSettingsModal()">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-gear"></i>
                        <span>Settings</span>
                    </div>
                </div>
            </div>

            <!-- 2. CATEGORIES -->
            <div>
                <div class="idm-sidebar-section-title">CATEGORIES</div>
                <div class="idm-nav-item active" id="catItemAll" onclick="FlowIDM.filterCategory('all')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-layer-group text-primary-custom"></i>
                        <span>All Downloads</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountAll">0</span>
                </div>
                <div class="idm-nav-item" id="catItemVideos" onclick="FlowIDM.filterCategory('videos')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-film" style="color: #8B5CF6;"></i>
                        <span>Videos</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountVideos">0</span>
                </div>
                <div class="idm-nav-item" id="catItemAudio" onclick="FlowIDM.filterCategory('audio')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-headphones" style="color: #10B981;"></i>
                        <span>Music & Audio</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountAudio">0</span>
                </div>
                <div class="idm-nav-item" id="catItemDocs" onclick="FlowIDM.filterCategory('documents')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-file-lines" style="color: #F59E0B;"></i>
                        <span>Documents</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountDocs">0</span>
                </div>
                <div class="idm-nav-item" id="catItemPrograms" onclick="FlowIDM.filterCategory('programs')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-window-restore" style="color: #3B82F6;"></i>
                        <span>Programs (.exe)</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountPrograms">0</span>
                </div>
                <div class="idm-nav-item" id="catItemCompressed" onclick="FlowIDM.filterCategory('compressed')">
                    <div class="idm-nav-left">
                        <i class="fa-solid fa-file-zipper" style="color: #EF4444;"></i>
                        <span>Compressed</span>
                    </div>
                    <span class="idm-counter-pill" id="catCountCompressed">0</span>
                </div>
            </div>

            <!-- 3. STATUS FILTER -->
            <div>
                <div class="idm-sidebar-section-title">STATUS FILTER</div>
                <div class="idm-nav-item" id="stItemProgress" onclick="FlowIDM.filterStatus('in_progress')">
                    <div class="idm-nav-left">
                        <span class="status-dot dot-progress"></span>
                        <span>In Progress</span>
                    </div>
                    <span class="idm-counter-pill" id="stCountProgress">0</span>
                </div>
                <div class="idm-nav-item" id="stItemCompleted" onclick="FlowIDM.filterStatus('completed')">
                    <div class="idm-nav-left">
                        <span class="status-dot dot-completed"></span>
                        <span>Completed</span>
                    </div>
                    <span class="idm-counter-pill" id="stCountCompleted">0</span>
                </div>
                <div class="idm-nav-item" id="stItemPaused" onclick="FlowIDM.filterStatus('paused')">
                    <div class="idm-nav-left">
                        <span class="status-dot dot-paused"></span>
                        <span>Paused</span>
                    </div>
                    <span class="idm-counter-pill" id="stCountPaused">0</span>
                </div>
                <div class="idm-nav-item" id="stItemFailed" onclick="FlowIDM.filterStatus('failed')">
                    <div class="idm-nav-left">
                        <span class="status-dot dot-failed"></span>
                        <span>Failed</span>
                    </div>
                    <span class="idm-counter-pill" id="stCountFailed">0</span>
                </div>
            </div>
        </aside>

        <!-- Right Workspace (Exact Match with Screenshot) -->
        <main class="idm-workspace">

            <!-- 5 Metric Cards Row -->
            <div class="idm-metrics-row">
                <!-- Total Downloads -->
                <div class="idm-metric-card">
                    <div class="idm-metric-icon-box">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </div>
                    <div class="idm-metric-info">
                        <label>Total Downloads</label>
                        <span id="metricTotal">0</span>
                    </div>
                </div>

                <!-- Completed -->
                <div class="idm-metric-card">
                    <div class="idm-metric-icon-box" style="color: #10B981; background: rgba(16, 185, 129, 0.12);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="idm-metric-info">
                        <label>Completed</label>
                        <span id="metricCompleted">0</span>
                    </div>
                </div>

                <!-- Active / Queue -->
                <div class="idm-metric-card">
                    <div class="idm-metric-icon-box" style="color: #F59E0B; background: rgba(245, 158, 11, 0.12);">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="idm-metric-info">
                        <label>Active / Queue</label>
                        <span id="metricActive">0</span>
                    </div>
                </div>

                <!-- Current Speed -->
                <div class="idm-metric-card">
                    <div class="idm-metric-icon-box" style="color: #8B5CF6; background: rgba(139, 92, 246, 0.12);">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <div class="idm-metric-info">
                        <label>Current Speed</label>
                        <span id="metricSpeed" style="font-size: 1.15rem;">0.0 KB/s</span>
                    </div>
                </div>

                <!-- Total Data -->
                <div class="idm-metric-card">
                    <div class="idm-metric-icon-box" style="color: #3B82F6; background: rgba(59, 130, 246, 0.12);">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div class="idm-metric-info">
                        <label>Total Data</label>
                        <span id="metricTotalData" style="font-size: 1.15rem;">0.0 MB</span>
                    </div>
                </div>
            </div>

            <!-- All Downloads Table Card -->
            <div class="idm-queue-card">
                <div class="idm-queue-header">
                    <div class="idm-queue-title-wrap">
                        <h4 class="idm-queue-title" id="idmViewTitle">All Downloads</h4>
                        <span class="idm-queue-items-badge" id="idmViewBadge">0 items</span>
                    </div>

                    <!-- Search Box -->
                    <div class="idm-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="idm-search-input" id="idmSearchInput" placeholder="Search downloads..." oninput="FlowIDM.handleSearch(this.value)">
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="idm-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="idmMasterCheck" onchange="FlowIDM.toggleSelectAll(this)">
                                </th>
                                <th style="width: 70px;">TYPE</th>
                                <th>FILE NAME</th>
                                <th style="width: 100px;">SIZE</th>
                                <th style="width: 180px;">PROGRESS</th>
                                <th style="width: 120px;">STATUS</th>
                                <th style="width: 120px;">SPEED / ETA</th>
                                <th style="width: 130px; text-align: right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="idmTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading download queue...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==========================================================================
     MODALS
     ========================================================================== -->

<!-- 1. Add URL Modal -->
<div class="modal fade" id="addUrlModal" tabindex="-1" aria-labelledby="addUrlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content idm-dialog">
            <div class="idm-header">
                <h5><i class="fa-solid fa-plus-circle text-primary-custom me-2"></i>Add New Download</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="idm-body">
                <div class="input-container mb-3">
                    <i class="fa-solid fa-link input-icon"></i>
                    <input type="text" class="url-input" id="modalUrlInput" placeholder="Paste video, audio, or playlist URL (YouTube, Vimeo, SoundCloud, Direct MP4)...">
                    <button type="button" class="paste-btn" onclick="FlowDownloader.pasteFromClipboard('modalUrlInput')">
                        <i class="fa-regular fa-clipboard"></i> Paste
                    </button>
                    <button type="button" class="btn btn-primary-custom action-btn" id="modalAnalyzeBtn" onclick="FlowIDM.analyzeFromModal()">
                        <i class="fa-solid fa-bolt"></i> Inspect
                    </button>
                </div>

                <!-- Sources info badges -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="source-pill small"><i class="fa-brands fa-youtube text-danger"></i> YouTube</span>
                    <span class="source-pill small"><i class="fa-brands fa-vimeo-v text-info"></i> Vimeo</span>
                    <span class="source-pill small"><i class="fa-brands fa-soundcloud text-warning"></i> SoundCloud</span>
                    <span class="source-pill small"><i class="fa-brands fa-tiktok text-dark"></i> TikTok</span>
                    <span class="source-pill small"><i class="fa-solid fa-file-video text-success"></i> Direct MP4 / WebM</span>
                </div>

                <!-- Inspected Media Details Panel -->
                <div class="border rounded-3 p-3 bg-light d-none" id="modalInspectorResult">
                    <div class="d-flex gap-3 mb-3">
                        <img src="" alt="Thumbnail" id="modalThumb" class="rounded" style="width: 140px; height: 80px; object-fit: cover;">
                        <div>
                            <h6 class="fw-bold mb-1" id="modalMediaTitle">Title</h6>
                            <div class="text-muted small mb-1" id="modalMediaMeta">Duration: 00:00</div>
                            <div class="badge bg-soft-mint text-primary-custom" id="modalExtractor">Direct Stream</div>
                        </div>
                    </div>

                    <!-- Quality / Format Select -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Quality / Resolution</label>
                            <select class="form-select form-select-sm" id="modalQualitySelect">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Target Folder</label>
                            <select class="form-select form-select-sm" id="modalFolderSelect">
                                <option value="">Default (General)</option>
                            </select>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary-custom w-100 py-2" id="modalStartDlBtn" onclick="FlowIDM.startDownloadFromModal()">
                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> Start Download Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Extract Audio Modal (Video to Audio Converter) -->
<div class="modal fade" id="extractAudioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content idm-dialog">
            <div class="idm-header">
                <h5><i class="fa-solid fa-scissors text-info me-2"></i>Video-to-Audio Transcoder (FFmpeg)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="idm-body">
                <div class="converter-dropzone mb-3" id="converterDropzone" onclick="document.getElementById('videoFileInput').click()">
                    <input type="file" id="videoFileInput" class="d-none" accept="video/*" onchange="FlowConverter.handleFileSelect(this.files)">
                    <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                    <h5 class="fw-bold mb-1">Drag and drop video file here</h5>
                    <p class="text-muted small mb-0">Supports MP4, WebM, MKV, MOV, AVI up to 500 MB</p>
                </div>

                <div class="d-none" id="converterOptionsPanel">
                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-video text-primary-custom"></i>
                            <div>
                                <div class="fw-bold small" id="convFilename">video.mp4</div>
                                <small class="text-muted" id="convFilesize">0 MB</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="FlowConverter.reset()"><i class="fa-solid fa-trash"></i></button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Audio Format</label>
                            <select class="form-select form-select-sm" id="convTargetFormat">
                                <option value="mp3" selected>MP3 (.mp3)</option>
                                <option value="wav">WAV (.wav)</option>
                                <option value="m4a">M4A (.m4a)</option>
                                <option value="flac">FLAC (.flac)</option>
                                <option value="ogg">OGG (.ogg)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Bitrate</label>
                            <select class="form-select form-select-sm" id="convTargetBitrate">
                                <option value="320k" selected>320 kbps (High Fidelity)</option>
                                <option value="256k">256 kbps (Studio)</option>
                                <option value="192k">192 kbps (Standard)</option>
                                <option value="128k">128 kbps (Compact)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-none mb-3" id="convProgressWrap">
                        <div class="d-flex justify-content-between small fw-bold mb-1">
                            <span id="convStatusText">Transcoding with FFmpeg...</span>
                            <span id="convProgressPct">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="convProgressBar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="d-none text-center p-3 bg-soft-mint rounded-3 mb-3" id="convResultWrap">
                        <i class="fa-solid fa-circle-check text-primary-custom fs-2 mb-2"></i>
                        <h6 class="fw-bold mb-2">Transcoding Completed!</h6>
                        <a href="#" class="btn btn-sm btn-primary-custom" id="convDownloadLink" download>
                            <i class="fa-solid fa-download me-1"></i> Save Converted Audio
                        </a>
                    </div>

                    <button type="button" class="btn btn-primary-custom w-100" id="startConversionBtn" onclick="FlowConverter.startTranscode()">
                        <i class="fa-solid fa-gears me-1"></i> Convert to Audio
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content idm-dialog">
            <div class="idm-header">
                <h5><i class="fa-solid fa-gear text-secondary me-2"></i>Application Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="idm-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Preferred Quality</label>
                    <select class="form-select" id="prefQualityInput">
                        <option value="2160p">2160p (4K Ultra HD)</option>
                        <option value="1080p" selected>1080p (Full HD)</option>
                        <option value="720p">720p (HD)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Concurrent Segment Streams</label>
                    <select class="form-select" id="prefSegmentsInput">
                        <option value="8" selected>8 Streams (IDM Optimized)</option>
                        <option value="4">4 Streams</option>
                        <option value="2">2 Streams</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Local Target Directory</label>
                    <button type="button" class="btn btn-outline-custom w-100" onclick="FlowDashboard.selectLocalFolder()">
                        <i class="fa-solid fa-folder-open me-1"></i> Pick Local Destination Folder
                    </button>
                </div>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> Server retention automatically purges temporary cache files after 48 hours.
                </div>
                <button type="button" class="btn btn-primary-custom w-100" data-bs-dismiss="modal">
                    Save Preferences
                </button>
            </div>
        </div>
    </div>
</div>

<!-- IDM-Style Download Progress Modal (Segment Visualizer) -->
<div class="modal fade" id="idmDownloadModal" tabindex="-1" aria-labelledby="idmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content idm-dialog">
            <div class="idm-header">
                <h5 id="idmModalLabel">
                    <i class="fa-solid fa-cloud-arrow-down text-secondary-custom"></i>
                    <span id="idmModalTitle">FlowFetch Download Engine</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="idmModalCloseBtn"></button>
            </div>
            <div class="idm-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-bold text-truncate" style="max-width: 320px;" id="idmMediaFilename">Initializing stream...</div>
                    <span class="badge bg-soft-mint text-primary-custom px-2 py-1 fw-bold" id="idmProgressPercent">0%</span>
                </div>

                <div class="idm-main-progress">
                    <div class="idm-main-progress-bar h-100" id="idmMainProgressBar" style="width: 0%;"></div>
                </div>

                <!-- IDM Iconic 8-Segment Visualization -->
                <div class="idm-segments-container">
                    <div class="idm-segments-title">
                        <span><i class="fa-solid fa-network-wired me-1"></i> IDM Multi-Threaded Segments</span>
                        <span id="idmSegmentsActive">8 Streams Active</span>
                    </div>
                    <div class="idm-segments-grid" id="idmSegmentsGrid">
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                        <div class="idm-segment active"></div>
                    </div>
                </div>

                <!-- Live Metrics Grid -->
                <div class="idm-stats-grid mb-3">
                    <div class="idm-stat-box">
                        <label>Transfer Speed</label>
                        <span id="idmSpeedVal">0 KB/s</span>
                    </div>
                    <div class="idm-stat-box">
                        <label>Estimated Time (ETA)</label>
                        <span id="idmEtaVal">--:--</span>
                    </div>
                    <div class="idm-stat-box">
                        <label>File Transferred</label>
                        <span id="idmBytesVal">0 MB / 0 MB</span>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="idmPauseResumeBtn" onclick="FlowDownloader.togglePause()">
                            <i class="fa-solid fa-pause me-1"></i> Pause
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="idmCancelBtn" onclick="FlowDownloader.cancelJob()">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </button>
                    </div>
                    <a href="#" class="btn btn-primary-custom d-none" id="idmSaveFileBtn" download>
                        <i class="fa-solid fa-download me-1"></i> Save File
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Notification Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="liveToast" class="toast align-items-center text-white bg-dark border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Notification message</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- App Scripts -->
<script src="public/js/app.js"></script>
<script src="public/js/downloader.js"></script>
<script src="public/js/playlist.js"></script>
<script src="public/js/converter.js"></script>
<script src="public/js/dashboard.js"></script>

<!-- Specialized IDM Desktop Engine UI Controller -->
<script>
window.FlowIDM = {
    currentCategory: 'all',
    currentStatus: null,
    searchQuery: '',
    inspectedMedia: null,
    refreshInterval: null,

    init() {
        this.loadQueue();
        if (this.refreshInterval) clearInterval(this.refreshInterval);
        this.refreshInterval = setInterval(() => this.loadQueue(true), 3000);
    },

    async loadQueue(silent = false) {
        let url = `api/download/queue?category=${this.currentCategory}`;
        if (this.currentStatus) url += `&status=${this.currentStatus}`;
        if (this.searchQuery) url += `&q=${encodeURIComponent(this.searchQuery)}`;

        const res = await FlowApp.request(url);
        if (!res.success) return;

        // Update 5 Metrics
        document.getElementById('metricTotal').innerText = res.counts.total;
        document.getElementById('metricCompleted').innerText = res.counts.completed;
        document.getElementById('metricActive').innerText = res.counts.active;
        document.getElementById('metricSpeed').innerText = res.counts.current_speed || '0.0 KB/s';
        document.getElementById('metricTotalData').innerText = res.counts.total_human || '0.0 MB';

        // Update Sidebar Category Counts
        document.getElementById('catCountAll').innerText = res.counts.total;
        document.getElementById('catCountVideos').innerText = res.counts.videos;
        document.getElementById('catCountAudio').innerText = res.counts.audio;
        document.getElementById('catCountDocs').innerText = res.counts.documents;
        document.getElementById('catCountPrograms').innerText = res.counts.programs;
        document.getElementById('catCountCompressed').innerText = res.counts.compressed;

        // Update Sidebar Status Counts
        document.getElementById('stCountProgress').innerText = res.counts.active;
        document.getElementById('stCountCompleted').innerText = res.counts.completed;
        document.getElementById('stCountPaused').innerText = res.counts.paused;
        document.getElementById('stCountFailed').innerText = res.counts.failed;

        // Update Queue Header
        document.getElementById('idmViewBadge').innerText = `${res.items.length} items`;

        // Render Table Rows
        const tbody = document.getElementById('idmTableBody');
        if (!tbody) return;

        if (res.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-inbox display-6 d-block mb-2 text-muted"></i>
                        No downloads found in this view. Click <strong>+ Add URL</strong> to start downloading!
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        res.items.forEach(item => {
            const tr = document.createElement('tr');

            // Icon by category
            let typeIcon = 'fa-film';
            let typeClass = 'type-video';
            if (item.category === 'audio') { typeIcon = 'fa-headphones'; typeClass = 'type-audio'; }
            else if (item.category === 'documents') { typeIcon = 'fa-file-lines'; typeClass = 'type-doc'; }
            else if (item.category === 'programs') { typeIcon = 'fa-window-restore'; typeClass = 'type-prog'; }
            else if (item.category === 'compressed') { typeIcon = 'fa-file-zipper'; typeClass = 'type-comp'; }

            // Status badge class
            let stBadgeClass = 'badge-st-completed';
            if (item.status_raw === 'in_progress') stBadgeClass = 'badge-st-progress';
            else if (item.status_raw === 'paused') stBadgeClass = 'badge-st-paused';
            else if (item.status_raw === 'failed') stBadgeClass = 'badge-st-failed';

            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input idm-item-check" value="${item.id}">
                </td>
                <td>
                    <div class="idm-type-icon ${typeClass}">
                        <i class="fa-solid ${typeIcon}"></i>
                    </div>
                </td>
                <td>
                    <div class="idm-file-title text-truncate" style="max-width: 320px;" title="${item.file_name}">${item.file_name}</div>
                    <div class="idm-file-sub">${item.category_label}</div>
                </td>
                <td class="fw-bold">${item.size_human}</td>
                <td>
                    <div class="idm-tbl-progress-wrap">
                        <div class="idm-tbl-progress">
                            <div class="idm-tbl-progress-bar" style="width: ${item.progress}%"></div>
                        </div>
                        <div class="idm-tbl-progress-text">
                            <span>${item.progress}%</span>
                            <span>${item.size_human}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="idm-badge-status ${stBadgeClass}">${item.status}</span>
                </td>
                <td class="text-muted small">${item.speed !== '-' ? item.speed : '-'}</td>
                <td style="text-align: right;">
                    <div class="idm-tbl-actions">
                        ${item.status_raw === 'completed' 
                            ? `<a href="${item.download_url}" class="idm-btn-action" title="Save File Locally" download><i class="fa-solid fa-arrow-up-right-from-square"></i></a>` 
                            : `<button type="button" class="idm-btn-action" title="Inspect Progress" onclick="FlowDownloader.openIdmModal('${item.job_uuid}', '${item.media_title}', '${item.format.toLowerCase()}')"><i class="fa-solid fa-eye"></i></button>`}
                        <button type="button" class="idm-btn-action" title="Folder: ${item.folder_name}" onclick="FlowApp.notify('Saved in: ${item.folder_name}', 'info')"><i class="fa-solid fa-folder"></i></button>
                        <button type="button" class="idm-btn-action btn-del" title="Delete" onclick="FlowIDM.deleteItem(${item.id})"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    filterCategory(cat) {
        this.currentCategory = cat;
        document.querySelectorAll('.idm-sidebar .idm-nav-item').forEach(el => el.classList.remove('active'));
        const activeNav = document.getElementById('catItem' + cat.charAt(0).toUpperCase() + cat.slice(1)) || document.getElementById('catItemAll');
        if (activeNav) activeNav.classList.add('active');

        const titleMap = {
            all: 'All Downloads',
            videos: 'Videos',
            audio: 'Music & Audio',
            documents: 'Documents',
            programs: 'Programs (.exe)',
            compressed: 'Compressed'
        };
        document.getElementById('idmViewTitle').innerText = titleMap[cat] || 'Downloads';
        this.loadQueue();
    },

    filterStatus(st) {
        this.currentStatus = st;
        document.querySelectorAll('.idm-sidebar .idm-nav-item').forEach(el => el.classList.remove('active'));
        const map = { in_progress: 'stItemProgress', completed: 'stItemCompleted', paused: 'stItemPaused', failed: 'stItemFailed' };
        if (map[st]) document.getElementById(map[st]).classList.add('active');

        document.getElementById('idmViewTitle').innerText = 'Status: ' + st.replace('_', ' ').toUpperCase();
        this.loadQueue();
    },

    handleSearch(val) {
        this.searchQuery = val.trim();
        this.loadQueue();
    },

    toggleSelectAll(master) {
        document.querySelectorAll('.idm-item-check').forEach(cb => cb.checked = master.checked);
    },

    async deleteItem(jobId) {
        if (!confirm('Remove this download?')) return;
        const res = await FlowApp.request('api/download/delete', {
            method: 'POST',
            body: JSON.stringify({ job_id: jobId })
        });
        if (res.success) {
            FlowApp.notify('Download removed.', 'info');
            this.loadQueue();
        }
    },

    async resumeAll() {
        const res = await FlowApp.request('api/download/bulk-control', {
            method: 'POST',
            body: JSON.stringify({ action: 'resume_all' })
        });
        FlowApp.notify(res.message || 'All downloads resumed.', 'success');
        this.loadQueue();
    },

    async pauseAll() {
        const res = await FlowApp.request('api/download/bulk-control', {
            method: 'POST',
            body: JSON.stringify({ action: 'pause_all' })
        });
        FlowApp.notify(res.message || 'All active downloads paused.', 'warning');
        this.loadQueue();
    },

    async stopAll() {
        if (!confirm('Stop all active downloads?')) return;
        const res = await FlowApp.request('api/download/bulk-control', {
            method: 'POST',
            body: JSON.stringify({ action: 'stop_all' })
        });
        FlowApp.notify(res.message || 'All downloads stopped.', 'danger');
        this.loadQueue();
    },

    async cleanCompleted() {
        const res = await FlowApp.request('api/download/bulk-control', {
            method: 'POST',
            body: JSON.stringify({ action: 'clean_completed' })
        });
        FlowApp.notify(res.message || 'Completed downloads cleaned.', 'info');
        this.loadQueue();
    },

    openAddUrlModal(typeHint = 'video') {
        const modal = new bootstrap.Modal(document.getElementById('addUrlModal'));
        modal.show();
        setTimeout(() => document.getElementById('modalUrlInput').focus(), 300);
    },

    openVideoDownloader() {
        this.openAddUrlModal('video');
    },

    openExtractAudioModal() {
        const modal = new bootstrap.Modal(document.getElementById('extractAudioModal'));
        modal.show();
    },

    openSettingsModal() {
        const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
        modal.show();
    },

    async analyzeFromModal() {
        const url = document.getElementById('modalUrlInput').value.trim();
        if (!url) {
            FlowApp.notify('Please enter or paste a URL.', 'warning');
            return;
        }

        const btn = document.getElementById('modalAnalyzeBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Inspecting...';

        const res = await FlowApp.request('api/media/inspect', {
            method: 'POST',
            body: JSON.stringify({ url: url })
        });

        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Inspect';

        if (!res.success) {
            FlowApp.notify(res.error || 'Inspection failed.', 'danger');
            return;
        }

        this.inspectedMedia = res.media;
        const panel = document.getElementById('modalInspectorResult');
        document.getElementById('modalMediaTitle').innerText = res.media.title || 'Media File';
        document.getElementById('modalMediaMeta').innerText = `Duration: ${res.media.duration || '00:00'} • Source: ${res.media.uploader || 'Web'}`;
        document.getElementById('modalThumb').src = res.media.thumbnail || 'public/assets/img/direct-video.svg';
        document.getElementById('modalExtractor').innerText = res.media.extractor || 'Direct Stream';

        const qualSelect = document.getElementById('modalQualitySelect');
        qualSelect.innerHTML = '';

        if (res.media.video_formats && res.media.video_formats.length > 0) {
            res.media.video_formats.forEach(vf => {
                qualSelect.innerHTML += `<option value="${vf.quality}|${vf.ext || 'mp4'}|video">${vf.label || vf.quality} (${vf.filesize_human || 'Direct'})</option>`;
            });
        }

        if (res.media.audio_formats && res.media.audio_formats.length > 0) {
            res.media.audio_formats.forEach(af => {
                qualSelect.innerHTML += `<option value="${af.bitrate}|${af.format}|audio">${af.label} (${af.filesize_human || 'Audio'})</option>`;
            });
        }

        panel.classList.remove('d-none');
    },

    async startDownloadFromModal() {
        if (!this.inspectedMedia) return;

        const qualVal = document.getElementById('modalQualitySelect').value;
        const [quality, format, type] = qualVal.split('|');
        const folderId = document.getElementById('modalFolderSelect').value || null;

        const res = await FlowApp.request('api/download/start', {
            method: 'POST',
            body: JSON.stringify({
                url: this.inspectedMedia.source_url,
                type: type,
                quality: quality,
                format: format,
                folder_id: folderId
            })
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to start download.', 'danger');
            return;
        }

        const modalEl = document.getElementById('addUrlModal');
        bootstrap.Modal.getInstance(modalEl).hide();

        FlowDownloader.openIdmModal(res.job_uuid, this.inspectedMedia.title, format);
        this.loadQueue();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FlowIDM.init();
});
</script>
</body>
</html>
