<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Security;
use PDO;

class MediaEngine
{
    private string $pythonBin;
    private string $extractorScript;
    private string $downloaderScript;
    private string $storageDir;
    private string $tempDir;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->pythonBin = $config['python_bin'] ?: 'python';
        $baseDir = dirname(__DIR__, 2);
        
        $this->extractorScript = $baseDir . '/engine/extractor.py';
        $this->downloaderScript = $baseDir . '/engine/downloader.py';
        $this->storageDir = $config['storage']['downloads'];
        $this->tempDir = $config['storage']['temp'];
    }

    /**
     * Inspect URL for available video and audio streams
     */
    public function inspectUrl(string $url, int $playlistLimit = 50): array
    {
        // 1. SSRF Check
        $safety = Security::isSafeUrl($url);
        if (!$safety['safe']) {
            return ['success' => false, 'error' => $safety['error']];
        }

        // 2. Execute Python Extractor with UTF-8 encoding
        putenv("PYTHONIOENCODING=utf-8");
        putenv("PYTHONUTF8=1");

        $cmdPrefix = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'set PYTHONIOENCODING=utf-8&& set PYTHONUTF8=1&& ' : '';

        $cmd = sprintf(
            '%s%s %s --url %s --playlist-limit %d 2>&1',
            $cmdPrefix,
            escapeshellcmd($this->pythonBin),
            escapeshellarg($this->extractorScript),
            escapeshellarg($url),
            $playlistLimit
        );

        $output = shell_exec($cmd);
        if (!$output) {
            return ['success' => false, 'error' => 'Media engine produced no output.'];
        }

        // Find JSON block in output
        $jsonStart = strpos($output, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($output, $jsonStart);
            $parsed = json_decode($jsonStr, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return ['success' => false, 'error' => 'Failed to parse media metadata: ' . trim($output)];
    }

    /**
     * Start background download job
     */
    public function startDownload(string $url, string $type, string $quality, string $format, string $jobUuid, ?int $userId = null, ?int $folderId = null): array
    {
        // SSRF Check
        $safety = Security::isSafeUrl($url);
        if (!$safety['safe']) {
            return ['success' => false, 'error' => $safety['error']];
        }

        $pdo = Database::getConnection();

        // 1. Create job record in database
        $stmt = $pdo->prepare("INSERT INTO download_jobs 
            (job_uuid, user_id, source_url, media_type, target_format, target_quality, status, progress, folder_id) 
            VALUES (?, ?, ?, ?, ?, ?, 'downloading', 1.0, ?)");
        $stmt->execute([$jobUuid, $userId, $url, $type, $format, $quality, $folderId]);
        $jobId = (int)$pdo->lastInsertId();

        // Initialize status temp file
        $statusFile = $this->tempDir . "/{$jobUuid}.json";
        @file_put_contents($statusFile, json_encode([
            'status'           => 'downloading',
            'progress'         => 1.0,
            'speed'            => 'Initializing...',
            'eta'              => 'Calculating...',
            'downloaded_bytes' => 0,
            'total_bytes'      => 0,
            'segments_active'  => 8
        ]));

        // 2. Launch background process
        $cmdPrefix = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'set PYTHONIOENCODING=utf-8&& set PYTHONUTF8=1&& ' : '';
        $cmd = sprintf(
            '%s%s %s --url %s --type %s --quality %s --format %s --output-dir %s --job-id %s',
            $cmdPrefix,
            escapeshellcmd($this->pythonBin),
            escapeshellarg($this->downloaderScript),
            escapeshellarg($url),
            escapeshellarg($type),
            escapeshellarg($quality),
            escapeshellarg($format),
            escapeshellarg($this->storageDir),
            escapeshellarg($jobUuid)
        );

        // Windows background execution via WScript / start /B
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B cmd /C \"{$cmd} > nul 2>&1\"", "r"));
        } else {
            exec("{$cmd} > /dev/null 2>&1 &");
        }

        return [
            'success'   => true,
            'job_id'    => $jobId,
            'job_uuid'  => $jobUuid,
            'status'    => 'downloading'
        ];
    }

    /**
     * Get live job status
     */
    public function getJobStatus(string $jobUuid): array
    {
        $statusFile = $this->tempDir . "/{$jobUuid}.json";
        
        if (file_exists($statusFile)) {
            $data = json_decode(@file_get_contents($statusFile), true);
            if (is_array($data)) {
                // Synchronize to DB if completed
                if (!empty($data['status']) && $data['status'] === 'completed') {
                    $this->finalizeCompletedJob($jobUuid, $data);
                }
                return $data;
            }
        }

        // Query database
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM download_jobs WHERE job_uuid = ? LIMIT 1");
        $stmt->execute([$jobUuid]);
        $job = $stmt->fetch();

        if ($job) {
            return [
                'status'           => $job['status'],
                'progress'         => (float)$job['progress'],
                'speed'            => $job['speed'] ?: '0 KB/s',
                'eta'              => $job['eta'] ?: '--:--',
                'downloaded_bytes' => (int)$job['downloaded_bytes'],
                'total_bytes'      => (int)$job['total_bytes'],
                'error'            => $job['error_message']
            ];
        }

        return ['status' => 'not_found', 'progress' => 0];
    }

    /**
     * Pause, Resume, or Cancel Job
     */
    public function controlJob(string $jobUuid, string $action): bool
    {
        $ctrlFile = $this->tempDir . "/{$jobUuid}.ctrl";
        $action = strtolower($action);

        if ($action === 'resume') {
            if (file_exists($ctrlFile)) @unlink($ctrlFile);
            $this->updateJobDbStatus($jobUuid, 'downloading');
            return true;
        }

        if (in_array($action, ['pause', 'cancel'])) {
            @file_put_contents($ctrlFile, $action);
            $newStatus = ($action === 'pause') ? 'paused' : 'cancelled';
            $this->updateJobDbStatus($jobUuid, $newStatus);
            return true;
        }

        return false;
    }

    private function updateJobDbStatus(string $jobUuid, string $status): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE download_jobs SET status = ? WHERE job_uuid = ?");
            $stmt->execute([$status, $jobUuid]);
        } catch (\Exception $e) {}
    }

    private function finalizeCompletedJob(string $jobUuid, array $data): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, user_id, folder_id, media_type FROM download_jobs WHERE job_uuid = ? LIMIT 1");
            $stmt->execute([$jobUuid]);
            $job = $stmt->fetch();
            if (!$job) return;

            $filePath = $data['file_path'] ?? '';
            $mediaTitle = $data['media_title'] ?? basename($filePath);
            $fileSize = $data['total_bytes'] ?? (file_exists($filePath) ? filesize($filePath) : 0);

            // Update Job
            $stmtUp = $pdo->prepare("UPDATE download_jobs SET status = 'completed', progress = 100.0, speed = '0 KB/s', eta = '00:00', total_bytes = ?, downloaded_bytes = ?, media_title = ? WHERE job_uuid = ?");
            $stmtUp->execute([$fileSize, $fileSize, $mediaTitle, $jobUuid]);

            // Check if already in downloaded_files
            $stmtCheck = $pdo->prepare("SELECT id FROM downloaded_files WHERE job_id = ? LIMIT 1");
            $stmtCheck->execute([$job['id']]);
            if (!$stmtCheck->fetch() && !empty($filePath) && file_exists($filePath)) {
                $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeType = ($job['media_type'] === 'audio') ? 'audio/mpeg' : 'video/mp4';
                $retentionHours = 48;
                $expiresAt = date('Y-m-d H:i:s', time() + ($retentionHours * 3600));

                $stmtFile = $pdo->prepare("INSERT INTO downloaded_files 
                    (user_id, folder_id, job_id, original_filename, stored_filename, file_path, file_size, mime_type, media_type, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtFile->execute([
                    $job['user_id'],
                    $job['folder_id'],
                    $job['id'],
                    $mediaTitle . '.' . $ext,
                    basename($filePath),
                    $filePath,
                    $fileSize,
                    $mimeType,
                    $job['media_type'],
                    $expiresAt
                ]);
            }
        } catch (\Exception $e) {}
    }
}

