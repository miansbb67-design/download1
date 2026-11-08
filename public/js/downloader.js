/**
 * FlowFetch Video & Audio Downloader Engine
 * Real-time format selector, clipboard paste, and IDM-style progress visualization.
 */

window.FlowDownloader = {
    currentMedia: null,
    activeJobUuid: null,
    progressInterval: null,
    isPaused: false,

    async pasteFromClipboard(targetInputId) {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                const input = document.getElementById(targetInputId);
                if (input) {
                    input.value = text.trim();
                    FlowApp.notify('URL pasted from clipboard!', 'info');
                }
            }
        } catch (err) {
            FlowApp.notify('Clipboard access blocked. Please paste using Ctrl+V.', 'warning');
        }
    },

    async analyzeUrl(targetInputId) {
        const input = document.getElementById(targetInputId);
        const url = input ? input.value.trim() : '';

        if (!url) {
            FlowApp.notify('Please enter or paste a valid media URL.', 'warning');
            if (input) input.focus();
            return;
        }

        const btn = document.getElementById('analyzeMediaBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> <span>Analyzing...</span>';
        }

        const res = await FlowApp.request('api/media/inspect', {
            method: 'POST',
            body: JSON.stringify({ url: url })
        });

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-arrow-down-to-line"></i> <span>Download</span>';
        }

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to inspect media URL.', 'danger');
            return;
        }

        if (res.is_playlist) {
            FlowApp.notify('Playlist detected! Switching to Playlist Downloader...', 'info');
            const plInput = document.getElementById('playlistUrlInput');
            if (plInput) plInput.value = url;
            FlowPlaylist.renderPlaylistData(res.media);
            document.getElementById('playlist-downloader').scrollIntoView({ behavior: 'smooth' });
            return;
        }

        this.renderMediaInspector(res.media);
    },

    renderMediaInspector(media) {
        this.currentMedia = media;

        const card = document.getElementById('mediaInspectorCard');
        if (!card) return;

        document.getElementById('inspectorTitle').innerText = media.title || 'Untitled Media';
        document.getElementById('inspectorAuthor').innerText = media.uploader || 'Web Media';
        document.getElementById('inspectorDuration').innerText = media.duration || '00:00';
        document.getElementById('inspectorViews').innerText = Number(media.views || 0).toLocaleString();
        document.getElementById('inspectorExtractor').innerText = media.extractor || 'Direct Stream';

        const thumb = document.getElementById('inspectorThumb');
        thumb.src = media.thumbnail || 'public/assets/img/direct-video.svg';

        // Load available user folders into folder selector
        this.loadFoldersSelector();

        // 1. Populate Video Formats Grid
        const videoGrid = document.getElementById('videoFormatsGrid');
        videoGrid.innerHTML = '';

        if (media.video_formats && media.video_formats.length > 0) {
            media.video_formats.forEach(f => {
                const badgeClass = f.quality.includes('2160') || f.quality.includes('1440') ? 'badge-4k' : (f.quality.includes('1080') ? 'badge-fhd' : 'badge-hd');
                const div = document.createElement('div');
                div.className = 'format-item-card';
                div.onclick = () => this.startDownload('video', f.quality, f.ext || 'mp4');
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge ${badgeClass} format-badge">${f.badge || f.quality}</span>
                        <span class="small text-muted fw-bold">${f.ext ? f.ext.toUpperCase() : 'MP4'}</span>
                    </div>
                    <div class="fw-bold fs-5 mb-1">${f.label || f.quality}</div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="small text-muted"><i class="fa-solid fa-hard-drive me-1"></i>${f.filesize_human || 'Direct Stream'}</span>
                        <button class="btn btn-sm btn-primary-custom py-1 px-3"><i class="fa-solid fa-download"></i></button>
                    </div>
                `;
                videoGrid.appendChild(div);
            });
        } else {
            videoGrid.innerHTML = `<div class="col-12 text-muted p-3">No direct video streams found for this source. Try audio extraction below.</div>`;
        }

        // 2. Populate Audio Formats Grid
        const audioGrid = document.getElementById('audioFormatsGrid');
        audioGrid.innerHTML = '';

        if (media.audio_formats && media.audio_formats.length > 0) {
            media.audio_formats.forEach(a => {
                const div = document.createElement('div');
                div.className = 'format-item-card';
                div.onclick = () => this.startDownload('audio', a.bitrate, a.format);
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge badge-audio format-badge">${a.format.toUpperCase()}</span>
                        <span class="small text-muted fw-bold">${a.bitrate}</span>
                    </div>
                    <div class="fw-bold fs-5 mb-1">${a.label}</div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="small text-muted"><i class="fa-solid fa-music me-1"></i>${a.filesize_human || 'High Quality'}</span>
                        <button class="btn btn-sm btn-primary-custom py-1 px-3"><i class="fa-solid fa-download"></i></button>
                    </div>
                `;
                audioGrid.appendChild(div);
            });
        }

        card.style.display = 'block';
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    async loadFoldersSelector() {
        const select = document.getElementById('inspectorFolderSelect');
        if (!select) return;

        const res = await FlowApp.request('api/folders/list');
        if (res.success && res.folders) {
            select.innerHTML = '<option value="">Default (Unfiled)</option>';
            res.folders.forEach(f => {
                select.innerHTML += `<option value="${f.id}">${f.name}</option>`;
            });
        }
    },

    async startDownload(type, quality, format) {
        if (!this.currentMedia) return;

        const folderSelect = document.getElementById('inspectorFolderSelect');
        const folderId = folderSelect ? folderSelect.value : null;

        const payload = {
            url: this.currentMedia.source_url,
            type: type,
            quality: quality,
            format: format,
            folder_id: folderId
        };

        const res = await FlowApp.request('api/download/start', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to start download.', 'danger');
            return;
        }

        this.openIdmModal(res.job_uuid, this.currentMedia.title, format);
    },

    async quickAudioDownload() {
        const input = document.getElementById('audioOnlyUrl');
        const url = input ? input.value.trim() : '';
        const format = document.getElementById('quickAudioFormat').value;
        const bitrate = document.getElementById('quickAudioBitrate').value;

        if (!url) {
            FlowApp.notify('Please enter a media URL to extract audio.', 'warning');
            return;
        }

        const res = await FlowApp.request('api/download/start', {
            method: 'POST',
            body: JSON.stringify({
                url: url,
                type: 'audio',
                quality: bitrate,
                format: format
            })
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Audio extraction failed.', 'danger');
            return;
        }

        this.openIdmModal(res.job_uuid, 'Audio Track', format);
    },

    openIdmModal(jobUuid, title, format) {
        this.activeJobUuid = jobUuid;
        this.isPaused = false;

        document.getElementById('idmModalTitle').innerText = 'IDM Download Engine';
        document.getElementById('idmMediaFilename').innerText = `${title}.${format}`;
        document.getElementById('idmProgressPercent').innerText = '0%';
        document.getElementById('idmMainProgressBar').style.width = '0%';
        document.getElementById('idmSpeedVal').innerText = 'Starting...';
        document.getElementById('idmEtaVal').innerText = '--:--';
        document.getElementById('idmBytesVal').innerText = '0 MB / 0 MB';

        const saveBtn = document.getElementById('idmSaveFileBtn');
        saveBtn.classList.add('d-none');
        saveBtn.href = '#';

        const pauseBtn = document.getElementById('idmPauseResumeBtn');
        pauseBtn.innerHTML = '<i class="fa-solid fa-pause me-1"></i> Pause';
        pauseBtn.disabled = false;

        const modalEl = document.getElementById('idmDownloadModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Start progress polling loop
        if (this.progressInterval) clearInterval(this.progressInterval);
        this.progressInterval = setInterval(() => this.pollProgress(), 600);
    },

    async pollProgress() {
        if (!this.activeJobUuid) return;

        const res = await FlowApp.request(`api/download/progress/${this.activeJobUuid}`);
        if (!res.success) return;

        const pct = Math.min(res.progress || 0, 100);
        document.getElementById('idmProgressPercent').innerText = `${pct.toFixed(1)}%`;
        document.getElementById('idmMainProgressBar').style.width = `${pct}%`;
        document.getElementById('idmSpeedVal').innerText = res.speed || '0 KB/s';
        document.getElementById('idmEtaVal').innerText = res.eta || '--:--';

        // Format bytes
        const dlMb = (res.downloaded_bytes / (1024 * 1024)).toFixed(1);
        const totMb = (res.total_bytes / (1024 * 1024)).toFixed(1);
        document.getElementById('idmBytesVal').innerText = `${dlMb} MB / ${totMb} MB`;

        // Update IDM segments visualizer
        this.updateSegments(pct, res.status);

        if (res.status === 'completed' || pct >= 100) {
            clearInterval(this.progressInterval);
            document.getElementById('idmProgressPercent').innerText = '100%';
            document.getElementById('idmMainProgressBar').style.width = '100%';
            document.getElementById('idmSpeedVal').innerText = 'Completed';
            document.getElementById('idmEtaVal').innerText = '00:00';

            const saveBtn = document.getElementById('idmSaveFileBtn');
            saveBtn.classList.remove('d-none');
            saveBtn.href = `api/download/direct/${this.activeJobUuid}`;

            const pauseBtn = document.getElementById('idmPauseResumeBtn');
            pauseBtn.disabled = true;

            FlowApp.notify('Download ready! Click Save File to store locally.', 'success');
        } else if (res.status === 'failed') {
            clearInterval(this.progressInterval);
            FlowApp.notify(res.error || 'Download failed.', 'danger');
            document.getElementById('idmSpeedVal').innerText = 'Failed';
        }
    },

    updateSegments(pct, status) {
        const segments = document.querySelectorAll('#idmSegmentsGrid .idm-segment');
        const activeCount = Math.floor((pct / 100) * segments.length);

        segments.forEach((seg, idx) => {
            if (status === 'completed') {
                seg.className = 'idm-segment complete';
            } else if (idx < activeCount) {
                seg.className = 'idm-segment complete';
            } else if (idx === activeCount && status === 'downloading') {
                seg.className = 'idm-segment active';
            } else {
                seg.className = 'idm-segment';
            }
        });
    },

    async togglePause() {
        if (!this.activeJobUuid) return;
        const btn = document.getElementById('idmPauseResumeBtn');

        const action = this.isPaused ? 'resume' : 'pause';
        btn.disabled = true;

        const res = await FlowApp.request('api/download/control', {
            method: 'POST',
            body: JSON.stringify({ job_uuid: this.activeJobUuid, action: action })
        });

        btn.disabled = false;
        if (res.success) {
            this.isPaused = !this.isPaused;
            btn.innerHTML = this.isPaused 
                ? '<i class="fa-solid fa-play me-1"></i> Resume' 
                : '<i class="fa-solid fa-pause me-1"></i> Pause';
            FlowApp.notify(`Download ${action}d.`, 'info');
        }
    },

    async cancelJob() {
        if (!this.activeJobUuid) return;
        if (!confirm('Are you sure you want to cancel this download?')) return;

        clearInterval(this.progressInterval);
        await FlowApp.request('api/download/control', {
            method: 'POST',
            body: JSON.stringify({ job_uuid: this.activeJobUuid, action: 'cancel' })
        });

        FlowApp.notify('Download cancelled.', 'warning');
        const modalEl = document.getElementById('idmDownloadModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
};

