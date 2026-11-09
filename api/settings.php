<?php
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';

use App\Core\Database;
use App\Core\Response;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    Response::success([], 'OK');
}

$db = Database::getConnection();

if ($method === 'GET') {
    $stmt = $db->query("SELECT key, value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
    Response::success(['settings' => $settings]);
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");

    foreach ($body as $k => $v) {
        if (is_scalar($v)) {
            $stmt->execute([':key' => $k, ':value' => (string)$v]);
        }
    }

    Response::success([], 'Settings updated successfully.');
}
