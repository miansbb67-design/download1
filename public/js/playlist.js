/**
 * FlowFetch Playlist Batch Downloader
 * Multi-video selection, playlist analysis, and batch job scheduling.
 */

window.FlowPlaylist = {
    currentPlaylist: null,

    async analyzePlaylist() {
        const input = document.getElementById('playlistUrlInput');
        const url = input ? input.value.trim() : '';

        if (!url) {
            FlowApp.notify('Please provide a playlist URL.', 'warning');
            return;
        }

        const btn = document.getElementById('analyzePlaylistBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> <span>Inspecting...</span>';

        const res = await FlowApp.request('api/playlist/inspect', {
            method: 'POST',
            body: JSON.stringify({ url: url, limit: 50 })
        });

        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> <span>Fetch Playlist</span>';

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to inspect playlist.', 'danger');
            return;
        }

        this.renderPlaylistData(res.playlist);
    },

    renderPlaylistData(playlist) {
        this.currentPlaylist = playlist;
        const wrap = document.getElementById('playlistTableWrap');
        const tbody = document.getElementById('playlistTableBody');
        if (!wrap || !tbody) return;

        document.getElementById('plTitleDisplay').innerText = playlist.title || 'Playlist Media';
        document.getElementById('plTotalCount').innerText = playlist.total_items || playlist.entries.length;
        document.getElementById('plSelectedCount').innerText = playlist.entries.length;
        document.getElementById('selectAllCheckbox').checked = true;

        tbody.innerHTML = '';
        playlist.entries.forEach(entry => {
            const tr = document.createElement('tr');
            tr.className = 'playlist-item-row';
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input pl-entry-checkbox" value="${entry.id}" checked onchange="FlowPlaylist.updateSelectedCount()">
                </td>
                <td class="text-muted fw-bold">${entry.index}</td>
                <td>
                    <img src="${entry.thumbnail || 'public/assets/img/direct-video.svg'}" class="rounded" style="width: 80px; height: 45px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-truncate" style="max-width: 380px;">${entry.title}</div>
                    <small class="text-muted">${entry.uploader || 'Creator'}</small>
                </td>
                <td><span class="badge bg-light text-dark">${entry.duration}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-custom" onclick="FlowDownloader.startDownload('video', '720p', 'mp4')">
                        <i class="fa-solid fa-download"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        wrap.classList.remove('d-none');
        wrap.scrollIntoView({ behavior: 'smooth' });
    },

    toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.pl-entry-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        this.updateSelectedCount();
    },

    updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.pl-entry-checkbox:checked');
        document.getElementById('plSelectedCount').innerText = checkboxes.length;
    },

    async startBatchDownload() {
        if (!this.currentPlaylist) return;

        const checkboxes = document.querySelectorAll('.pl-entry-checkbox:checked');
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);

        if (selectedIds.length === 0) {
            FlowApp.notify('Please select at least one video to download.', 'warning');
            return;
        }

        const quality = document.getElementById('plBatchQuality').value;
        const createFolder = document.getElementById('plCreateFolderCheck').checked;

        const res = await FlowApp.request('api/playlist/batch', {
            method: 'POST',
            body: JSON.stringify({
                playlist: this.currentPlaylist,
                selected_ids: selectedIds,
                quality: quality,
                format: 'mp4',
                create_folder: createFolder
            })
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to start batch playlist download.', 'danger');
            return;
        }

        FlowApp.notify(`Successfully scheduled ${selectedIds.length} videos into download queue!`, 'success');

        if (res.first_job_uuid) {
            FlowDownloader.openIdmModal(res.first_job_uuid, 'Playlist Stream #1', 'mp4');
        }
    }
};
