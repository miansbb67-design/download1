<?php
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';

use App\Core\Database;
use App\Core\Response;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::success([], 'OK');
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $body['action'] ?? '';
$taskId = $body['task_id'] ?? '';

if (empty($taskId)) {
    Response::error('Task ID required.');
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT file_path, filename FROM downloads WHERE task_id = :id");
$stmt->execute([':id' => $taskId]);
$dl = $stmt->fetch();

if (!$dl) {
    Response::error('Download record not found.');
}

$filePath = $dl['file_path'];

// If file path is not set or file does not exist, use default downloads dir
if (empty($filePath) || !file_exists($filePath)) {
    $setStmt = $db->query("SELECT value FROM settings WHERE key = 'download_folder'");
    $downFolder = $setStmt->fetch()['value'] ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'downloads');
    $filePath = $downFolder . DIRECTORY_SEPARATOR . $dl['filename'];
}

if ($action === 'open_file') {
    if (file_exists($filePath)) {
        pclose(popen('start "" ' . escapeshellarg($filePath), 'r'));
        Response::success([], 'File opened.');
    } else {
        Response::error('File not yet downloaded or has been moved from disk: ' . $filePath);
    }
} elseif ($action === 'open_folder') {
    if (file_exists($filePath)) {
        pclose(popen('explorer.exe /select,' . escapeshellarg($filePath), 'r'));
        Response::success([], 'Folder opened.');
    } else {
        $folder = dirname($filePath);
        if (is_dir($folder)) {
            pclose(popen('explorer.exe ' . escapeshellarg($folder), 'r'));
            Response::success([], 'Folder opened.');
        } else {
            Response::error('Download folder does not exist.');
        }
    }
} else {
    Response::error('Invalid open action.');
}
