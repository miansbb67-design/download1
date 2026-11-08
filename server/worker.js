/**
 * FlowFetch Background Worker & SSE Progress Engine
 * Zero-dependency Node.js companion for real-time progress events & retention cleanup.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = 3001;
const TEMP_DIR = path.resolve(__dirname, '../storage/temp');
const DOWNLOADS_DIR = path.resolve(__dirname, '../storage/downloads');
const CONVERTED_DIR = path.resolve(__dirname, '../storage/converted');

// Ensure directories exist
[TEMP_DIR, DOWNLOADS_DIR, CONVERTED_DIR].forEach(dir => {
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Periodic retention cleanup (Every 30 minutes)
function cleanExpiredFiles(hours = 48) {
    const now = Date.now();
    const maxAgeMs = hours * 60 * 60 * 1000;

    [DOWNLOADS_DIR, CONVERTED_DIR, TEMP_DIR].forEach(targetDir => {
        try {
            const files = fs.readdirSync(targetDir);
            for (const file of files) {
                const filePath = path.join(targetDir, file);
                const stats = fs.statSync(filePath);
                if (now - stats.mtimeMs > maxAgeMs) {
                    fs.unlinkSync(filePath);
                    console.log(`[Retention] Removed expired file: ${file}`);
                }
            }
        } catch (e) {
            // Ignore directory scan errors
        }
    });
}
setInterval(() => cleanExpiredFiles(48), 30 * 60 * 1000);

const server = http.createServer((req, res) => {
    // Enable CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // SSE: Real-time progress streaming for a specific job
    // GET /stream-progress?job_id=XXXX
    if (pathname === '/stream-progress') {
        const jobId = parsedUrl.query.job_id;
        if (!jobId || !/^[a-zA-Z0-9_-]+$/.test(jobId)) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'Valid job_id required' }));
            return;
        }

        res.writeHead(200, {
            'Content-Type': 'text/event-stream',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive'
        });

        const statusFile = path.join(TEMP_DIR, `${jobId}.json`);

        const timer = setInterval(() => {
            if (fs.existsSync(statusFile)) {
                try {
                    const content = fs.readFileSync(statusFile, 'utf8');
                    const data = JSON.parse(content);
                    res.write(`data: ${JSON.stringify(data)}\n\n`);

                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(timer);
                        res.end();
                    }
                } catch (e) {
                    // Ignore transient read errors during atomic write
                }
            } else {
                res.write(`data: ${JSON.stringify({ status: 'queued', progress: 0 })}\n\n`);
            }
        }, 500);

        req.on('close', () => {
            clearInterval(timer);
        });
        return;
    }

    // Control Job: Pause, Resume, Cancel
    // POST /control-job?job_id=XXXX&action=pause|resume|cancel
    if (pathname === '/control-job') {
        const jobId = parsedUrl.query.job_id;
        const action = (parsedUrl.query.action || '').toLowerCase();
        
        if (!jobId || !['pause', 'resume', 'cancel'].includes(action)) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'Invalid job_id or action' }));
            return;
        }

        const ctrlFile = path.join(TEMP_DIR, `${jobId}.ctrl`);
        try {
            if (action === 'resume') {
                if (fs.existsSync(ctrlFile)) fs.unlinkSync(ctrlFile);
            } else {
                fs.writeFileSync(ctrlFile, action, 'utf8');
            }
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: true, action: action, job_id: jobId }));
        } catch (e) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: e.message }));
        }
        return;
    }

    // Health check
    if (pathname === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', worker: 'FlowFetch Engine', uptime: process.uptime() }));
        return;
    }

    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Not Found' }));
});

server.listen(PORT, '127.0.0.1', () => {
    console.log(`[FlowFetch Worker] Background SSE & Control Server listening on http://127.0.0.1:${PORT}`);
});
