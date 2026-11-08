<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Services\FFmpegService;

class ConverterController
{
    private FFmpegService $service;

    public function __construct()
    {
        $this->service = new FFmpegService();
    }

    public function upload(Request $request): void
    {
        $file = $request->file('video_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file was uploaded or an upload error occurred.');
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $secConfig = require dirname(__DIR__, 2) . '/config/security.php';

        // Check size
        if ($file['size'] > $config['limits']['max_upload_size_bytes']) {
            Response::error('File size exceeds 500 MB limit.');
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $secConfig['allowed_upload_mimes'])) {
            Response::error("Unsupported file type ({$mime}). Please upload MP4, WebM, MKV, MOV or AVI videos.");
        }

        $uploadUuid = 'up_' . bin2hex(random_bytes(16));
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $destPath = $config['storage']['uploads'] . DIRECTORY_SEPARATOR . "{$uploadUuid}.{$ext}";

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Response::error('Failed to store uploaded file.');
        }

        Response::success([
            'upload_uuid'       => $uploadUuid,
            'original_filename' => $file['name'],
            'file_size'         => $file['size'],
            'file_path'         => $destPath,
            'mime'              => $mime
        ], 'File uploaded successfully. Choose your audio settings to begin conversion.');
    }

    public function convert(Request $request): void
    {
        $uploadUuid = $request->input('upload_uuid');
        $origName   = $request->input('original_filename', 'uploaded_video.mp4');
        $format     = $request->input('format', 'mp3');
        $bitrate    = $request->input('bitrate', '192k');

        if (empty($uploadUuid)) {
            Response::error('Uploaded file UUID is required.');
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $uploadsDir = $config['storage']['uploads'];

        // Find file
        $sourcePath = null;
        foreach (scandir($uploadsDir) as $item) {
            if (str_starts_with($item, $uploadUuid)) {
                $sourcePath = $uploadsDir . DIRECTORY_SEPARATOR . $item;
                break;
            }
        }

        if (!$sourcePath || !file_exists($sourcePath)) {
            Response::error('Uploaded source file not found or expired.');
        }

        $userId = Auth::id();
        $jobUuid = 'conv_' . bin2hex(random_bytes(16));

        $res = $this->service->startConversion($sourcePath, $origName, $format, $bitrate, $jobUuid, $userId);
        if (!$res['success']) {
            Response::error($res['error'] ?? 'Conversion startup failed.');
        }

        Response::success([
            'job_uuid' => $jobUuid,
            'status'   => 'processing'
        ], 'Conversion started.');
    }

    public function status(Request $request, array $params = []): void
    {
        $uuid = $params['uuid'] ?? $request->input('job_uuid');
        if (empty($uuid)) {
            Response::error('Job UUID required.');
        }

        $status = $this->service->getStatus($uuid);
        Response::success($status);
    }

    public function download(Request $request, array $params = []): void
    {
        $uuid = $params['uuid'] ?? $request->input('job_uuid');
        if (empty($uuid)) {
            Response::error('Job UUID required.');
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $convDir = $config['storage']['converted'];

        if (is_dir($convDir)) {
            foreach (scandir($convDir) as $item) {
                if (str_starts_with($item, $uuid)) {
                    $fullPath = $convDir . DIRECTORY_SEPARATOR . $item;
                    $cleanName = preg_replace('/^' . preg_quote($uuid, '/') . '_?/', '', $item);
                    $ext = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));
                    $mime = 'audio/mpeg';
                    if ($ext === 'wav') $mime = 'audio/wav';
                    elseif ($ext === 'ogg') $mime = 'audio/ogg';
                    elseif ($ext === 'm4a' || $ext === 'aac') $mime = 'audio/mp4';
                    elseif ($ext === 'flac') $mime = 'audio/flac';

                    Response::streamDownload($fullPath, $cleanName ?: 'audio_track.mp3', $mime);
                    return;
                }
            }
        }

        Response::error('Converted audio file not found.', 404);
    }
}
