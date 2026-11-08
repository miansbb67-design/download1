<?php
namespace App\Core;

use PDO;

class Auth
{
    private static ?array $currentUser = null;

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u && ($u['role'] === 'admin');
    }

    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        self::init();

        // 1. Check Session
        if (!empty($_SESSION['user_id'])) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT u.*, p.avatar, p.preferred_format, p.preferred_quality, p.max_concurrent_downloads, p.dark_mode 
                                   FROM users u 
                                   LEFT JOIN user_profiles p ON u.id = p.user_id 
                                   WHERE u.id = ? AND u.status = 'active' LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                unset($user['password_hash']);
                self::$currentUser = $user;
                return $user;
            }
        }

        // 2. Check Bearer Token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT u.*, p.avatar, p.preferred_format, p.preferred_quality, p.max_concurrent_downloads, p.dark_mode 
                                   FROM users u 
                                   LEFT JOIN user_profiles p ON u.id = p.user_id 
                                   WHERE u.api_token = ? AND u.status = 'active' LIMIT 1");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                unset($user['password_hash']);
                self::$currentUser = $user;
                return $user;
            }
        }

        return null;
    }

    public static function attempt(string $email, string $password): bool
    {
        self::init();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                return false;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            self::$currentUser = null;
            return true;
        }

        return false;
    }

    public static function register(string $name, string $email, string $password): array
    {
        $pdo = Database::getConnection();

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([trim($email)]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $apiToken = 'usr_' . bin2hex(random_bytes(24));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status, api_token, email_verified_at) VALUES (?, ?, ?, 'user', 'active', ?, NOW())");
            $stmt->execute([trim($name), trim($email), $hash, $apiToken]);
            $userId = (int)$pdo->lastInsertId();

            // Create profile
            $stmtProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, avatar, preferred_format, preferred_quality) VALUES (?, 'assets/img/avatar-default.png', 'mp4', '1080p')");
            $stmtProfile->execute([$userId]);

            // Create default folders
            $stmtFolder = $pdo->prepare("INSERT INTO folders (user_id, name, slug, color, icon) VALUES (?, 'General Downloads', 'general', '#16A36A', 'download')");
            $stmtFolder->execute([$userId]);

            $pdo->commit();

            self::init();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = 'user';

            return ['success' => true, 'user_id' => $userId];
        } catch (\Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function logout(): void
    {
        self::init();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$currentUser = null;
    }
}
