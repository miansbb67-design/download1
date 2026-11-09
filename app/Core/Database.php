<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static string $dbType = 'sqlite';

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $storageDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }

            $sqlitePath = $storageDir . DIRECTORY_SEPARATOR . 'downloadpro.sqlite';
            
            try {
                self::$instance = new PDO("sqlite:" . $sqlitePath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::initSchema(self::$instance);
            } catch (PDOException $e) {
                die("Database connection error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private static function initSchema(PDO $db): void
    {
        // 1. Create downloads table
        $db->exec("
            CREATE TABLE IF NOT EXISTS downloads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id VARCHAR(64) UNIQUE,
                url TEXT NOT NULL,
                title TEXT NOT NULL,
                filename TEXT NOT NULL,
                file_path TEXT,
                file_size BIGINT DEFAULT 0,
                downloaded_bytes BIGINT DEFAULT 0,
                progress REAL DEFAULT 0,
                speed VARCHAR(64) DEFAULT '0 KB/s',
                eta VARCHAR(64) DEFAULT '-',
                status VARCHAR(32) DEFAULT 'pending',
                category VARCHAR(64) DEFAULT 'Other Files',
                file_type VARCHAR(64) DEFAULT 'file',
                thumbnail TEXT,
                duration VARCHAR(32) DEFAULT 'N/A',
                is_resumable INTEGER DEFAULT 1,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 2. Create settings table
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key VARCHAR(64) PRIMARY KEY,
                value TEXT NOT NULL
            );
        ");

        // Seed default settings if empty
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM settings");
        $row = $stmt->fetch();
        if ($row['cnt'] == 0) {
            $defaultDownloads = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'downloads';
            if (!is_dir($defaultDownloads)) {
                mkdir($defaultDownloads, 0777, true);
            }

            $defaultSettings = [
                'download_folder' => $defaultDownloads,
                'max_concurrent' => '3',
                'speed_limit' => 'unlimited',
                'auto_retry' => '3',
                'theme' => 'dark',
                'sound_notify' => '1',
                'minimize_tray' => '1'
            ];

            $insertStmt = $db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
            foreach ($defaultSettings as $k => $v) {
                $insertStmt->execute([':key' => $k, ':value' => $v]);
            }
        }

        // Seed matching screenshot download items if downloads table is empty
        $countStmt = $db->query("SELECT COUNT(*) as cnt FROM downloads");
        $cntRow = $countStmt->fetch();
        if ($cntRow['cnt'] == 0) {
            $sampleItems = [
                [
                    'task_id' => 'sample_1',
                    'url' => 'https://example.com/media/Dil_Diyan_Gallan.mp4',
                    'title' => 'Dil_Diyan_Gallan___Official_Video___.mp4',
                    'filename' => 'Dil_Diyan_Gallan___Official_Video___.mp4',
                    'file_size' => 22334668, // 21.3 MB
                    'downloaded_bytes' => 22334668,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '04:18'
                ],
                [
                    'task_id' => 'sample_2',
                    'url' => 'https://example.com/media/Bom_Diggy_Diggy.mp4',
                    'title' => 'Bom_Diggy_Diggy_8K_Video___...Zack_Kni.mp4',
                    'filename' => 'Bom_Diggy_Diggy_8K_Video___...Zack_Kni.mp4',
                    'file_size' => 40055603, // 38.2 MB
                    'downloaded_bytes' => 40055603,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '03:42'
                ],
                [
                    'task_id' => 'sample_3',
                    'url' => 'https://example.com/media/Dil_Diyan_Gallan.mp3',
                    'title' => 'Dil_Diyan_Gallan___Official_Video___.mp3',
                    'filename' => 'Dil_Diyan_Gallan___Official_Video___.mp3',
                    'file_size' => 6815744, // 6.5 MB
                    'downloaded_bytes' => 6815744,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Music & Audio',
                    'file_type' => 'audio',
                    'duration' => '04:18'
                ],
                [
                    'task_id' => 'sample_4',
                    'url' => 'https://example.com/media/I_Tried_Instagram_Street_Food.mp4',
                    'title' => 'I_Tried_Instagram_Street_Food_Hacks.mp4',
                    'filename' => 'I_Tried_Instagram_Street_Food_Hacks.mp4',
                    'file_size' => 78852915, // 75.2 MB
                    'downloaded_bytes' => 78852915,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '12:05'
                ],
                [
                    'task_id' => 'sample_5',
                    'url' => 'https://example.com/media/Aankhein_Khuli.mp3',
                    'title' => 'Aankhein Khuli Song _ Mohabbatein _ Shah Rukh ...',
                    'filename' => 'Aankhein Khuli Song _ Mohabbatein _ Shah Rukh ...mp3',
                    'file_size' => 21390950, // 20.4 MB
                    'downloaded_bytes' => 21390950,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Music & Audio',
                    'file_type' => 'audio',
                    'duration' => '06:30'
                ],
                [
                    'task_id' => 'sample_6',
                    'url' => 'https://example.com/media/Video_by_aymanwilliam.mp4',
                    'title' => 'Video_by_aymanwilliam_acc.mp4',
                    'filename' => 'Video_by_aymanwilliam_acc.mp4',
                    'file_size' => 1992294, // 1.9 MB
                    'downloaded_bytes' => 1992294,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '00:45'
                ],
                [
                    'task_id' => 'sample_7',
                    'url' => 'https://example.com/media/Amrinder_Gill.mp3',
                    'title' => '(Official 1080p HD) Ki Samjhaiye ft. Amrinder Gill...',
                    'filename' => '(Official 1080p HD) Ki Samjhaiye ft. Amrinder Gill.mp3',
                    'file_size' => 8703180, // 8.3 MB
                    'downloaded_bytes' => 8703180,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Music & Audio',
                    'file_type' => 'audio',
                    'duration' => '04:12'
                ],
                [
                    'task_id' => 'sample_8',
                    'url' => 'https://example.com/media/Kalyani_Aasa_Kooda.mp3',
                    'title' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp3',
                    'filename' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp3',
                    'file_size' => 6081740, // 5.8 MB
                    'downloaded_bytes' => 6081740,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Music & Audio',
                    'file_type' => 'audio',
                    'duration' => '03:15'
                ],
                [
                    'task_id' => 'sample_9',
                    'url' => 'https://example.com/media/Kalyani_Aasa_Kooda.mp4',
                    'title' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp4',
                    'filename' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp4',
                    'file_size' => 19713228, // 18.8 MB
                    'downloaded_bytes' => 19713228,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '03:15'
                ],
                [
                    'task_id' => 'sample_10',
                    'url' => 'https://example.com/media/song.mp4',
                    'title' => 'song',
                    'filename' => 'song.mp4',
                    'file_size' => 2411724, // 2.3 MB
                    'downloaded_bytes' => 2411724,
                    'progress' => 100.0,
                    'speed' => '-',
                    'eta' => '-',
                    'status' => 'completed',
                    'category' => 'Videos',
                    'file_type' => 'video',
                    'duration' => '01:10'
                ]
            ];

            $insertDownload = $db->prepare("
                INSERT INTO downloads 
                (task_id, url, title, filename, file_size, downloaded_bytes, progress, speed, eta, status, category, file_type, duration)
                VALUES 
                (:task_id, :url, :title, :filename, :file_size, :downloaded_bytes, :progress, :speed, :eta, :status, :category, :file_type, :duration)
            ");

            foreach ($sampleItems as $item) {
                $insertDownload->execute($item);
            }
        }
    }
}
