<?php
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Services/MediaEngine.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Response;
use App\Core\Database;
use App\Services\MediaEngine;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::success([], 'OK');
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$inputFile = trim($body['input_file'] ?? '');
$format = trim($body['format'] ?? 'mp3');
$bitrate = trim($body['bitrate'] ?? '320k');

if (empty($inputFile)) {
    Response::error('Please select or specify a video file to extract audio from.');
}

if (!file_exists($inputFile)) {
    Response::error('The specified video file does not exist on disk.');
}

$db = Database::getConnection();
$setStmt = $db->query("SELECT value FROM settings WHERE key = 'download_folder'");
$downFolder = $setStmt->fetch()['value'] ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'downloads');

$result = MediaEngine::extractAudioLocal($inputFile, $format, $bitrate, $downFolder);

if (!empty($result['success'])) {
    // Record into downloads table as completed audio item
    $taskId = 'ext_' . bin2hex(random_bytes(6));
    $stmt = $db->prepare("
        INSERT INTO downloads 
        (task_id, url, title, filename, file_path, file_size, downloaded_bytes, progress, speed, eta, status, category, file_type, duration)
        VALUES 
        (:task_id, :url, :title, :filename, :file_path, :file_size, :downloaded_bytes, 100, '-', '-', 'completed', 'Music & Audio', 'audio', 'Audio Extraction')
    ");

    $stmt->execute([
        ':task_id' => $taskId,
        ':url' => 'local://' . $result['filename'],
        ':title' => $result['filename'],
        ':filename' => $result['filename'],
        ':file_path' => $result['output_file'],
        ':file_size' => $result['size_bytes'],
        ':downloaded_bytes' => $result['size_bytes']
    ]);

    Response::success($result, 'Audio extracted successfully.');
} else {
    Response::error($result['error'] ?? 'Audio extraction failed.');
}
