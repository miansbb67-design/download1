<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class DashboardController
{
    public function stats(Request $request): void
    {
        $userId = Auth::id() ?: 1;
        $pdo = Database::getConnection();

        // 1. Jobs summary
        $stmtJobs = $pdo->prepare("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'downloading' OR status = 'processing' THEN 1 ELSE 0 END) as active_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
                SUM(CASE WHEN media_type = 'video' THEN 1 ELSE 0 END) as video_jobs,
                SUM(CASE WHEN media_type = 'audio' THEN 1 ELSE 0 END) as audio_jobs,
                SUM(CASE WHEN media_type = 'playlist' THEN 1 ELSE 0 END) as playlist_jobs
            FROM download_jobs 
            WHERE user_id = ?
        ");
        $stmtJobs->execute([$userId]);
        $stats = $stmtJobs->fetch();

        // 2. Total file size downloaded
        $stmtSize = $pdo->prepare("SELECT COALESCE(SUM(file_size), 0) as total_size, COUNT(id) as total_files FROM downloaded_files WHERE user_id = ?");
        $stmtSize->execute([$userId]);
        $sizeRow = $stmtSize->fetch();

        // 3. Playlists count
        $stmtPl = $pdo->prepare("SELECT COUNT(id) as pl_count FROM playlists WHERE user_id = ?");
        $stmtPl->execute([$userId]);
        $plRow = $stmtPl->fetch();

        // 4. Conversions count
        $stmtConv = $pdo->prepare("SELECT COUNT(id) as conv_count FROM conversion_jobs WHERE user_id = ?");
        $stmtConv->execute([$userId]);
        $convRow = $stmtConv->fetch();

        Response::success([
            'total_downloads'     => (int)($stats['total_jobs'] ?? 0),
            'active_downloads'    => (int)($stats['active_jobs'] ?? 0),
            'completed_downloads' => (int)($stats['completed_jobs'] ?? 0),
            'failed_downloads'    => (int)($stats['failed_jobs'] ?? 0),
            'video_downloads'     => (int)($stats['video_jobs'] ?? 0),
            'audio_downloads'     => (int)($stats['audio_jobs'] ?? 0),
            'playlist_downloads'  => (int)($plRow['pl_count'] ?? 0),
            'conversion_count'    => (int)($convRow['conv_count'] ?? 0),
            'total_files'         => (int)($sizeRow['total_files'] ?? 0),
            'total_bytes'         => (int)($sizeRow['total_size'] ?? 0)
        ]);
    }

    public function history(Request $request): void
    {
        $userId = Auth::id() ?: 1;
        $limit = (int)$request->input('limit', 20);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT dj.*, df.id as file_id, df.stored_filename, df.file_size as disk_size, f.name as folder_name
            FROM download_jobs dj
            LEFT JOIN downloaded_files df ON dj.id = df.job_id
            LEFT JOIN folders f ON dj.folder_id = f.id
            WHERE dj.user_id = ?
            ORDER BY dj.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $history = $stmt->fetchAll();

        Response::success(['history' => $history]);
    }

    public function notifications(Request $request): void
    {
        $userId = Auth::id() ?: 1;
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();

        Response::success(['notifications' => $notifs]);
    }
}

