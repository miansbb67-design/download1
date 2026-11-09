<?php
namespace App\Core;

class Response
{
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, $extra = []): void
    {
        self::json(array_merge([
            'success' => false,
            'error' => $message
        ], $extra), $statusCode);
    }

    public static function success($data = [], string $message = ''): void
    {
        $payload = ['success' => true];
        if ($message !== '') {
            $payload['message'] = $message;
        }
        if (is_array($data)) {
            $payload = array_merge($payload, $data);
        } else {
            $payload['data'] = $data;
        }
        self::json($payload, 200);
    }
}
