<?php
/**
 * Application Core Configuration
 */

return [
    'name'       => getenv('APP_NAME') ?: 'FlowFetch Pro',
    'env'        => getenv('APP_ENV') ?: 'production',
    'debug'      => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'url'        => getenv('APP_URL') ?: 'http://localhost/download1',
    'timezone'   => 'UTC',
    'locale'     => 'en',
    
    // Media tools
    'python_bin' => getenv('PYTHON_PATH') ?: 'python',
    'ffmpeg_bin' => getenv('FFMPEG_PATH') ?: 'ffmpeg',
    'ytdlp_bin'  => getenv('YTDLP_PATH') ?: 'yt-dlp',
    
    // Storage paths
    'storage'    => [
        'root'      => __DIR__ . '/../storage',
        'downloads' => __DIR__ . '/../storage/downloads',
        'uploads'   => __DIR__ . '/../storage/uploads',
        'converted' => __DIR__ . '/../storage/converted',
        'temp'      => __DIR__ . '/../storage/temp',
        'logs'      => __DIR__ . '/../storage/logs',
    ],
    
    // Limits
    'limits'     => [
        'max_concurrent_downloads' => (int)(getenv('MAX_CONCURRENT_DOWNLOADS') ?: 4),
        'max_upload_size_bytes'    => 500 * 1024 * 1024, // 500 MB
        'retention_hours'          => (int)(getenv('RETENTION_HOURS') ?: 48),
        'rate_limit_per_minute'    => (int)(getenv('RATE_LIMIT_PER_MINUTE') ?: 60),
    ]
];

