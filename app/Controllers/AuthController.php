<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;

class AuthController
{
    public function login(Request $request): void
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (empty($email) || empty($password)) {
            Response::error('Email and password are required.');
        }

        if (Auth::attempt($email, $password)) {
            $user = Auth::user();
            Response::success([
                'user' => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                    'api_token' => $user['api_token']
                ],
                'redirect' => $user['role'] === 'admin' ? '/admin' : '/dashboard'
            ], 'Login successful.');
        } else {
            Response::error('Invalid email or password credentials.', 401);
        }
    }

    public function register(Request $request): void
    {
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (empty($name) || strlen($name) < 2) {
            Response::error('Full name is required (min 2 characters).');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('A valid email address is required.');
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters long.');
        }

        $res = Auth::register($name, $email, $password);
        if ($res['success']) {
            $user = Auth::user();
            Response::success([
                'user' => $user,
                'redirect' => '/dashboard'
            ], 'Account created successfully!');
        } else {
            Response::error($res['error'] ?? 'Registration failed.');
        }
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::success(['redirect' => '/'], 'Logged out successfully.');
    }

    public function me(Request $request): void
    {
        $user = Auth::user();
        if ($user) {
            Response::success(['user' => $user]);
        } else {
            Response::success(['user' => null, 'guest' => true]);
        }
    }

    public function updateProfile(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Unauthorized.', 401);
        }

        $name = trim($request->input('name', ''));
        $prefFormat = $request->input('preferred_format', 'mp4');
        $prefQuality = $request->input('preferred_quality', '1080p');
        $darkMode = $request->input('dark_mode', false) ? 1 : 0;

        $pdo = Database::getConnection();
        if (!empty($name)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $userId]);
        }

        $stmt = $pdo->prepare("UPDATE user_profiles SET preferred_format = ?, preferred_quality = ?, dark_mode = ? WHERE user_id = ?");
        $stmt->execute([$prefFormat, $prefQuality, $darkMode, $userId]);

        Response::success([], 'Profile settings updated.');
    }

    public function changePassword(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Unauthorized.', 401);
        }

        $current = $request->input('current_password');
        $newPass = $request->input('new_password');

        if (strlen($newPass) < 6) {
            Response::error('New password must be at least 6 characters.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            Response::error('Current password does not match.');
        }

        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmtUp = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUp->execute([$newHash, $userId]);

        Response::success([], 'Password updated successfully.');
    }
}
