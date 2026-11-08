<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\StorageManager;

class AdminController
{
    private function checkAdmin(): void
    {
        if (!Auth::isAdmin()) {
            Response::error('Administrator access required.', 403);
        }
    }

    public function stats(Request $request): void
    {
        $this->checkAdmin();
        $pdo = Database::getConnection();

        // 1. Users
        $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

        // 2. Jobs
        $totalJobs = $pdo->query("SELECT COUNT(*) FROM download_jobs")->fetchColumn();
        $failedJobs = $pdo->query("SELECT COUNT(*) FROM download_jobs WHERE status = 'failed'")->fetchColumn();
        $completedJobs = $pdo->query("SELECT COUNT(*) FROM download_jobs WHERE status = 'completed'")->fetchColumn();

        // 3. Storage
        $sm = new StorageManager();
        $storage = $sm->getStorageStats();

        // 4. Conversions
        $conversions = $pdo->query("SELECT COUNT(*) FROM conversion_jobs")->fetchColumn();

        Response::success([
            'users_total'      => (int)$usersCount,
            'users_active'     => (int)$activeUsers,
            'jobs_total'       => (int)$totalJobs,
            'jobs_failed'      => (int)$failedJobs,
            'jobs_completed'   => (int)$completedJobs,
            'conversions'      => (int)$conversions,
            'storage_used'     => $storage['total_human'],
            'storage_bytes'    => $storage['total_bytes'],
            'disk_free'        => $storage['disk_free_human'],
            'active_workers'   => 4
        ]);
    }

    public function users(Request $request): void
    {
        $this->checkAdmin();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
                   COUNT(dj.id) as total_downloads
            FROM users u
            LEFT JOIN download_jobs dj ON u.id = dj.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();

        Response::success(['users' => $users]);
    }

    public function toggleUser(Request $request): void
    {
        $this->checkAdmin();
        $id = (int)$request->input('user_id');
        $status = $request->input('status'); // active, inactive, suspended

        if (!in_array($status, ['active', 'inactive', 'suspended'])) {
            Response::error('Invalid user status specified.');
        }

        // Prevent self suspension
        if ($id === Auth::id()) {
            Response::error('You cannot change your own administrator status.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        Response::success([], "User status updated to {$status}.");
    }

    public function settings(Request $request): void
    {
        $this->checkAdmin();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("SELECT * FROM admin_settings ORDER BY setting_group, setting_key");
        $settings = $stmt->fetchAll();

        Response::success(['settings' => $settings]);
    }

    public function updateSettings(Request $request): void
    {
        $this->checkAdmin();
        $settings = $request->input('settings', []);

        if (!is_array($settings)) {
            Response::error('Invalid settings payload.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = ?");

        foreach ($settings as $key => $val) {
            $stmt->execute([(string)$val, (string)$key]);
        }

        Response::success([], 'System settings updated successfully.');
    }

    public function sources(Request $request): void
    {
        $this->checkAdmin();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("SELECT * FROM supported_sources ORDER BY is_active DESC, name ASC");
        $sources = $stmt->fetchAll();

        Response::success(['sources' => $sources]);
    }

    public function toggleSource(Request $request): void
    {
        $this->checkAdmin();
        $id = (int)$request->input('source_id');
        $active = (int)$request->input('is_active');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE supported_sources SET is_active = ? WHERE id = ?");
        $stmt->execute([$active ? 1 : 0, $id]);

        Response::success([], 'Source status updated.');
    }

    public function logs(Request $request): void
    {
        $this->checkAdmin();
        $pdo = Database::getConnection();

        $limit = (int)$request->input('limit', 50);
        $level = $request->input('level');

        $query = "SELECT * FROM system_logs";
        $binds = [];

        if (!empty($level)) {
            $query .= " WHERE level = ?";
            $binds[] = $level;
        }

        $query .= " ORDER BY created_at DESC LIMIT ?";
        $binds[] = $limit;

        $stmt = $pdo->prepare($query);
        foreach ($binds as $i => $v) {
            $stmt->bindValue($i + 1, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();
        $logs = $stmt->fetchAll();

        Response::success(['logs' => $logs]);
    }

    public function cleanStorage(Request $request): void
    {
        $this->checkAdmin();
        $hours = (int)$request->input('retention_hours', 48);

        $sm = new StorageManager();
        $count = $sm->cleanExpiredFiles($hours);

        Response::success(['cleaned_files' => $count], "Purged {$count} expired files from local disk.");
    }
}
