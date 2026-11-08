<?php
namespace App\Core;

class Security
{
    /**
     * Prevent Server-Side Request Forgery (SSRF)
     */
    public static function isSafeUrl(string $url): array
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return ['safe' => false, 'error' => 'Malformed URL.'];
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            return ['safe' => false, 'error' => 'Only HTTP and HTTPS protocols are permitted.'];
        }

        $host = strtolower($parts['host']);

        // Forbidden hostnames
        $forbiddenHosts = [
            'localhost', '127.0.0.1', '::1', '0.0.0.0',
            '169.254.169.254', 'metadata.google.internal', 'instance-data'
        ];
        if (in_array($host, $forbiddenHosts)) {
            return ['safe' => false, 'error' => 'Access to internal or local addresses is prohibited.'];
        }

        // Resolve DNS and check if IP is private/reserved
        $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (!empty($ips)) {
            foreach ($ips as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if ($ip && self::isPrivateIp($ip)) {
                    return ['safe' => false, 'error' => "Resolved IP ({$ip}) is within a restricted private network."];
                }
            }
        }

        return ['safe' => true, 'error' => null];
    }

    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * CSRF protection
     */
    public static function getCsrfToken(): string
    {
        Auth::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        Auth::init();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiter by Client IP
     */
    public static function checkRateLimit(string $ip, int $maxRequests = 60, int $windowSeconds = 60): bool
    {
        $cacheDir = dirname(__DIR__, 2) . '/storage/temp/ratelimit';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $file = $cacheDir . '/' . md5($ip) . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(@file_get_contents($file) ?: '{}', true);
            if (!empty($data) && ($now - $data['start']) < $windowSeconds) {
                if ($data['count'] >= $maxRequests) {
                    return false; // Rate limit exceeded
                }
                $data['count']++;
                @file_put_contents($file, json_encode($data));
                return true;
            }
        }

        // Initialize window
        @file_put_contents($file, json_encode(['start' => $now, 'count' => 1]));
        return true;
    }

    /**
     * Path traversal sanitize
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Strip out control chars, path delimiters, and null bytes
        $clean = preg_replace('/[^\w\s\d\-_~,;:\[\]\(\).]/', '', $filename);
        $clean = basename($clean);
        return trim($clean) ?: 'media_download';
    }

    /**
     * Sanitize output string
     */
    public static function clean(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
