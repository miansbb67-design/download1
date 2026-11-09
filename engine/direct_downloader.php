<?php
/**
 * Direct HTTP/HTTPS Range Chunked Downloader Worker
 * Runs as a background CLI process to download direct files with pause/resume support.
 */

if ($argc < 5) {
    die("Usage: direct_downloader.php <task_id> <url> <file_path> <progress_file>\n");
}

$taskId = $argv[1];
$url = $argv[2];
$filePath = $argv[3];
$progressFile = $argv[4];

$dir = dirname($filePath);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Check existing downloaded size for resume
$downloadedBytes = 0;
if (file_exists($filePath)) {
    $downloadedBytes = filesize($filePath);
}

$fileHandle = fopen($filePath, $downloadedBytes > 0 ? 'ab' : 'wb');
if (!$fileHandle) {
    file_put_contents($progressFile, json_encode([
        'status' => 'failed',
        'error' => 'Unable to open target file for writing.'
    ]));
    exit(1);
}

// Get total remote file size
$chHead = curl_init($url);
curl_setopt($chHead, CURLOPT_NOBODY, true);
curl_setopt($chHead, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chHead, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($chHead, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($chHead, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($chHead, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DownloadPro/1.0');
curl_exec($chHead);
$totalRemoteBytes = (int)curl_getinfo($chHead, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
curl_close($chHead);

if ($totalRemoteBytes > 0 && $downloadedBytes >= $totalRemoteBytes) {
    fclose($fileHandle);
    file_put_contents($progressFile, json_encode([
        'status' => 'completed',
        'percent' => 100,
        'downloaded_bytes' => $totalRemoteBytes,
        'total_bytes' => $totalRemoteBytes,
        'file_path' => $filePath
    ]));
    exit(0);
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $fileHandle);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DownloadPro/1.0');
curl_setopt($ch, CURLOPT_NOPROGRESS, false);

if ($downloadedBytes > 0) {
    curl_setopt($ch, CURLOPT_RANGE, "$downloadedBytes-");
}

$startTime = microtime(true);
$lastUpdate = 0;
$lastBytes = $downloadedBytes;

curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use (
    $downloadedBytes, $totalRemoteBytes, $progressFile, &$startTime, &$lastUpdate, &$lastBytes, $filePath
) {
    $now = microtime(true);
    if ($now - $lastUpdate >= 0.5) {
        $lastUpdate = $now;
        $currentDownloaded = $downloadedBytes + $downloaded;
        $effectiveTotal = ($totalRemoteBytes > 0) ? $totalRemoteBytes : ($downloadedBytes + $downloadSize);
        $percent = ($effectiveTotal > 0) ? round(($currentDownloaded / $effectiveTotal) * 100, 1) : 0;
        
        $elapsed = $now - $startTime;
        $speed = ($elapsed > 0) ? round(($downloaded) / $elapsed, 1) : 0;
        
        $eta = 0;
        if ($speed > 0 && $effectiveTotal > $currentDownloaded) {
            $eta = (int)(($effectiveTotal - $currentDownloaded) / $speed);
        }

        $progressData = [
            'status' => 'downloading',
            'percent' => $percent,
            'downloaded_bytes' => $currentDownloaded,
            'total_bytes' => $effectiveTotal,
            'speed_bytes_sec' => $speed,
            'eta_seconds' => $eta
        ];

        @file_put_contents($progressFile, json_encode($progressData));
    }
});

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
fclose($fileHandle);

if ($result === false || ($httpCode >= 400 && $httpCode != 416)) {
    file_put_contents($progressFile, json_encode([
        'status' => 'failed',
        'error' => $error ?: "HTTP error $httpCode"
    ]));
    exit(1);
}

$finalSize = file_exists($filePath) ? filesize($filePath) : $totalRemoteBytes;

file_put_contents($progressFile, json_encode([
    'status' => 'completed',
    'percent' => 100,
    'downloaded_bytes' => $finalSize,
    'total_bytes' => $finalSize,
    'file_path' => $filePath
]));
