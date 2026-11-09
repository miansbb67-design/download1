<?php
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Services/MediaEngine.php';

use App\Core\Response;
use App\Core\Security;
use App\Services\MediaEngine;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::success([], 'OK');
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$url = trim($body['url'] ?? '');

if (empty($url)) {
    Response::error('Please provide a media or file URL to analyze.');
}

if (!Security::validateUrl($url)) {
    Response::error('Invalid URL format. Please enter a valid HTTP or HTTPS URL.');
}

$result = MediaEngine::probeUrl($url);

if (!empty($result['success'])) {
    Response::success($result);
} else {
    Response::error($result['error'] ?? 'Failed to parse media metadata from source.');
}
