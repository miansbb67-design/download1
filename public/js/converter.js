/**
 * FlowFetch Video to Audio Transcoder Engine
 * Drag & Drop uploading and FFmpeg audio conversion tracker.
 */

window.FlowConverter = {
    currentUpload: null,
    conversionJobUuid: null,
    pollInterval: null,

    init() {
        const dropzone = document.getElementById('converterDropzone');
        if (!dropzone) return;

        ['dragenter', 'dragover'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                this.handleFileSelect(dt.files);
            }
        });
    },

    async handleFileSelect(files) {
        if (!files || files.length === 0) return;
        const file = files[0];

        // Max 500 MB
        if (file.size > 500 * 1024 * 1024) {
            FlowApp.notify('File size exceeds the 500 MB limit.', 'danger');
            return;
        }

        const dropzone = document.getElementById('converterDropzone');
        dropzone.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin dropzone-icon"></i>
            <h5 class="fw-bold mb-1">Uploading "${file.name}"...</h5>
            <p class="text-muted small">Validating media streams...</p>
        `;

        const formData = new FormData();
        formData.append('video_file', file);

        const res = await FlowApp.request('api/converter/upload', {
            method: 'POST',
            body: formData
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to upload video.', 'danger');
            this.reset();
            return;
        }

        this.currentUpload = res;
        this.showOptionsPanel(file.name, file.size);
    },

    showOptionsPanel(name, size) {
        document.getElementById('converterDropzone').classList.add('d-none');
        document.getElementById('converterOptionsPanel').classList.remove('d-none');
        document.getElementById('convFilename').innerText = name;
        document.getElementById('convFilesize').innerText = (size / (1024 * 1024)).toFixed(2) + ' MB';
    },

    reset() {
        this.currentUpload = null;
        if (this.pollInterval) clearInterval(this.pollInterval);

        const dropzone = document.getElementById('converterDropzone');
        dropzone.innerHTML = `
            <input type="file" id="videoFileInput" class="d-none" accept="video/*" onchange="FlowConverter.handleFileSelect(this.files)">
            <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
            <h5 class="fw-bold mb-1">Drag and drop your video file here</h5>
            <p class="text-muted small mb-0">Supports MP4, WebM, MKV, MOV, AVI up to 500 MB</p>
        `;
        dropzone.classList.remove('d-none');
        document.getElementById('converterOptionsPanel').classList.add('d-none');
        document.getElementById('convProgressWrap').classList.add('d-none');
        document.getElementById('convResultWrap').classList.add('d-none');
        document.getElementById('startConversionBtn').disabled = false;
    },

    async startTranscode() {
        if (!this.currentUpload) return;

        const format = document.getElementById('convTargetFormat').value;
        const bitrate = document.getElementById('convTargetBitrate').value;
        const btn = document.getElementById('startConversionBtn');

        btn.disabled = true;
        document.getElementById('convProgressWrap').classList.remove('d-none');
        document.getElementById('convResultWrap').classList.add('d-none');

        const res = await FlowApp.request('api/converter/convert', {
            method: 'POST',
            body: JSON.stringify({
                upload_uuid: this.currentUpload.upload_uuid,
                original_filename: this.currentUpload.original_filename,
                format: format,
                bitrate: bitrate
            })
        });

        if (!res.success) {
            FlowApp.notify(res.error || 'Failed to start conversion.', 'danger');
            btn.disabled = false;
            return;
        }

        this.conversionJobUuid = res.job_uuid;
        this.pollInterval = setInterval(() => this.pollConversionProgress(), 700);
    },

    async pollConversionProgress() {
        if (!this.conversionJobUuid) return;

        const res = await FlowApp.request(`api/converter/status/${this.conversionJobUuid}`);
        if (!res.success) return;

        const pct = Math.min(res.progress || 0, 100);
        document.getElementById('convProgressPct').innerText = `${pct.toFixed(1)}%`;
        document.getElementById('convProgressBar').style.width = `${pct}%`;

        if (res.status === 'completed' || pct >= 100) {
            clearInterval(this.pollInterval);
            document.getElementById('convProgressWrap').classList.add('d-none');
            document.getElementById('convResultWrap').classList.remove('d-none');

            const link = document.getElementById('convDownloadLink');
            link.href = `api/converter/download/${this.conversionJobUuid}`;

            FlowApp.notify('Audio conversion complete!', 'success');
        } else if (res.status === 'failed') {
            clearInterval(this.pollInterval);
            FlowApp.notify(res.error || 'FFmpeg conversion failed.', 'danger');
            document.getElementById('startConversionBtn').disabled = false;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FlowConverter.init();
});

