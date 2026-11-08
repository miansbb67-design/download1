<?php
namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): void
    {
        self::json(array_merge(['success' => false, 'error' => $message], $extra), $statusCode);
    }

    public static function success(array $data = [], string $message = ''): void
    {
        $payload = ['success' => true];
        if ($message !== '') $payload['message'] = $message;
        self::json(array_merge($payload, $data), 200);
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Stream file download with IDM-compatible HTTP Range (206 Partial Content) support
     */
    public static function streamDownload(string $filePath, string $downloadName, string $mimeType = 'application/octet-stream'): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            die("File not found.");
        }

        $size = filesize($filePath);
        $start = 0;
        $end = $size - 1;

        header("Content-Type: {$mimeType}");
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, must-revalidate, max-age=0');
        header('Pragma: public');

        // Check for HTTP Range header (Resumable downloads)
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int)$matches[1];
                if (!empty($matches[2])) {
                    $end = (int)$matches[2];
                }
            }

            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
            header("Content-Length: " . ($end - $start + 1));
        } else {
            header("Content-Length: {$size}");
        }

        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);

        $chunkSize = 1024 * 64; // 64KB buffer
        $cur = $start;

        while (!feof($fp) && $cur <= $end && connection_status() == 0) {
            $bytesToRead = min($chunkSize, ($end - $cur + 1));
            $data = fread($fp, $bytesToRead);
            echo $data;
            flush();
            $cur += strlen($data);
        }

        fclose($fp);
        exit;
    }
}

