<!-- IDM-Style Download Progress Modal -->
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

                <!-- Primary Progress Bar -->
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

                <!-- Controls -->
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

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="brand-logo text-white mb-3 d-inline-flex" href="./">
                    <div class="brand-icon">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <span>Flow<span class="text-secondary-custom">Fetch</span></span>
                </a>
                <p class="text-muted mb-3" style="font-size: 0.95rem;">
                    Advanced, IDM-inspired high-speed video and audio downloader. Extract 4K streams, download entire playlists, and transcode audio with studio fidelity.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge bg-dark border border-secondary text-secondary-custom px-2 py-1">
                        <i class="fa-solid fa-microchip me-1"></i> FFmpeg 7.1 Engine
                    </span>
                    <span class="badge bg-dark border border-secondary text-secondary-custom px-2 py-1">
                        <i class="fa-solid fa-bolt me-1"></i> yt-dlp 2026
                    </span>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3">Downloaders</h6>
                <a href="#video-downloader" class="footer-link">Video Downloader</a>
                <a href="#playlist-downloader" class="footer-link">Playlist Downloader</a>
                <a href="#audio-downloader" class="footer-link">Audio Downloader</a>
                <a href="#converter-section" class="footer-link">Video to Audio</a>
                <a href="#quality-4k" class="footer-link">4K Ultra HD</a>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3">Platforms</h6>
                <a href="#sources" class="footer-link">YouTube Media</a>
                <a href="#sources" class="footer-link">Vimeo Cloud</a>
                <a href="#sources" class="footer-link">SoundCloud Audio</a>
                <a href="#sources" class="footer-link">TikTok Streams</a>
                <a href="#sources" class="footer-link">Direct MP4/WebM</a>
            </div>

            <div class="col-lg-4">
                <h6 class="text-white fw-bold mb-3">Legal & Compliance</h6>
                <p class="text-muted small">
                    FlowFetch is strictly intended for downloading authorized, royalty-free, public domain, or personal media permitted under platform terms. DRM protection bypass is not supported.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="terms" class="footer-link">Terms & Conditions</a>
                    <a href="privacy" class="footer-link">Privacy Policy</a>
                    <a href="copyright" class="footer-link">Acceptable Use & DMCA</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
                &copy; <?= date('Y') ?> FlowFetch. Built with engineering excellence.
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary-custom"><i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> Media Processing Engine Online</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- App Scripts -->
<script src="public/js/app.js"></script>
<script src="public/js/downloader.js"></script>
<script src="public/js/playlist.js"></script>
<script src="public/js/converter.js"></script>
</body>
</html>
