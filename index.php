<?php
/**
 * FlowFetch - Professional Video & Audio Downloader
 * Master Front Controller & REST API Router
 */

// Global PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\MediaController;
use App\Controllers\DownloadController;
use App\Controllers\PlaylistController;
use App\Controllers\ConverterController;
use App\Controllers\FolderController;
use App\Controllers\DashboardController;
use App\Controllers\AdminController;

Auth::init();

$request = new Request();
$router = new Router();

// ==========================================
// Web Views Routes
// ==========================================
$router->get('/', function() {
    require __DIR__ . '/views/home.php';
});

$router->get('/dashboard', function() {
    require __DIR__ . '/views/dashboard.php';
});

$router->get('/admin', function() {
    require __DIR__ . '/views/admin.php';
});

$router->get('/login', function() {
    require __DIR__ . '/views/login.php';
});

$router->get('/register', function() {
    require __DIR__ . '/views/register.php';
});

$router->get('/terms', function() {
    require __DIR__ . '/views/legal/terms.php';
});

$router->get('/privacy', function() {
    require __DIR__ . '/views/legal/privacy.php';
});

$router->get('/copyright', function() {
    require __DIR__ . '/views/legal/copyright.php';
});

// ==========================================
// REST API Routes
// ==========================================

// Auth API
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);
$router->post('/api/auth/profile', [AuthController::class, 'updateProfile']);
$router->post('/api/auth/password', [AuthController::class, 'changePassword']);

// Media Inspection API
$router->post('/api/media/inspect', [MediaController::class, 'inspect']);

// Download Engine API
$router->post('/api/download/start', [DownloadController::class, 'start']);
$router->get('/api/download/progress/{uuid}', [DownloadController::class, 'progress']);
$router->post('/api/download/control', [DownloadController::class, 'control']);
$router->get('/api/download/file/{id}', [DownloadController::class, 'file']);
$router->get('/api/download/direct/{uuid}', [DownloadController::class, 'directByUuid']);

// Playlist API
$router->post('/api/playlist/inspect', [PlaylistController::class, 'inspect']);
$router->post('/api/playlist/batch', [PlaylistController::class, 'startBatch']);

// Video to Audio Converter API
$router->post('/api/converter/upload', [ConverterController::class, 'upload']);
$router->post('/api/converter/convert', [ConverterController::class, 'convert']);
$router->get('/api/converter/status/{uuid}', [ConverterController::class, 'status']);
$router->get('/api/converter/download/{uuid}', [ConverterController::class, 'download']);

// Virtual Folders API
$router->get('/api/folders/list', [FolderController::class, 'list']);
$router->post('/api/folders/create', [FolderController::class, 'create']);
$router->post('/api/folders/rename', [FolderController::class, 'rename']);
$router->post('/api/folders/delete', [FolderController::class, 'delete']);
$router->post('/api/folders/move', [FolderController::class, 'moveFile']);
$router->get('/api/folders/contents/{id}', [FolderController::class, 'getContents']);
$router->get('/api/folders/search', [FolderController::class, 'search']);

// Dashboard API
$router->get('/api/dashboard/stats', [DashboardController::class, 'stats']);
$router->get('/api/dashboard/history', [DashboardController::class, 'history']);
$router->get('/api/dashboard/notifications', [DashboardController::class, 'notifications']);

// Admin API
$router->get('/api/admin/stats', [AdminController::class, 'stats']);
$router->get('/api/admin/users', [AdminController::class, 'users']);
$router->post('/api/admin/users/toggle', [AdminController::class, 'toggleUser']);
$router->get('/api/admin/settings', [AdminController::class, 'settings']);
$router->post('/api/admin/settings/update', [AdminController::class, 'updateSettings']);
$router->get('/api/admin/sources', [AdminController::class, 'sources']);
$router->post('/api/admin/sources/toggle', [AdminController::class, 'toggleSource']);
$router->get('/api/admin/logs', [AdminController::class, 'logs']);
$router->post('/api/admin/clean-storage', [AdminController::class, 'cleanStorage']);

// Dispatch
$router->dispatch($request);
