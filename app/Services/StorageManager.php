<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class StorageManager
{
    private string $downloadsDir;
    private string $convertedDir;
    private string $uploadsDir;
    private string $tempDir;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->downloadsDir = $config['storage']['downloads'];
        $this->convertedDir = $config['storage']['converted'];
        $this->uploadsDir   = $config['storage']['uploads'];
        $this->tempDir      = $config['storage']['temp'];
    }

    /**
     * Purge expired files older than retention limit
     */
    public function cleanExpiredFiles(int $hours = 48): int
    {
        $pdo = Database::getConnection();
        $cleaned = 0;
        $expiryThreshold = date('Y-m-d H:i:s', time() - ($hours * 3600));

        // Find expired DB records
        $stmt = $pdo->prepare("SELECT id, file_path FROM downloaded_files WHERE created_at < ?");
        $stmt->execute([$expiryThreshold]);
        $files = $stmt->fetchAll();

        foreach ($files as $f) {
            if (!empty($f['file_path']) && file_exists($f['file_path'])) {
                @unlink($f['file_path']);
            }
            $cleaned++;
        }

        $stmtDel = $pdo->prepare("DELETE FROM downloaded_files WHERE created_at < ?");
        $stmtDel->execute([$expiryThreshold]);

        // Clean orphaned temp files
        $now = time();
        $maxAge = $hours * 3600;
        foreach ([$this->tempDir, $this->uploadsDir] as $dir) {
            if (is_dir($dir)) {
                foreach (scandir($dir) as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
                    if (is_file($fullPath) && ($now - filemtime($fullPath) > $maxAge)) {
                        @unlink($fullPath);
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $totalBytes = 0;
        $fileCount = 0;

        foreach ([$this->downloadsDir, $this->convertedDir] as $dir) {
            if (is_dir($dir)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $totalBytes += $file->getSize();
                        $fileCount++;
                    }
                }
            }
        }

        return [
            'total_bytes'      => $totalBytes,
            'total_human'      => $this->formatBytes($totalBytes),
            'total_files'      => $fileCount,
            'disk_free_bytes'  => @disk_free_space(__DIR__) ?: 0,
            'disk_free_human'  => $this->formatBytes(@disk_free_space(__DIR__) ?: 0),
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
