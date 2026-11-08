/**
 * FlowFetch Admin Command Center Controller
 */

window.FlowAdmin = {
    settingsData: [],

    init() {
        this.loadStats();
        this.loadUsers();
        this.loadSettings();
        this.loadSources();
        this.loadLogs();
    },

    refreshAll() {
        this.init();
        FlowApp.notify('Admin metrics refreshed.', 'info');
    },

    async loadStats() {
        const res = await FlowApp.request('api/admin/stats');
        if (!res.success) return;

        document.getElementById('admUsersCount').innerText = res.users_total;
        document.getElementById('admActiveUsersCount').innerText = `${res.users_active} Active Accounts`;
        document.getElementById('admTotalJobs').innerText = res.jobs_total;
        document.getElementById('admCompletedJobs').innerText = `${res.jobs_completed} Succeeded`;
        document.getElementById('admStorageUsed').innerText = res.storage_used;
        document.getElementById('admDiskFree').innerText = `Free: ${res.disk_free}`;
        document.getElementById('admConversionsCount').innerText = res.conversions;
    },

    async loadUsers() {
        const tbody = document.getElementById('admUsersTableBody');
        if (!tbody) return;

        const res = await FlowApp.request('api/admin/users');
        if (!res.success) return;

        tbody.innerHTML = '';
        res.users.forEach(u => {
            const tr = document.createElement('tr');
            const statusBadge = u.status === 'active' ? 'bg-success' : 'bg-danger';

            tr.innerHTML = `
                <td class="text-muted fw-bold">#${u.id}</td>
                <td class="fw-bold">${u.name}</td>
                <td>${u.email}</td>
                <td><span class="badge bg-light text-dark text-uppercase">${u.role}</span></td>
                <td>${u.total_downloads} files</td>
                <td><span class="badge ${statusBadge}">${u.status}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm ${u.status === 'active' ? 'btn-outline-danger' : 'btn-outline-success'}" 
                            onclick="FlowAdmin.toggleUser(${u.id}, '${u.status === 'active' ? 'suspended' : 'active'}')">
                        ${u.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    async toggleUser(userId, newStatus) {
        const res = await FlowApp.request('api/admin/users/toggle', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId, status: newStatus })
        });

        if (res.success) {
            FlowApp.notify(`User status set to ${newStatus}.`, 'success');
            this.loadUsers();
        } else {
            FlowApp.notify(res.error || 'Failed to update user status.', 'danger');
        }
    },

    async loadSettings() {
        const container = document.getElementById('admSettingsContainer');
        if (!container) return;

        const res = await FlowApp.request('api/admin/settings');
        if (!res.success) return;

        this.settingsData = res.settings;
        container.innerHTML = '';

        res.settings.forEach(s => {
            const col = document.createElement('div');
            col.className = 'col-md-6';
            col.innerHTML = `
                <label class="form-label small fw-bold text-muted mb-1">${s.description || s.setting_key}</label>
                <div class="input-group">
                    <span class="input-group-text small bg-light font-monospace" style="font-size: 0.75rem;">${s.setting_key}</span>
                    <input type="text" class="form-control adm-setting-input" data-key="${s.setting_key}" value="${s.setting_value}">
                </div>
            `;
            container.appendChild(col);
        });
    },

    async saveSettings() {
        const inputs = document.querySelectorAll('.adm-setting-input');
        const payload = {};
        inputs.forEach(inp => {
            payload[inp.getAttribute('data-key')] = inp.value;
        });

        const res = await FlowApp.request('api/admin/settings/update', {
            method: 'POST',
            body: JSON.stringify({ settings: payload })
        });

        if (res.success) {
            FlowApp.notify('All settings updated successfully!', 'success');
            this.loadSettings();
        } else {
            FlowApp.notify(res.error || 'Failed to save settings.', 'danger');
        }
    },

    async loadSources() {
        const tbody = document.getElementById('admSourcesTableBody');
        if (!tbody) return;

        const res = await FlowApp.request('api/admin/sources');
        if (!res.success) return;

        tbody.innerHTML = '';
        res.sources.forEach(src => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${src.name}</td>
                <td><code>${src.domain}</code></td>
                <td><span class="badge bg-light text-dark">${src.max_resolution}</span></td>
                <td>${src.allow_audio_extract ? '<i class="fa-solid fa-check text-success"></i> Yes' : '<i class="fa-solid fa-xmark text-danger"></i> No'}</td>
                <td>
                    <span class="badge ${src.is_active ? 'bg-success' : 'bg-secondary'}">${src.is_active ? 'Active' : 'Disabled'}</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm ${src.is_active ? 'btn-outline-secondary' : 'btn-outline-success'}" 
                            onclick="FlowAdmin.toggleSource(${src.id}, ${src.is_active ? 0 : 1})">
                        ${src.is_active ? 'Disable' : 'Enable'}
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    async toggleSource(sourceId, activeVal) {
        const res = await FlowApp.request('api/admin/sources/toggle', {
            method: 'POST',
            body: JSON.stringify({ source_id: sourceId, is_active: activeVal })
        });

        if (res.success) {
            FlowApp.notify('Source status updated.', 'info');
            this.loadSources();
        }
    },

    async loadLogs(level = '') {
        const tbody = document.getElementById('admLogsTableBody');
        if (!tbody) return;

        const res = await FlowApp.request(`api/admin/logs${level ? '?level=' + level : ''}`);
        if (!res.success) return;

        tbody.innerHTML = '';
        res.logs.forEach(l => {
            const tr = document.createElement('tr');
            const levelClass = l.level === 'ERROR' ? 'text-danger' : (l.level === 'WARNING' ? 'text-warning' : 'text-success');

            tr.innerHTML = `
                <td class="text-muted">${l.created_at}</td>
                <td class="fw-bold ${levelClass}">${l.level}</td>
                <td>${l.category}</td>
                <td><code>${l.ip_address || '127.0.0.1'}</code></td>
                <td>${l.message}</td>
            `;
            tbody.appendChild(tr);
        });
    },

    async purgeExpiredFiles() {
        if (!confirm('Purge all completed files older than 48 hours to recover disk space?')) return;

        const res = await FlowApp.request('api/admin/clean-storage', {
            method: 'POST',
            body: JSON.stringify({ retention_hours: 48 })
        });

        if (res.success) {
            FlowApp.notify(`Storage purged! ${res.cleaned_files} expired files deleted from disk.`, 'success');
            this.loadStats();
        } else {
            FlowApp.notify(res.error || 'Storage cleanup failed.', 'danger');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FlowAdmin.init();
});

