<?php
namespace App\Core;

class Security
{
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeFilename(string $filename): string
    {
        // Strip null bytes and directory traversal characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
        $filename = str_replace(['..', '/', '\\'], '', $filename);
        $filename = preg_replace('/[?*:|"<>]/', '_', $filename);
        return trim($filename) ?: 'downloaded_file';
    }

    public static function validateUrl(string $url): bool
    {
        if (empty($url)) return false;
        
        // Ensure proper scheme
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower($scheme), ['http', 'https']);
    }

    public static function isSafeLocalPath(string $path, string $baseDir): bool
    {
        $realPath = realpath($path);
        $realBase = realpath($baseDir);

        if ($realPath === false || $realBase === false) {
            return false;
        }

        return str_starts_with($realPath, $realBase);
    }
}
