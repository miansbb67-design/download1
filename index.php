<?php
require_once __DIR__ . '/app/Core/Database.php';
use App\Core\Database;

// Trigger database init and seed
Database::getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DownloadPro v1.0 [IDM Edition]</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body, * { font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif; }
  </style>
</head>
<body>

  <!-- App Header & Titlebar -->
  <header class="app-header">
    <div class="brand-section">
      <svg class="brand-logo" fill="currentColor" viewBox="0 0 24 24">
        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/>
      </svg>
      <span class="brand-title">DownloadPro</span>
      <span class="brand-badge">v1.0 [IDM Edition]</span>
    </div>
    <div class="window-controls">
      <button class="win-btn" title="Minimize">&#9472;</button>
      <button class="win-btn" title="Maximize">&#9633;</button>
      <button class="win-btn close" title="Close">&#10005;</button>
    </div>
  </header>

  <!-- Action Toolbar -->
  <div class="action-toolbar">
    <button class="btn-tool btn-primary-cyan" id="btnAddUrl">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Add URL
    </button>
    <button class="btn-tool btn-emerald" id="btnResumeAll">
      <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
      Resume All
    </button>
    <button class="btn-tool btn-amber" id="btnPauseAll">
      <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
      Pause All
    </button>
    <button class="btn-tool btn-crimson" id="btnStopAll">
      <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><rect x="5" y="5" width="14" height="14" rx="2"></rect></svg>
      Stop All
    </button>
    <button class="btn-tool btn-dark" id="btnVideoDownloaderTop">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
      Video Downloader
    </button>
    <button class="btn-tool btn-dark" id="btnExtractAudioTop">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M10 12l4 4m0-4l-4 4"></path></svg>
      Extract Audio
    </button>
    <button class="btn-tool btn-dark" id="btnCleanCompleted">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
      Clean Completed
    </button>
    <button class="btn-tool btn-dark" id="btnSettingsTop">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
      Settings
    </button>
  </div>

  <!-- App Body Layout -->
  <div class="app-container">
    
    <!-- Sidebar -->
    <aside class="app-sidebar">
      
      <!-- NAVIGATION -->
      <div>
        <div class="sidebar-section-title">Navigation</div>
        <ul class="sidebar-nav-list">
          <li class="nav-item active" data-nav="dashboard">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
              <span>Dashboard</span>
            </div>
          </li>
          <li class="nav-item" data-nav="video">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
              <span>Video Downloader</span>
            </div>
          </li>
          <li class="nav-item" data-nav="audio">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
              <span>Audio Downloader</span>
            </div>
          </li>
          <li class="nav-item" data-nav="extract">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              <span>Audio Extraction</span>
            </div>
          </li>
          <li class="nav-item" data-nav="settings">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              <span>Settings</span>
            </div>
          </li>
        </ul>
      </div>

      <!-- CATEGORIES -->
      <div>
        <div class="sidebar-section-title">Categories</div>
        <ul class="sidebar-nav-list">
          <li class="nav-item active" data-category="All Downloads">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
              <span>All Downloads</span>
            </div>
            <span class="badge-count" id="countAll">10</span>
          </li>
          <li class="nav-item" data-category="Videos">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
              <span>Videos</span>
            </div>
            <span class="badge-count" id="countVideos">6</span>
          </li>
          <li class="nav-item" data-category="Music & Audio">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
              <span>Music & Audio</span>
            </div>
            <span class="badge-count" id="countMusic">4</span>
          </li>
          <li class="nav-item" data-category="Documents">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              <span>Documents</span>
            </div>
            <span class="badge-count" id="countDocs">0</span>
          </li>
          <li class="nav-item" data-category="Programs (.exe)">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><line x1="8" y1="12" x2="16" y2="12"></line></svg>
              <span>Programs (.exe)</span>
            </div>
            <span class="badge-count" id="countPrograms">0</span>
          </li>
          <li class="nav-item" data-category="Compressed">
            <div class="nav-left">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"></polyline><line x1="1" y1="3" x2="23" y2="3"></line></svg>
              <span>Compressed</span>
            </div>
            <span class="badge-count" id="countCompressed">0</span>
          </li>
        </ul>
      </div>

      <!-- STATUS FILTER -->
      <div>
        <div class="sidebar-section-title">Status Filter</div>
        <ul class="sidebar-nav-list">
          <li class="nav-item" data-status="In Progress">
            <div class="nav-left">
              <span class="status-dot dot-cyan"></span>
              <span>In Progress</span>
            </div>
            <span class="badge-count" id="countInProgress">0</span>
          </li>
          <li class="nav-item" data-status="Completed">
            <div class="nav-left">
              <span class="status-dot dot-green"></span>
              <span>Completed</span>
            </div>
            <span class="badge-count" id="countStatusCompleted">10</span>
          </li>
          <li class="nav-item" data-status="Paused">
            <div class="nav-left">
              <span class="status-dot dot-amber"></span>
              <span>Paused</span>
            </div>
            <span class="badge-count" id="countStatusPaused">0</span>
          </li>
          <li class="nav-item" data-status="Failed">
            <div class="nav-left">
              <span class="status-dot dot-red"></span>
              <span>Failed</span>
            </div>
            <span class="badge-count" id="countStatusFailed">0</span>
          </li>
        </ul>
      </div>

    </aside>

    <!-- Main Workspace Content -->
    <main class="app-content">

      <!-- Metrics Row (5 Cards) -->
      <section class="metrics-grid">
        <div class="metric-card">
          <div class="metric-icon-wrap icon-blue">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
          </div>
          <div class="metric-info">
            <span class="metric-label">Total Downloads</span>
            <span class="metric-value" id="metricTotal">10</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon-wrap icon-green">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          </div>
          <div class="metric-info">
            <span class="metric-label">Completed</span>
            <span class="metric-value" id="metricCompleted">10</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon-wrap icon-amber">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
          </div>
          <div class="metric-info">
            <span class="metric-label">Active / Queue</span>
            <span class="metric-value" id="metricActive">0</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon-wrap icon-purple">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
          </div>
          <div class="metric-info">
            <span class="metric-label">Current Speed</span>
            <span class="metric-value" id="metricSpeed">0.0 KB/s</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon-wrap icon-teal">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>
          </div>
          <div class="metric-info">
            <span class="metric-label">Total Data</span>
            <span class="metric-value" id="metricData">170.1 MB</span>
          </div>
        </div>
      </section>

      <!-- All Downloads Table Container -->
      <section class="table-card">
        <div class="table-header-bar">
          <div class="table-title-wrap">
            <span class="table-title" id="tableTitle">All Downloads</span>
            <span class="table-count-badge" id="tableCountBadge">10 items</span>
          </div>
          <div class="search-box">
            <svg class="search-icon" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="searchInput" placeholder="Search downloads...">
          </div>
        </div>

        <div class="downloads-table-wrapper">
          <table class="downloads-table">
            <thead>
              <tr>
                <th style="width: 38px;">
                  <input type="checkbox" id="selectAllCheckbox" style="cursor: pointer; accent-color: var(--primary-cyan);">
                </th>
                <th style="width: 44px;">TYPE</th>
                <th>FILE NAME</th>
                <th>SIZE</th>
                <th>PROGRESS</th>
                <th>STATUS</th>
                <th>SPEED / ETA</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="downloadsTableBody">
              <!-- Rendered dynamically by app.js -->
            </tbody>
          </table>
        </div>
      </section>

    </main>
  </div>

  <!-- Modal: Add Download URL -->
  <div class="modal-overlay" id="modalAddUrl">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add Download Link</h3>
        <button class="modal-close" data-close-modal>&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Enter Video, Audio, or File URL</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" class="form-input" id="inputDownloadUrl" placeholder="https://..." style="flex: 1;">
            <button class="btn-tool btn-dark" id="btnPasteUrl" style="white-space: nowrap;">Paste</button>
            <button class="btn-tool btn-primary-cyan" id="btnAnalyzeUrl" style="white-space: nowrap;">Analyze</button>
          </div>
          <div id="probeStatus" style="font-size: 11.5px; color: var(--primary-cyan); margin-top: 4px; display: none;"></div>
        </div>

        <!-- Video/Audio Inspection Preview Box -->
        <div class="preview-box" id="mediaPreviewWrap" style="display: none;">
          <img src="" class="preview-thumb" id="previewThumb" alt="Media Preview">
          <div class="preview-info">
            <div>
              <div id="previewTitle" style="font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px;"></div>
              <div id="previewMeta" style="font-size: 11px; color: var(--text-muted);"></div>
            </div>
            <div>
              <label class="form-label" style="margin-bottom: 4px; display: block;">Select Quality / Format</label>
              <div class="format-pill-group" id="formatContainer"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Save As Filename</label>
          <input type="text" class="form-input" id="inputCustomFilename" placeholder="media_filename.mp4">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-tool btn-dark" data-close-modal>Cancel</button>
        <button class="btn-tool btn-primary-cyan" id="btnStartDownloadConfirm">Start Download</button>
      </div>
    </div>
  </div>

  <!-- Modal: Audio Extraction -->
  <div class="modal-overlay" id="modalExtractAudio">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Extract Audio from Video (FFmpeg)</h3>
        <button class="modal-close" data-close-modal>&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Local Video File Path</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" class="form-input" id="inputExtractVideoFile" placeholder="C:\Users\...\video.mp4" style="flex: 1;">
            <button class="btn-tool btn-dark" id="btnBrowseVideo">Browse...</button>
          </div>
          <span style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Enter full path or browse for any local MP4, MKV, WebM, AVI or MOV file.</span>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Target Format</label>
            <select class="form-input" id="selectExtractFormat">
              <option value="mp3" selected>MP3 (Universal Audio)</option>
              <option value="m4a">M4A (Apple AAC)</option>
              <option value="wav">WAV (Lossless Uncompressed)</option>
              <option value="flac">FLAC (Lossless Compressed)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Audio Bitrate</label>
            <select class="form-input" id="selectExtractBitrate">
              <option value="320k" selected>320 kbps (Ultra Studio Quality)</option>
              <option value="256k">256 kbps (High Quality)</option>
              <option value="192k">192 kbps (Medium)</option>
              <option value="128k">128 kbps (Standard)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-tool btn-dark" data-close-modal>Cancel</button>
        <button class="btn-tool btn-emerald" id="btnRunExtractAudio">Extract Audio Now</button>
      </div>
    </div>
  </div>

  <!-- Modal: Settings -->
  <div class="modal-overlay" id="modalSettings">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">DownloadPro Settings</h3>
        <button class="modal-close" data-close-modal>&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Default Download Directory</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" class="form-input" id="inputDownloadFolder" placeholder="storage/downloads" style="flex: 1;">
            <button class="btn-tool btn-dark" id="btnBrowseFolder">Browse...</button>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Max Concurrent Downloads</label>
            <select class="form-input" id="selectMaxConcurrent">
              <option value="1">1 Download</option>
              <option value="2">2 Downloads</option>
              <option value="3" selected>3 Simultaneous (Recommended)</option>
              <option value="5">5 Simultaneous</option>
              <option value="8">8 Simultaneous</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Speed Limiter</label>
            <select class="form-input" id="selectSpeedLimit">
              <option value="unlimited" selected>Unlimited (Maximum Bandwidth)</option>
              <option value="1024">1 MB/s</option>
              <option value="2048">2 MB/s</option>
              <option value="5120">5 MB/s</option>
              <option value="10240">10 MB/s</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-tool btn-dark" data-close-modal>Cancel</button>
        <button class="btn-tool btn-primary-cyan" id="btnSaveSettings">Save Preferences</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div class="toast-container" id="toastContainer"></div>

  <script src="assets/js/app.js"></script>
</body>
</html>
