<?php
/**
 * Seed IDM Sample items matching user's screenshot
 */

require_once __DIR__ . '/../config/database.php';
$dbConfig = require __DIR__ . '/../config/database.php';

$dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$samples = [
    [
        'title' => 'Dil_Diyan_Gallan___Official_Video___.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '1080p',
        'size' => 22334668, // 21.3 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample1'
    ],
    [
        'title' => 'Bom_Diggy_Diggy_8K_Video___Zack_Knl.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '2160p',
        'size' => 40055603, // 38.2 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample2'
    ],
    [
        'title' => 'Dil_Diyan_Gallan___Official_Video___.mp3',
        'type' => 'audio',
        'format' => 'mp3',
        'quality' => '320k',
        'size' => 6815744, // 6.5 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample3'
    ],
    [
        'title' => 'I_Tried_Instagram_Street_Food_Hacks.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '1080p',
        'size' => 78852915, // 75.2 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample4'
    ],
    [
        'title' => 'Aankhein Khuli Song _ Mohabbatein _ Shah Rukh Khan.mp3',
        'type' => 'audio',
        'format' => 'mp3',
        'quality' => '320k',
        'size' => 21390950, // 20.4 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample5'
    ],
    [
        'title' => 'Video_by_aymanwilliam_acc.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '720p',
        'size' => 1992294, // 1.9 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample6'
    ],
    [
        'title' => '(Official 1080p HD) Ki Samjhaiye ft. Amrinder Gill.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '1080p',
        'size' => 8703180, // 8.3 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample7'
    ],
    [
        'title' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp3',
        'type' => 'audio',
        'format' => 'mp3',
        'quality' => '320k',
        'size' => 6081740, // 5.8 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample8'
    ],
    [
        'title' => 'Kalyani___Aasa_Kooda___Majboor___Suj.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '720p',
        'size' => 19713228, // 18.8 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample9'
    ],
    [
        'title' => 'song.mp4',
        'type' => 'video',
        'format' => 'mp4',
        'quality' => '480p',
        'size' => 2411724, // 2.3 MB
        'status' => 'completed',
        'progress' => 100.0,
        'url' => 'https://www.youtube.com/watch?v=sample10'
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO download_jobs 
    (job_uuid, user_id, source_url, media_title, media_type, target_format, target_quality, status, progress, speed, eta, total_bytes, downloaded_bytes, folder_id, created_at)
    VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, '0 KB/s', '00:00', ?, ?, 1, NOW() - INTERVAL ? MINUTE)
");

foreach ($samples as $i => $s) {
    $uuid = 'idm_' . bin2hex(random_bytes(14));
    $stmt->execute([
        $uuid,
        $s['url'],
        $s['title'],
        $s['type'],
        $s['format'],
        $s['quality'],
        $s['status'],
        $s['progress'],
        $s['size'],
        $s['size'],
        (10 - $i) * 5
    ]);
}

echo "Seeded " . count($samples) . " sample IDM downloads successfully.\n";

