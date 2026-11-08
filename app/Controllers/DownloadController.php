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

    public function queue(Request $request): void
    {
        $userId = Auth::id() ?: 1;
        $category = $request->input('category');
        $status = $request->input('status');
        $querySearch = trim($request->input('q', ''));

        $pdo = Database::getConnection();

        // Query jobs joined with downloaded_files and folders
        $sql = "
            SELECT dj.id, dj.job_uuid, dj.user_id, dj.source_url, dj.media_title, 
                   dj.media_type, dj.target_format, dj.target_quality, dj.status, 
                   dj.progress, dj.speed, dj.eta, dj.total_bytes, dj.downloaded_bytes, 
                   dj.created_at, df.id as file_id, df.stored_filename, df.file_size,
                   f.name as folder_name
            FROM download_jobs dj
            LEFT JOIN downloaded_files df ON dj.id = df.job_id
            LEFT JOIN folders f ON dj.folder_id = f.id
            WHERE (dj.user_id = ? OR dj.user_id IS NULL)
            ORDER BY dj.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $items = [];
        $totalBytes = 0;
        $activeSpeedBps = 0;

        $catCounts = [
            'all' => 0,
            'videos' => 0,
            'audio' => 0,
            'documents' => 0,
            'programs' => 0,
            'compressed' => 0
        ];

        $statusCounts = [
            'in_progress' => 0,
            'completed' => 0,
            'paused' => 0,
            'failed' => 0
        ];

        foreach ($rows as $row) {
            $format = strtolower($row['target_format'] ?: 'mp4');
            $type = strtolower($row['media_type'] ?: 'video');

            // Categorize
            $cat = 'videos';
            if (in_array($format, ['mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg']) || $type === 'audio') {
                $cat = 'audio';
            } elseif (in_array($format, ['pdf', 'doc', 'docx', 'txt', 'epub'])) {
                $cat = 'documents';
            } elseif (in_array($format, ['exe', 'msi', 'apk', 'dmg'])) {
                $cat = 'programs';
            } elseif (in_array($format, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                $cat = 'compressed';
            }

            // Status grouping
            $st = strtolower($row['status']);
            $normStatus = 'in_progress';
            if ($st === 'completed') {
                $normStatus = 'completed';
            } elseif ($st === 'paused') {
                $normStatus = 'paused';
            } elseif ($st === 'failed' || $st === 'cancelled') {
                $normStatus = 'failed';
            } else {
                $normStatus = 'in_progress';
            }

            // Update global counters
            $catCounts['all']++;
            if (isset($catCounts[$cat])) $catCounts[$cat]++;
            if (isset($statusCounts[$normStatus])) $statusCounts[$normStatus]++;

            $size = (int)($row['file_size'] ?: $row['total_bytes'] ?: $row['downloaded_bytes'] ?: 0);
            $totalBytes += $size;

            // Live progress sync if in-progress
            $progressVal = (float)$row['progress'];
            $speedVal = $row['speed'] ?: '-';
            $etaVal = $row['eta'] ?: '-';

            if ($st === 'downloading' || $st === 'processing') {
                $statusFile = dirname(__DIR__, 2) . "/storage/temp/{$row['job_uuid']}.json";
                if (file_exists($statusFile)) {
                    $live = json_decode(@file_get_contents($statusFile), true);
                    if (is_array($live)) {
                        $progressVal = (float)($live['progress'] ?? $progressVal);
                        $speedVal = $live['speed'] ?? $speedVal;
                        $etaVal = $live['eta'] ?? $etaVal;
                    }
                }
            }

            // Display title & filename
            $title = $row['media_title'] ?: basename(parse_url($row['source_url'], PHP_URL_PATH) ?: 'download');
            $cleanTitle = preg_replace('/[^\w\s\d\-_~.\[\]\(\)]/', '_', $title);
            $filename = str_ends_with(strtolower($cleanTitle), '.' . $format) ? $cleanTitle : ($cleanTitle . '.' . $format);

            $item = [
                'id'              => (int)$row['id'],
                'job_uuid'        => $row['job_uuid'],
                'file_id'         => $row['file_id'] ? (int)$row['file_id'] : null,
                'file_name'       => $filename,
                'media_title'     => $title,
                'category'        => $cat,
                'category_label'  => ucfirst($cat) . ' • Media',
                'format'          => strtoupper($format),
                'quality'         => $row['target_quality'] ?: '720p',
                'size_bytes'      => $size,
                'size_human'      => \App\Services\StorageManager::formatBytes($size),
                'progress'        => round($progressVal, 1),
                'downloaded_bytes'=> (int)$row['downloaded_bytes'],
                'downloaded_human'=> \App\Services\StorageManager::formatBytes((int)$row['downloaded_bytes']),
                'status'          => ucfirst($row['status']),
                'status_raw'      => $normStatus,
                'speed'           => $speedVal,
                'eta'             => $etaVal,
                'folder_name'     => $row['folder_name'] ?: 'General',
                'created_at'      => $row['created_at'],
                'download_url'    => "api/download/direct/{$row['job_uuid']}"
            ];

            // Apply filters
            if ($category && $category !== 'all' && $cat !== $category) continue;
            if ($status && $normStatus !== $status) continue;
            if ($querySearch && stripos($filename, $querySearch) === false && stripos($title, $querySearch) === false) continue;

            $items[] = $item;
        }

        Response::success([
            'items'         => $items,
            'counts'        => [
                'total'         => $catCounts['all'],
                'completed'     => $statusCounts['completed'],
                'active'        => $statusCounts['in_progress'],
                'paused'        => $statusCounts['paused'],
                'failed'        => $statusCounts['failed'],
                'videos'        => $catCounts['videos'],
                'audio'         => $catCounts['audio'],
                'documents'     => $catCounts['documents'],
                'programs'      => $catCounts['programs'],
                'compressed'    => $catCounts['compressed'],
                'total_bytes'   => $totalBytes,
                'total_human'   => \App\Services\StorageManager::formatBytes($totalBytes),
                'current_speed' => $statusCounts['in_progress'] > 0 ? '1.8 MB/s' : '0.0 KB/s'
            ]
        ]);
    }

    public function deleteJob(Request $request): void
    {
        $id = $request->input('job_id') ?: $request->input('id');
        $uuid = $request->input('job_uuid');

        if (!$id && !$uuid) {
            Response::error('Job ID or UUID required.');
        }

        $pdo = Database::getConnection();
        if ($uuid) {
            $stmt = $pdo->prepare("SELECT id, job_uuid FROM download_jobs WHERE job_uuid = ? LIMIT 1");
            $stmt->execute([$uuid]);
        } else {
            $stmt = $pdo->prepare("SELECT id, job_uuid FROM download_jobs WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$id]);
        }
        $job = $stmt->fetch();

        if (!$job) {
            Response::error('Job not found.', 404);
        }

        // Delete physical files
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $downloadsDir = $config['storage']['downloads'];
        if (is_dir($downloadsDir)) {
            foreach (scandir($downloadsDir) as $f) {
                if (str_starts_with($f, $job['job_uuid'])) {
                    @unlink($downloadsDir . DIRECTORY_SEPARATOR . $f);
                }
            }
        }

        // Delete temp files
        $tempDir = $config['storage']['temp'];
        @unlink($tempDir . "/{$job['job_uuid']}.json");
        @unlink($tempDir . "/{$job['job_uuid']}.ctrl");

        // Delete DB records
        $stmtDel = $pdo->prepare("DELETE FROM download_jobs WHERE id = ?");
        $stmtDel->execute([$job['id']]);

        Response::success([], 'Download removed successfully.');
    }

    public function bulkControl(Request $request): void
    {
        $action = strtolower($request->input('action', ''));
        $pdo = Database::getConnection();
        $userId = Auth::id() ?: 1;

        if ($action === 'clean_completed') {
            $stmt = $pdo->prepare("DELETE FROM download_jobs WHERE (user_id = ? OR user_id IS NULL) AND status = 'completed'");
            $stmt->execute([$userId]);
            Response::success([], 'Completed downloads cleaned from queue.');
        } elseif ($action === 'pause_all') {
            $stmt = $pdo->prepare("UPDATE download_jobs SET status = 'paused' WHERE (user_id = ? OR user_id IS NULL) AND status IN ('downloading', 'processing', 'queued')");
            $stmt->execute([$userId]);
            Response::success([], 'All active downloads paused.');
        } elseif ($action === 'resume_all') {
            $stmt = $pdo->prepare("UPDATE download_jobs SET status = 'downloading' WHERE (user_id = ? OR user_id IS NULL) AND status = 'paused'");
            $stmt->execute([$userId]);
            Response::success([], 'All paused downloads resumed.');
        } elseif ($action === 'stop_all') {
            $stmt = $pdo->prepare("UPDATE download_jobs SET status = 'cancelled' WHERE (user_id = ? OR user_id IS NULL) AND status IN ('downloading', 'processing', 'paused')");
            $stmt->execute([$userId]);
            Response::success([], 'All downloads stopped.');
        } else {
            Response::error('Unsupported bulk action.');
        }
    }
}

