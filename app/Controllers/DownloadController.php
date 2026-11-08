<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Services\MediaEngine;

class DownloadController
{
    private MediaEngine $engine;

    public function __construct()
    {
        $this->engine = new MediaEngine();
    }

    public function start(Request $request): void
    {
        $url = trim($request->input('url', ''));
        $type = $request->input('type', 'video');
        $quality = $request->input('quality', '720p');
        $format = $request->input('format', 'mp4');
        $folderId = $request->input('folder_id');

        if (empty($url)) {
            Response::error('Target URL is required.');
        }

        $userId = Auth::id();
        $jobUuid = 'job_' . bin2hex(random_bytes(16));

        $res = $this->engine->startDownload($url, $type, $quality, $format, $jobUuid, $userId, $folderId ? (int)$folderId : null);
        if (!$res['success']) {
            Response::error($res['error'] ?? 'Failed to initialize download.');
        }

        Response::success([
            'job_uuid' => $jobUuid,
            'status'   => 'downloading',
            'type'     => $type,
            'quality'  => $quality,
            'format'   => $format
        ], 'Download job queued successfully.');
    }

    public function progress(Request $request, array $params = []): void
    {
        $uuid = $params['uuid'] ?? $request->input('job_uuid');
        if (empty($uuid)) {
            Response::error('Job UUID is required.');
        }

        $status = $this->engine->getJobStatus($uuid);
        Response::success($status);
    }

    public function control(Request $request): void
    {
        $uuid = $request->input('job_uuid');
        $action = $request->input('action'); // pause, resume, cancel

        if (empty($uuid) || empty($action)) {
            Response::error('Job UUID and action are required.');
        }

        $res = $this->engine->controlJob($uuid, $action);
        if ($res) {
            Response::success(['action' => $action, 'job_uuid' => $uuid], "Job {$action} signal transmitted.");
        } else {
            Response::error('Failed to update job control state.');
        }
    }

    public function file(Request $request, array $params = []): void
    {
        $id = $params['id'] ?? $request->input('file_id');
        if (empty($id)) {
            Response::error('File ID required.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM downloaded_files WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $file = $stmt->fetch();

        if (!$file || !file_exists($file['file_path'])) {
            // Also check download_jobs for completed file
            $stmtJob = $pdo->prepare("SELECT * FROM download_jobs WHERE job_uuid = ? LIMIT 1");
            $stmtJob->execute([$id]);
            $job = $stmtJob->fetch();
            if ($job) {
                // Find file in downloads
                $config = require dirname(__DIR__, 2) . '/config/app.php';
                $downloadsDir = $config['storage']['downloads'];
                foreach (scandir($downloadsDir) as $item) {
                    if (str_starts_with($item, $job['job_uuid'])) {
                        $fullPath = $downloadsDir . '/' . $item;
                        Response::streamDownload($fullPath, $item);
                        return;
                    }
                }
            }
            Response::error('Downloadable file not found or expired.', 404);
        }

        // Increment download counter
        $stmtUp = $pdo->prepare("UPDATE downloaded_files SET download_count = download_count + 1 WHERE id = ?");
        $stmtUp->execute([$file['id']]);

        Response::streamDownload($file['file_path'], $file['original_filename'], $file['mime_type']);
    }

    public function directByUuid(Request $request, array $params = []): void
    {
        $uuid = $params['uuid'] ?? '';
        if (empty($uuid)) {
            Response::error('Job UUID required.');
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $downloadsDir = $config['storage']['downloads'];

        if (is_dir($downloadsDir)) {
            foreach (scandir($downloadsDir) as $item) {
                if (str_starts_with($item, $uuid)) {
                    $fullPath = $downloadsDir . DIRECTORY_SEPARATOR . $item;
                    $cleanName = preg_replace('/^' . preg_quote($uuid, '/') . '_?/', '', $item);
                    Response::streamDownload($fullPath, $cleanName ?: 'media_download.mp4');
                    return;
                }
            }
        }

        Response::error('File is still downloading or not found.', 404);
    }
}
