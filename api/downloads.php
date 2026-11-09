<?php
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Services/MediaEngine.php';
require_once dirname(__DIR__) . '/app/Services/DownloadEngine.php';

use App\Core\Response;
use App\Core\Security;
use App\Services\DownloadEngine;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    Response::success([], 'OK');
}

if ($method === 'GET') {
    $category = $_GET['category'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    // Check updates for any downloading tasks
    $downloads = DownloadEngine::getDownloads($category, $status, $search);
    foreach ($downloads as $dl) {
        if ($dl['status'] === 'downloading' || $dl['status'] === 'pending') {
            DownloadEngine::updateTaskProgress($dl['task_id']);
        }
    }

    // Refresh downloads after updating progress
    $downloads = DownloadEngine::getDownloads($category, $status, $search);
    $metrics = DownloadEngine::getMetrics();
    $sidebar = DownloadEngine::getSidebarCounts();

    Response::success([
        'downloads' => $downloads,
        'metrics' => $metrics,
        'sidebar' => $sidebar
    ]);
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $body['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $url = $body['url'] ?? '';
            if (empty($url)) {
                Response::error('URL is required.');
            }
            $result = DownloadEngine::createDownload($body);
            Response::success($result);
            break;

        case 'pause':
            $taskId = $body['task_id'] ?? '';
            if (empty($taskId)) Response::error('Task ID is required');
            DownloadEngine::pauseDownload($taskId);
            Response::success([], 'Download paused.');
            break;

        case 'resume':
            $taskId = $body['task_id'] ?? '';
            if (empty($taskId)) Response::error('Task ID is required');
            DownloadEngine::resumeDownload($taskId);
            Response::success([], 'Download resumed.');
            break;

        case 'delete':
            $taskId = $body['task_id'] ?? '';
            $deleteDisk = !empty($body['delete_file']);
            if (empty($taskId)) Response::error('Task ID is required');
            DownloadEngine::deleteDownload($taskId, $deleteDisk);
            Response::success([], 'Download removed.');
            break;

        case 'pause_all':
            DownloadEngine::pauseAll();
            Response::success([], 'All active downloads paused.');
            break;

        case 'resume_all':
            DownloadEngine::resumeAll();
            Response::success([], 'All paused downloads resumed.');
            break;

        case 'stop_all':
            DownloadEngine::stopAll();
            Response::success([], 'All downloads stopped.');
            break;

        case 'clean_completed':
            DownloadEngine::cleanCompleted();
            Response::success([], 'Completed downloads cleaned.');
            break;

        default:
            Response::error('Invalid action requested.');
    }
}
