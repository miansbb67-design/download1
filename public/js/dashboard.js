/**
 * FlowFetch User Dashboard & Folder Management Logic
 */

window.FlowDashboard = {
    activeFolderId: null,

    init() {
        this.loadStats();
        this.loadHistory();
        this.loadFolders();
    },

    async loadStats() {
        const res = await FlowApp.request('api/dashboard/stats');
        if (!res.success) return;

        document.getElementById('dashTotalDownloads').innerText = res.total_downloads;
        document.getElementById('dashActiveDownloads').innerText = res.active_downloads;
        document.getElementById('dashMediaBreakdown').innerText = `${res.video_downloads} / ${res.audio_downloads}`;
        document.getElementById('dashPlaylistsCount').innerText = res.playlist_downloads;

        const sizeMb = (res.total_bytes / (1024 * 1024)).toFixed(1);
        document.getElementById('dashTotalSize').innerText = `${sizeMb} MB Stored`;
    },

    async loadHistory() {
        const tbody = document.getElementById('historyTableBody');
        if (!tbody) return;

        const res = await FlowApp.request('api/dashboard/history');
        if (!res.success) return;

        if (!res.history || res.history.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No downloads found yet. Start your first download from the home page!</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        res.history.forEach(job => {
            const tr = document.createElement('tr');
            const statusBadge = job.status === 'completed' ? 'bg-success' : (job.status === 'failed' ? 'bg-danger' : 'bg-primary');
            const typeIcon = job.media_type === 'audio' ? 'fa-headphones text-info' : 'fa-film text-primary-custom';

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid ${typeIcon}"></i>
                        <span class="fw-bold text-truncate" style="max-width: 280px;">${job.media_title || 'Media File'}</span>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark text-uppercase">${job.media_type}</span></td>
                <td>${job.target_format.toUpperCase()} (${job.target_quality})</td>
                <td>
                    <span class="badge ${statusBadge} text-capitalize">${job.status}</span>
                </td>
                <td class="text-muted small">${new Date(job.created_at).toLocaleDateString()}</td>
                <td class="text-end">
                    ${job.status === 'completed' 
                        ? `<a href="api/download/direct/${job.job_uuid}" class="btn btn-sm btn-outline-custom" download><i class="fa-solid fa-download"></i> Save</a>` 
                        : `<button class="btn btn-sm btn-outline-secondary" onclick="FlowDownloader.openIdmModal('${job.job_uuid}', '${job.media_title || 'Download'}', '${job.target_format}')"><i class="fa-solid fa-eye"></i> Track</button>`}
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    filterHistory(query) {
        const rows = document.querySelectorAll('#historyTableBody tr');
        const q = query.toLowerCase();
        rows.forEach(r => {
            r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    },

    async loadFolders() {
        const container = document.getElementById('foldersListContainer');
        if (!container) return;

        const res = await FlowApp.request('api/folders/list');
        if (!res.success) return;

        container.innerHTML = '';

        // All Files / Unfiled
        const unfiledPill = document.createElement('div');
        unfiledPill.className = `folder-pill ${this.activeFolderId === null ? 'active' : ''}`;
        unfiledPill.onclick = () => this.selectFolder(null, 'All Downloads');
        unfiledPill.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-inbox text-secondary"></i>
                <span class="fw-bold">All Downloads</span>
            </div>
            <span class="badge bg-light text-dark">${res.unfiled.file_count || 0}</span>
        `;
        container.appendChild(unfiledPill);

        // Custom Folders
        res.folders.forEach(f => {
            const pill = document.createElement('div');
            pill.className = `folder-pill ${this.activeFolderId === f.id ? 'active' : ''}`;
            pill.onclick = () => this.selectFolder(f.id, f.name);
            pill.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-${f.icon || 'folder'}" style="color: ${f.color || 'var(--primary)'};"></i>
                    <span class="fw-bold">${f.name}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">${f.file_count || 0}</span>
                    <button class="btn btn-sm p-0 text-muted border-0" onclick="event.stopPropagation(); FlowDashboard.deleteFolder(${f.id})"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;
            container.appendChild(pill);
        });

        // Load files for active folder
        this.loadFolderFiles(this.activeFolderId);
    },

    selectFolder(id, name) {
        this.activeFolderId = id;
        document.getElementById('currentFolderTitle').innerText = name;
        this.loadFolders();
    },

    async loadFolderFiles(folderId) {
        const tbody = document.getElementById('folderFilesTableBody');
        if (!tbody) return;

        const url = folderId ? `api/folders/contents/${folderId}` : 'api/folders/contents/all';
        const res = await FlowApp.request(url);
        if (!res.success) return;

        document.getElementById('currentFolderCount').innerText = `${res.files.length} Files`;

        if (res.files.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No files in this folder.</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        res.files.forEach(f => {
            const tr = document.createElement('tr');
            const sizeMb = (f.file_size / (1024 * 1024)).toFixed(1);
            tr.innerHTML = `
                <td>
                    <div class="fw-bold text-truncate" style="max-width: 280px;">${f.original_filename}</div>
                    <small class="text-muted">${f.folder_name || 'Unfiled'}</small>
                </td>
                <td><span class="badge bg-light text-dark text-uppercase">${f.media_type}</span></td>
                <td>${sizeMb} MB</td>
                <td class="text-muted small">${new Date(f.created_at).toLocaleDateString()}</td>
                <td class="text-end">
                    <a href="api/download/file/${f.id}" class="btn btn-sm btn-outline-custom" download><i class="fa-solid fa-download"></i></a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    openCreateFolderModal() {
        const modal = new bootstrap.Modal(document.getElementById('createFolderModal'));
        modal.show();
    },

    async createFolder() {
        const nameInput = document.getElementById('newFolderName');
        const colorInput = document.getElementById('newFolderColor');

        const name = nameInput.value.trim();
        if (!name) {
            FlowApp.notify('Folder name cannot be empty.', 'warning');
            return;
        }

        const res = await FlowApp.request('api/folders/create', {
            method: 'POST',
            body: JSON.stringify({ name: name, color: colorInput.value })
        });

        if (res.success) {
            FlowApp.notify('Folder created successfully!', 'success');
            nameInput.value = '';
            const modalEl = document.getElementById('createFolderModal');
            bootstrap.Modal.getInstance(modalEl).hide();
            this.loadFolders();
        } else {
            FlowApp.notify(res.error || 'Failed to create folder.', 'danger');
        }
    },

    async deleteFolder(folderId) {
        if (!confirm('Delete this folder? Contained files will be preserved in Unfiled.')) return;

        const res = await FlowApp.request('api/folders/delete', {
            method: 'POST',
            body: JSON.stringify({ folder_id: folderId })
        });

        if (res.success) {
            FlowApp.notify('Folder deleted.', 'info');
            this.activeFolderId = null;
            this.loadFolders();
        }
    },

    async selectLocalFolder() {
        if ('showDirectoryPicker' in window) {
            try {
                const dirHandle = await window.showDirectoryPicker();
                FlowApp.notify(`Target download folder set to: "${dirHandle.name}"`, 'success');
            } catch (err) {
                // Cancelled by user
            }
        } else {
            FlowApp.notify('Browser standard download directory is active. You can customize download locations in your browser settings.', 'info');
        }
    },

    async saveProfile(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        const res = await FlowApp.request('api/auth/profile', {
            method: 'POST',
            body: formData
        });

        if (res.success) {
            FlowApp.notify('Profile preferences saved.', 'success');
        } else {
            FlowApp.notify(res.error || 'Failed to update preferences.', 'danger');
        }
    },

    async changePassword(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        const res = await FlowApp.request('api/auth/password', {
            method: 'POST',
            body: formData
        });

        if (res.success) {
            FlowApp.notify('Password changed successfully.', 'success');
            form.reset();
        } else {
            FlowApp.notify(res.error || 'Failed to update password.', 'danger');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FlowDashboard.init();
});

