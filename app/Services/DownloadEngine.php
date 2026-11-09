<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Security;
use PDO;

class DownloadEngine
{
    public static function getDownloads(?string $category = null, ?string $status = null, ?string $search = null): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM downloads WHERE 1=1";
        $params = [];

        if ($category && $category !== 'All' && $category !== 'All Downloads') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        if ($status && $status !== 'All') {
            $sql .= " AND status = :status";
            $params[':status'] = strtolower($status);
        }

        if ($search && trim($search) !== '') {
            $sql .= " AND (title LIKE :search OR filename LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getMetrics(): array
    {
        $db = Database::getConnection();
        
        $totalStmt = $db->query("SELECT COUNT(*) as total FROM downloads");
        $total = (int)$totalStmt->fetch()['total'];

        $completedStmt = $db->query("SELECT COUNT(*) as completed FROM downloads WHERE status = 'completed'");
        $completed = (int)$completedStmt->fetch()['completed'];

        $activeStmt = $db->query("SELECT COUNT(*) as active FROM downloads WHERE status IN ('downloading', 'pending')");
        $active = (int)$activeStmt->fetch()['active'];

        $dataStmt = $db->query("SELECT SUM(downloaded_bytes) as total_bytes FROM downloads");
        $totalBytes = (float)($dataStmt->fetch()['total_bytes'] ?? 0);

        // Calculate total data string e.g. 170.1 MB
        $formattedData = self::formatBytes($totalBytes);

        // Sum speed of active downloads
        $speed = "0.0 KB/s";
        if ($active > 0) {
            $speedStmt = $db->query("SELECT speed FROM downloads WHERE status = 'downloading' LIMIT 1");
            $sRow = $speedStmt->fetch();
            if ($sRow && !empty($sRow['speed']) && $sRow['speed'] !== '-') {
                $speed = $sRow['speed'];
            }
        }

        return [
            'total_downloads' => $total,
            'completed' => $completed,
            'active_queue' => $active,
            'current_speed' => $speed,
            'total_data' => $formattedData,
            'total_bytes' => $totalBytes
        ];
    }

    public static function getSidebarCounts(): array
    {
        $db = Database::getConnection();

        $categories = [
            'All Downloads' => (int)$db->query("SELECT COUNT(*) as c FROM downloads")->fetch()['c'],
            'Videos' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE category = 'Videos'")->fetch()['c'],
            'Music & Audio' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE category = 'Music & Audio'")->fetch()['c'],
            'Documents' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE category = 'Documents'")->fetch()['c'],
            'Programs (.exe)' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE category = 'Programs (.exe)'")->fetch()['c'],
            'Compressed' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE category = 'Compressed'")->fetch()['c'],
        ];

        $statuses = [
            'In Progress' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE status IN ('downloading', 'pending')")->fetch()['c'],
            'Completed' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE status = 'completed'")->fetch()['c'],
            'Paused' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE status = 'paused'")->fetch()['c'],
            'Failed' => (int)$db->query("SELECT COUNT(*) as c FROM downloads WHERE status = 'failed'")->fetch()['c'],
        ];

        return [
            'categories' => $categories,
            'statuses' => $statuses
        ];
    }

    public static function createDownload(array $data): array
    {
        $db = Database::getConnection();
        $taskId = 'dl_' . bin2hex(random_bytes(8));

        $url = $data['url'];
        $title = $data['title'] ?? basename(parse_url($url, PHP_URL_PATH) ?: 'download');
        $filename = Security::sanitizeFilename($data['filename'] ?? $title);
        $fileSize = (int)($data['file_size'] ?? 0);
        $category = $data['category'] ?? 'Other Files';
        $fileType = $data['file_type'] ?? 'file';
        $duration = $data['duration'] ?? 'N/A';
        $thumbnail = $data['thumbnail'] ?? null;
        $isStream = !empty($data['is_stream']);
        $formatId = $data['format_id'] ?? 'best';

        // Get default downloads folder
        $setStmt = $db->query("SELECT value FROM settings WHERE key = 'download_folder'");
        $downFolder = $setStmt->fetch()['value'] ?? (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'downloads');
        if (!is_dir($downFolder)) {
            mkdir($downFolder, 0777, true);
        }

        $filePath = $downFolder . DIRECTORY_SEPARATOR . $filename;

        $stmt = $db->prepare("
            INSERT INTO downloads 
            (task_id, url, title, filename, file_path, file_size, downloaded_bytes, progress, speed, eta, status, category, file_type, thumbnail, duration, is_resumable)
            VALUES 
            (:task_id, :url, :title, :filename, :file_path, :file_size, 0, 0, 'Starting...', 'Calculating', 'pending', :category, :file_type, :thumbnail, :duration, 1)
        ");

        $stmt->execute([
            ':task_id' => $taskId,
            ':url' => $url,
            ':title' => $title,
            ':filename' => $filename,
            ':file_path' => $filePath,
            ':file_size' => $fileSize,
            ':category' => $category,
            ':file_type' => $fileType,
            ':thumbnail' => $thumbnail,
            ':duration' => $duration
        ]);

        // Launch background job
        self::dispatchDownloadWorker($taskId, $url, $filePath, $isStream, $formatId, $fileType === 'audio');

        return [
            'success' => true,
            'task_id' => $taskId,
            'message' => 'Download scheduled successfully.'
        ];
    }

    private static function dispatchDownloadWorker(string $taskId, string $url, string $filePath, bool $isStream, string $formatId, bool $isAudio): void
    {
        $db = Database::getConnection();
        $dbDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $progressFile = $dbDir . DIRECTORY_SEPARATOR . $taskId . '.json';
        $python = MediaEngine::getPythonBinary();

        if ($isStream) {
            $script = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'downloader.py';
            $outDir = dirname($filePath);
            $audioFlag = $isAudio ? "1" : "0";

            $cmd = "start /B \"\" \"$python\" \"$script\" \"$url\" \"$outDir\" \"$formatId\" $audioFlag \"$progressFile\"";
            pclose(popen($cmd, "r"));
        } else {
            // Direct download with Range support
            $script = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'direct_downloader.php';
            $phpBin = 'C:\\xampp\\php\\php.exe';
            if (!file_exists($phpBin)) {
                $phpBin = 'php';
            }

            $cmd = "start /B \"\" \"$phpBin\" \"$script\" \"$taskId\" \"$url\" \"$filePath\" \"$progressFile\"";
            pclose(popen($cmd, "r"));
        }

        // Set status to downloading
        $update = $db->prepare("UPDATE downloads SET status = 'downloading', speed = 'Connecting...' WHERE task_id = :id");
        $update->execute([':id' => $taskId]);
    }

    public static function pauseDownload(string $taskId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE downloads SET status = 'paused', speed = '-', eta = '-' WHERE task_id = :id");
        return $stmt->execute([':id' => $taskId]);
    }

    public static function resumeDownload(string $taskId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM downloads WHERE task_id = :id");
        $stmt->execute([':id' => $taskId]);
        $dl = $stmt->fetch();

        if (!$dl) return false;

        $update = $db->prepare("UPDATE downloads SET status = 'pending', speed = 'Resuming...' WHERE task_id = :id");
        $update->execute([':id' => $taskId]);

        $isAudio = ($dl['file_type'] === 'audio');
        $isStream = (strpos($dl['url'], 'youtube') !== false || strpos($dl['url'], 'youtu.be') !== false || strpos($dl['url'], 'vimeo') !== false);
        
        self::dispatchDownloadWorker($taskId, $dl['url'], $dl['file_path'], $isStream, 'best', $isAudio);
        return true;
    }

    public static function pauseAll(): void
    {
        $db = Database::getConnection();
        $db->exec("UPDATE downloads SET status = 'paused', speed = '-', eta = '-' WHERE status = 'downloading'");
    }

    public static function resumeAll(): void
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT task_id FROM downloads WHERE status = 'paused'");
        $tasks = $stmt->fetchAll();
        foreach ($tasks as $t) {
            self::resumeDownload($t['task_id']);
        }
    }

    public static function stopAll(): void
    {
        $db = Database::getConnection();
        $db->exec("UPDATE downloads SET status = 'failed', speed = '-', eta = '-' WHERE status IN ('downloading', 'pending')");
    }

    public static function cleanCompleted(): void
    {
        $db = Database::getConnection();
        $db->exec("DELETE FROM downloads WHERE status = 'completed'");
    }

    public static function deleteDownload(string $taskId, bool $deleteDiskFile = false): bool
    {
        $db = Database::getConnection();
        if ($deleteDiskFile) {
            $stmt = $db->prepare("SELECT file_path FROM downloads WHERE task_id = :id");
            $stmt->execute([':id' => $taskId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['file_path']) && file_exists($row['file_path'])) {
                @unlink($row['file_path']);
            }
        }

        $del = $db->prepare("DELETE FROM downloads WHERE task_id = :id");
        return $del->execute([':id' => $taskId]);
    }

    public static function updateTaskProgress(string $taskId): void
    {
        $tempDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp';
        $progressFile = $tempDir . DIRECTORY_SEPARATOR . $taskId . '.json';

        if (file_exists($progressFile)) {
            $content = file_get_contents($progressFile);
            $data = json_decode($content, true);
            if ($data) {
                $db = Database::getConnection();
                $status = $data['status'] ?? 'downloading';
                $percent = (float)($data['percent'] ?? 0);
                $downloaded = (int)($data['downloaded_bytes'] ?? 0);
                $total = (int)($data['total_bytes'] ?? 0);
                $speedBytes = (float)($data['speed_bytes_sec'] ?? 0);
                $speedStr = self::formatBytes($speedBytes) . '/s';
                $etaSecs = (int)($data['eta_seconds'] ?? 0);
                $etaStr = self::formatEta($etaSecs);

                if ($status === 'completed' || $percent >= 100) {
                    $status = 'completed';
                    $speedStr = '-';
                    $etaStr = '-';
                    $percent = 100.0;
                    if (isset($data['file_path'])) {
                        $updFile = $db->prepare("UPDATE downloads SET file_path = :fp WHERE task_id = :id");
                        $updFile->execute([':fp' => $data['file_path'], ':id' => $taskId]);
                    }
                }

                $stmt = $db->prepare("
                    UPDATE downloads 
                    SET progress = :prog, downloaded_bytes = :dl, file_size = CASE WHEN file_size <= 0 THEN :total ELSE file_size END,
                        speed = :spd, eta = :eta, status = :status, updated_at = CURRENT_TIMESTAMP
                    WHERE task_id = :id AND status != 'paused'
                ");
                $stmt->execute([
                    ':prog' => $percent,
                    ':dl' => $downloaded,
                    ':total' => $total,
                    ':spd' => $speedStr,
                    ':eta' => $etaStr,
                    ':status' => $status,
                    ':id' => $taskId
                ]);
            }
        }
    }

    public static function formatBytes(float $bytes): string
    {
        if ($bytes <= 0) return "0.0 B";
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    public static function formatEta(int $seconds): string
    {
        if ($seconds <= 0) return "-";
        $m = floor($seconds / 60);
        $s = $seconds % 60;
        return sprintf("%02d:%02d", $m, $s);
    }
}
