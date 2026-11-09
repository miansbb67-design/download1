/**
 * DownloadPro - IDM Edition Frontend Engine
 */

const App = {
  currentCategory: 'All Downloads',
  currentStatus: 'All',
  searchQuery: '',
  downloads: [],
  selectedFormat: null,
  probedData: null,
  pollInterval: null,

  init() {
    this.bindEvents();
    this.loadDownloads();
    this.loadSettings();
    this.startPolling();
  },

  bindEvents() {
    // Top Action Bar Buttons
    document.getElementById('btnAddUrl')?.addEventListener('click', () => this.openAddModal());
    document.getElementById('btnResumeAll')?.addEventListener('click', () => this.resumeAll());
    document.getElementById('btnPauseAll')?.addEventListener('click', () => this.pauseAll());
    document.getElementById('btnStopAll')?.addEventListener('click', () => this.stopAll());
    document.getElementById('btnCleanCompleted')?.addEventListener('click', () => this.cleanCompleted());
    document.getElementById('btnExtractAudioTop')?.addEventListener('click', () => this.openAudioExtractModal());
    document.getElementById('btnVideoDownloaderTop')?.addEventListener('click', () => this.openAddModal());
    document.getElementById('btnSettingsTop')?.addEventListener('click', () => this.openSettingsModal());

    // Navigation items
    document.querySelectorAll('[data-nav]').forEach(item => {
      item.addEventListener('click', (e) => {
        const nav = item.getAttribute('data-nav');
        if (nav === 'dashboard') {
          this.switchCategory('All Downloads');
        } else if (nav === 'video') {
          this.openAddModal();
        } else if (nav === 'audio') {
          this.openAddModal();
        } else if (nav === 'extract') {
          this.openAudioExtractModal();
        } else if (nav === 'settings') {
          this.openSettingsModal();
        }
      });
    });

    // Categories in Sidebar
    document.querySelectorAll('[data-category]').forEach(el => {
      el.addEventListener('click', () => {
        const cat = el.getAttribute('data-category');
        this.switchCategory(cat);
      });
    });

    // Status filters in Sidebar
    document.querySelectorAll('[data-status]').forEach(el => {
      el.addEventListener('click', () => {
        const st = el.getAttribute('data-status');
        this.switchStatus(st);
      });
    });

    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchQuery = e.target.value;
        this.renderDownloads();
      });
    }

    // Modal Close Buttons
    document.querySelectorAll('.modal-close, [data-close-modal]').forEach(el => {
      el.addEventListener('click', () => this.closeModals());
    });

    // Paste from clipboard button
    document.getElementById('btnPasteUrl')?.addEventListener('click', async () => {
      try {
        const text = await navigator.clipboard.readText();
        const urlInput = document.getElementById('inputDownloadUrl');
        if (urlInput && text) {
          urlInput.value = text.trim();
          this.probeUrl(text.trim());
        }
      } catch (err) {
        this.showToast('Please paste the URL manually.', 'error');
      }
    });

    // Probe URL button
    document.getElementById('btnAnalyzeUrl')?.addEventListener('click', () => {
      const url = document.getElementById('inputDownloadUrl')?.value?.trim();
      if (url) this.probeUrl(url);
    });

    // Start Download Submit
    document.getElementById('btnStartDownloadConfirm')?.addEventListener('click', () => this.submitDownload());

    // Audio Extract Submit
    document.getElementById('btnRunExtractAudio')?.addEventListener('click', () => this.submitAudioExtract());

    // Settings Submit
    document.getElementById('btnSaveSettings')?.addEventListener('click', () => this.saveSettings());

    // Native desktop file dialogs if running in Electron
    if (window.desktopAPI) {
      const browseFolderBtn = document.getElementById('btnBrowseFolder');
      if (browseFolderBtn) {
        browseFolderBtn.addEventListener('click', async () => {
          const dir = await window.desktopAPI.selectDirectory();
          if (dir) document.getElementById('inputDownloadFolder').value = dir;
        });
      }

      const browseVideoBtn = document.getElementById('btnBrowseVideo');
      if (browseVideoBtn) {
        browseVideoBtn.addEventListener('click', async () => {
          const file = await window.desktopAPI.selectVideoFile();
          if (file) document.getElementById('inputExtractVideoFile').value = file;
        });
      }

      window.desktopAPI.onTriggerAddUrl(() => this.openAddModal());
    }
  },

  async loadDownloads() {
    try {
      const params = new URLSearchParams();
      if (this.currentCategory && this.currentCategory !== 'All Downloads') {
        params.append('category', this.currentCategory);
      }
      if (this.currentStatus && this.currentStatus !== 'All') {
        params.append('status', this.currentStatus);
      }
      if (this.searchQuery) {
        params.append('search', this.searchQuery);
      }

      const res = await fetch(`api/downloads.php?${params.toString()}`);
      const data = await res.json();

      if (data && data.success) {
        this.downloads = data.downloads || [];
        this.updateMetrics(data.metrics);
        this.updateSidebarCounts(data.sidebar);
        this.renderDownloads();
      }
    } catch (e) {
      console.error('Failed to load downloads', e);
    }
  },

  startPolling() {
    if (this.pollInterval) clearInterval(this.pollInterval);
    this.pollInterval = setInterval(() => {
      this.loadDownloads();
    }, 2500);
  },

  updateMetrics(metrics) {
    if (!metrics) return;
    document.getElementById('metricTotal').textContent = metrics.total_downloads;
    document.getElementById('metricCompleted').textContent = metrics.completed;
    document.getElementById('metricActive').textContent = metrics.active_queue;
    document.getElementById('metricSpeed').textContent = metrics.current_speed || '0.0 KB/s';
    document.getElementById('metricData').textContent = metrics.total_data || '0.0 MB';
  },

  updateSidebarCounts(sidebar) {
    if (!sidebar) return;
    const cats = sidebar.categories || {};
    const stats = sidebar.statuses || {};

    document.getElementById('countAll').textContent = cats['All Downloads'] || 0;
    document.getElementById('countVideos').textContent = cats['Videos'] || 0;
    document.getElementById('countMusic').textContent = cats['Music & Audio'] || 0;
    document.getElementById('countDocs').textContent = cats['Documents'] || 0;
    document.getElementById('countPrograms').textContent = cats['Programs (.exe)'] || 0;
    document.getElementById('countCompressed').textContent = cats['Compressed'] || 0;

    document.getElementById('countInProgress').textContent = stats['In Progress'] || 0;
    document.getElementById('countStatusCompleted').textContent = stats['Completed'] || 0;
    document.getElementById('countStatusPaused').textContent = stats['Paused'] || 0;
    document.getElementById('countStatusFailed').textContent = stats['Failed'] || 0;
  },

  switchCategory(cat) {
    this.currentCategory = cat;
    this.currentStatus = 'All';

    document.querySelectorAll('[data-category]').forEach(el => {
      if (el.getAttribute('data-category') === cat) {
        el.classList.add('active');
      } else {
        el.classList.remove('active');
      }
    });

    document.querySelectorAll('[data-status]').forEach(el => el.classList.remove('active'));

    const titleEl = document.getElementById('tableTitle');
    if (titleEl) titleEl.textContent = cat;

    this.loadDownloads();
  },

  switchStatus(status) {
    this.currentStatus = status;
    this.currentCategory = 'All Downloads';

    document.querySelectorAll('[data-status]').forEach(el => {
      if (el.getAttribute('data-status') === status) {
        el.classList.add('active');
      } else {
        el.classList.remove('active');
      }
    });

    document.querySelectorAll('[data-category]').forEach(el => el.classList.remove('active'));

    const titleEl = document.getElementById('tableTitle');
    if (titleEl) titleEl.textContent = `${status} Downloads`;

    this.loadDownloads();
  },

  renderDownloads() {
    const tbody = document.getElementById('downloadsTableBody');
    const countBadge = document.getElementById('tableCountBadge');
    if (!tbody) return;

    let items = this.downloads;
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      items = items.filter(i => (i.title && i.title.toLowerCase().includes(q)) || (i.filename && i.filename.toLowerCase().includes(q)));
    }

    if (countBadge) {
      countBadge.textContent = `${items.length} items`;
    }

    if (items.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align: center; padding: 48px; color: var(--text-muted);">
            <div style="font-size: 14px; font-weight: 500;">No downloads found</div>
            <div style="font-size: 12px; margin-top: 4px;">Click "+ Add URL" above to paste a video, audio, or direct download link.</div>
          </td>
        </tr>
      `;
      return;
    }

    let html = '';
    items.forEach(item => {
      const isVideo = item.category === 'Videos' || item.file_type === 'video';
      const isAudio = item.category === 'Music & Audio' || item.file_type === 'audio';
      const isDoc = item.category === 'Documents';
      const isProg = item.category === 'Programs (.exe)';
      const isComp = item.category === 'Compressed';

      let typeIconHtml = '';
      if (isVideo) {
        typeIconHtml = `<svg class="file-type-icon icon-type-video" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>`;
      } else if (isAudio) {
        typeIconHtml = `<svg class="file-type-icon icon-type-audio" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>`;
      } else if (isDoc) {
        typeIconHtml = `<svg class="file-type-icon icon-type-doc" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`;
      } else if (isProg) {
        typeIconHtml = `<svg class="file-type-icon icon-type-prog" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><line x1="8" y1="12" x2="16" y2="12"></line></svg>`;
      } else {
        typeIconHtml = `<svg class="file-type-icon icon-type-comp" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"></polyline><line x1="1" y1="3" x2="23" y2="3"></line></svg>`;
      }

      // Status Badge
      let statusClass = 'status-completed';
      let statusLabel = 'Completed';
      let isProgressActive = false;

      if (item.status === 'downloading' || item.status === 'pending') {
        statusClass = 'status-downloading';
        statusLabel = item.status === 'pending' ? 'Connecting...' : 'In Progress';
        isProgressActive = true;
      } else if (item.status === 'paused') {
        statusClass = 'status-paused';
        statusLabel = 'Paused';
      } else if (item.status === 'failed') {
        statusClass = 'status-failed';
        statusLabel = 'Failed';
      }

      const sizeFormatted = this.formatBytes(item.file_size || item.downloaded_bytes || 0);
      const downloadedFormatted = this.formatBytes(item.downloaded_bytes || 0);
      const percent = Math.min(100, Math.max(0, parseFloat(item.progress) || 0));

      const subLabel = `${item.category} • ${item.duration && item.duration !== 'N/A' ? item.duration : 'Direct'}`;

      html += `
        <tr data-task-id="${item.task_id}">
          <td style="width: 38px;">
            <input type="checkbox" style="cursor: pointer; accent-color: var(--primary-cyan);">
          </td>
          <td style="width: 44px;">
            ${typeIconHtml}
          </td>
          <td>
            <div class="file-cell">
              <span class="file-name" title="${this.escapeHtml(item.title || item.filename)}">${this.escapeHtml(item.title || item.filename)}</span>
              <span class="file-subtitle">${this.escapeHtml(subLabel)}</span>
            </div>
          </td>
          <td style="white-space: nowrap; font-weight: 500;">
            ${sizeFormatted}
          </td>
          <td class="progress-cell">
            <div class="progress-bar-bg">
              <div class="progress-bar-fill ${isProgressActive ? 'active' : ''}" style="width: ${percent}%;"></div>
            </div>
            <div class="progress-info">
              <span>${percent}%</span>
              <span>${downloadedFormatted}</span>
            </div>
          </td>
          <td>
            <span class="status-pill ${statusClass}">${statusLabel}</span>
          </td>
          <td style="white-space: nowrap; font-size: 11.5px; color: var(--text-muted);">
            ${item.status === 'downloading' ? `${item.speed || ''} • ${item.eta || ''}` : '-'}
          </td>
          <td>
            <div class="action-btn-group">
              ${item.status === 'downloading' ? `
                <button class="action-icon-btn" title="Pause" onclick="App.pauseDownload('${item.task_id}')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                </button>
              ` : item.status === 'paused' ? `
                <button class="action-icon-btn" title="Resume" onclick="App.resumeDownload('${item.task_id}')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </button>
              ` : ''}

              <button class="action-icon-btn" title="Open File" onclick="App.openItem('${item.task_id}', 'open_file')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
              </button>
              <button class="action-icon-btn" title="Open Containing Folder" onclick="App.openItem('${item.task_id}', 'open_folder')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              </button>
              <button class="action-icon-btn delete" title="Delete Download" onclick="App.deleteDownload('${item.task_id}')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  },

  async probeUrl(url) {
    const probeStatus = document.getElementById('probeStatus');
    const previewWrap = document.getElementById('mediaPreviewWrap');
    const formatContainer = document.getElementById('formatContainer');

    if (probeStatus) {
      probeStatus.textContent = 'Probing URL and inspecting stream formats...';
      probeStatus.style.display = 'block';
    }

    try {
      const res = await fetch('api/probe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url })
      });
      const data = await res.json();

      if (data && data.success) {
        this.probedData = data;
        if (probeStatus) probeStatus.style.display = 'none';

        if (previewWrap) {
          previewWrap.style.display = 'flex';
          const thumb = document.getElementById('previewThumb');
          const title = document.getElementById('previewTitle');
          const meta = document.getElementById('previewMeta');
          const filenameInput = document.getElementById('inputCustomFilename');

          if (thumb) {
            thumb.src = data.thumbnail || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=200';
          }
          if (title) title.textContent = data.title;
          if (meta) meta.textContent = `${data.category} • ${data.duration || 'N/A'} • ${data.size_formatted || 'Stream'}`;
          if (filenameInput) filenameInput.value = data.filename || data.title;
        }

        // Render format selection pills
        if (formatContainer && data.formats && data.formats.length > 0) {
          let fHtml = '';
          data.formats.forEach((fmt, idx) => {
            const isSel = idx === 0;
            if (isSel) this.selectedFormat = fmt.format_id;
            fHtml += `
              <div class="format-pill ${isSel ? 'selected' : ''}" onclick="App.selectFormatPill(this, '${fmt.format_id}')">
                <strong>${fmt.resolution}</strong> (${fmt.ext.toUpperCase()})
              </div>
            `;
          });

          // Also add audio extraction presets if stream
          if (data.audio_formats && data.audio_formats.length > 0) {
            data.audio_formats.slice(0, 2).forEach(afmt => {
              fHtml += `
                <div class="format-pill" onclick="App.selectFormatPill(this, '${afmt.format_id}', true)">
                  🎵 ${afmt.quality}
                </div>
              `;
            });
          }

          formatContainer.innerHTML = fHtml;
        }

        this.showToast('Media inspected successfully!', 'success');
      } else {
        if (probeStatus) {
          probeStatus.textContent = data.error || 'Failed to inspect link.';
        }
        this.showToast(data.error || 'Failed to analyze URL.', 'error');
      }
    } catch (e) {
      if (probeStatus) probeStatus.textContent = 'Error connecting to probe service.';
      this.showToast('Connection error analyzing link.', 'error');
    }
  },

  selectFormatPill(el, formatId, isAudio = false) {
    document.querySelectorAll('.format-pill').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    this.selectedFormat = formatId;
    this.selectedIsAudio = isAudio;
  },

  async submitDownload() {
    const url = document.getElementById('inputDownloadUrl')?.value?.trim();
    if (!url) {
      this.showToast('Please provide a URL.', 'error');
      return;
    }

    const customFilename = document.getElementById('inputCustomFilename')?.value?.trim();
    const payload = {
      action: 'create',
      url: url,
      title: this.probedData ? this.probedData.title : customFilename,
      filename: customFilename || (this.probedData ? this.probedData.filename : ''),
      category: this.selectedIsAudio ? 'Music & Audio' : (this.probedData ? this.probedData.category : 'Videos'),
      file_type: this.selectedIsAudio ? 'audio' : (this.probedData ? (this.probedData.category === 'Videos' ? 'video' : 'file') : 'file'),
      file_size: this.probedData ? this.probedData.size_bytes : 0,
      duration: this.probedData ? this.probedData.duration : 'N/A',
      thumbnail: this.probedData ? this.probedData.thumbnail : null,
      is_stream: this.probedData ? this.probedData.is_stream : false,
      format_id: this.selectedFormat || 'best'
    };

    try {
      const res = await fetch('api/downloads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data && data.success) {
        this.showToast('Download started successfully!', 'success');
        this.closeModals();
        this.loadDownloads();
      } else {
        this.showToast(data.error || 'Failed to start download.', 'error');
      }
    } catch (e) {
      this.showToast('Error requesting download.', 'error');
    }
  },

  async submitAudioExtract() {
    const inputPath = document.getElementById('inputExtractVideoFile')?.value?.trim();
    const format = document.getElementById('selectExtractFormat')?.value || 'mp3';
    const bitrate = document.getElementById('selectExtractBitrate')?.value || '320k';

    if (!inputPath) {
      this.showToast('Please provide the full path to a local video file.', 'error');
      return;
    }

    this.showToast('FFmpeg audio extraction in progress...', 'info');

    try {
      const res = await fetch('api/extract_audio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          input_file: inputPath,
          format: format,
          bitrate: bitrate
        })
      });
      const data = await res.json();
      if (data && data.success) {
        this.showToast(`Audio extracted: ${data.filename}`, 'success');
        this.closeModals();
        this.loadDownloads();
      } else {
        this.showToast(data.error || 'Extraction failed.', 'error');
      }
    } catch (e) {
      this.showToast('Error connecting to extraction service.', 'error');
    }
  },

  async pauseDownload(taskId) {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'pause', task_id: taskId })
    });
    this.loadDownloads();
  },

  async resumeDownload(taskId) {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'resume', task_id: taskId })
    });
    this.loadDownloads();
  },

  async pauseAll() {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'pause_all' })
    });
    this.showToast('All active downloads paused.', 'info');
    this.loadDownloads();
  },

  async resumeAll() {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'resume_all' })
    });
    this.showToast('All paused downloads resumed.', 'success');
    this.loadDownloads();
  },

  async stopAll() {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'stop_all' })
    });
    this.showToast('All downloads stopped.', 'error');
    this.loadDownloads();
  },

  async cleanCompleted() {
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'clean_completed' })
    });
    this.showToast('Cleaned completed downloads.', 'info');
    this.loadDownloads();
  },

  async deleteDownload(taskId) {
    if (!confirm('Are you sure you want to remove this download record?')) return;
    await fetch('api/downloads.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', task_id: taskId, delete_file: false })
    });
    this.loadDownloads();
  },

  async openItem(taskId, action) {
    try {
      const res = await fetch('api/open.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, task_id: taskId })
      });
      const data = await res.json();
      if (!data.success) {
        this.showToast(data.error || 'Unable to open location.', 'error');
      }
    } catch (e) {
      this.showToast('Failed to trigger open action.', 'error');
    }
  },

  async loadSettings() {
    try {
      const res = await fetch('api/settings.php');
      const data = await res.json();
      if (data && data.success && data.settings) {
        const s = data.settings;
        if (document.getElementById('inputDownloadFolder')) {
          document.getElementById('inputDownloadFolder').value = s.download_folder || '';
        }
        if (document.getElementById('selectMaxConcurrent')) {
          document.getElementById('selectMaxConcurrent').value = s.max_concurrent || '3';
        }
        if (document.getElementById('selectSpeedLimit')) {
          document.getElementById('selectSpeedLimit').value = s.speed_limit || 'unlimited';
        }
      }
    } catch (e) {
      console.error(e);
    }
  },

  async saveSettings() {
    const payload = {
      download_folder: document.getElementById('inputDownloadFolder')?.value?.trim(),
      max_concurrent: document.getElementById('selectMaxConcurrent')?.value,
      speed_limit: document.getElementById('selectSpeedLimit')?.value
    };

    try {
      const res = await fetch('api/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data && data.success) {
        this.showToast('Settings saved successfully.', 'success');
        this.closeModals();
      }
    } catch (e) {
      this.showToast('Failed to save settings.', 'error');
    }
  },

  openAddModal() {
    this.closeModals();
    document.getElementById('modalAddUrl')?.classList.add('open');
    document.getElementById('inputDownloadUrl')?.focus();
  },

  openAudioExtractModal() {
    this.closeModals();
    document.getElementById('modalExtractAudio')?.classList.add('open');
  },

  openSettingsModal() {
    this.closeModals();
    this.loadSettings();
    document.getElementById('modalSettings')?.classList.add('open');
  },

  closeModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
  },

  showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  },

  formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0.0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    let b = parseFloat(bytes);
    while (b >= 1024 && i < units.length - 1) {
      b /= 1024;
      i++;
    }
    return `${b.toFixed(1)} ${units[i]}`;
  },

  escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
