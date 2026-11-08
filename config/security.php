<?php
/**
 * Security & Protection Configuration
 */

return [
    // SSRF Safeguards
    'block_private_ips'   => filter_var(getenv('BLOCK_PRIVATE_IPS') ?: true, FILTER_VALIDATE_BOOLEAN),
    'allowed_protocols'   => ['http', 'https'],
    'blocked_hosts'       => [
        'localhost',
        '127.0.0.1',
        '::1',
        '0.0.0.0',
        '169.254.169.254', // Cloud metadata
        'metadata.google.internal'
    ],
    
    // CSRF Protection
    'csrf_token_name'     => 'csrf_token',
    'csrf_header_name'    => 'X-CSRF-TOKEN',
    
    // Media Converters
    'allowed_conversion_formats' => ['mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg'],
    'allowed_bitrates'           => ['128k', '192k', '256k', '320k'],
    'allowed_upload_mimes'       => [
        'video/mp4',
        'video/webm',
        'video/x-matroska',
        'video/quicktime',
        'video/x-msvideo',
        'video/mpeg',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/ogg'
    ]
];

