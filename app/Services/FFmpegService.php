<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class FFmpegService
{
    private string $pythonBin;
    private string $ffmpegBin;
    private string $converterScript;
    private string $uploadsDir;
    private string $convertedDir;
    private string $tempDir;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->pythonBin = $config['python_bin'] ?: 'python';
        $this->ffmpegBin = $config['ffmpeg_bin'] ?: 'ffmpeg';
        $baseDir = dirname(__DIR__, 2);

        $this->converterScript = $baseDir . '/engine/converter.py';
        $this->uploadsDir = $config['storage']['uploads'];
        $this->convertedDir = $config['storage']['converted'];
        $this->tempDir = $config['storage']['temp'];
    }

    /**
     * Start async video-to-audio conversion
     */
    public function startConversion(string $inputFilePath, string $originalFilename, string $targetFormat, string $targetBitrate, string $jobUuid, ?int $userId = null): array
    {
        $allowedFormats = ['mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg'];
        $targetFormat = strtolower($targetFormat);
        if (!in_array($targetFormat, $allowedFormats)) {
            return ['success' => false, 'error' => "Unsupported target format: {$targetFormat}"];
        }

        $allowedBitrates = ['128k', '192k', '256k', '320k'];
        if (!in_array($targetBitrate, $allowedBitrates)) {
            $targetBitrate = '192k';
        }

        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        $outputFilename = "{$jobUuid}_{$baseName}.{$targetFormat}";
        $outputFilePath = $this->convertedDir . DIRECTORY_SEPARATOR . $outputFilename;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO conversion_jobs 
            (job_uuid, user_id, original_filename, source_file_path, target_format, target_bitrate, status, progress, output_filename, output_file_path) 
            VALUES (?, ?, ?, ?, ?, ?, 'processing', 2.0, ?, ?)");
        $stmt->execute([
            $jobUuid, $userId, $originalFilename, $inputFilePath, $targetFormat, $targetBitrate, $outputFilename, $outputFilePath
        ]);

        $statusFile = $this->tempDir . "/{$jobUuid}.json";
        @file_put_contents($statusFile, json_encode([
            'status'   => 'processing',
            'progress' => 2.0,
            'speed'    => 'Starting FFmpeg transcode...',
            'eta'      => 'Calculating...'
        ]));

        $cmd = sprintf(
            '%s %s --input %s --output %s --format %s --bitrate %s --job-id %s --ffmpeg-bin %s',
            escapeshellcmd($this->pythonBin),
            escapeshellarg($this->converterScript),
            escapeshellarg($inputFilePath),
            escapeshellarg($outputFilePath),
            escapeshellarg($targetFormat),
            escapeshellarg($targetBitrate),
            escapeshellarg($jobUuid),
            escapeshellarg($this->ffmpegBin)
        );

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B cmd /C \"{$cmd} > nul 2>&1\"", "r"));
        } else {
            exec("{$cmd} > /dev/null 2>&1 &");
        }

        return [
            'success'   => true,
            'job_uuid'  => $jobUuid,
            'status'    => 'processing'
        ];
    }

    /**
     * Get conversion job progress
     */
    public function getStatus(string $jobUuid): array
    {
        $statusFile = $this->tempDir . "/{$jobUuid}.json";
        if (file_exists($statusFile)) {
            $data = json_decode(@file_get_contents($statusFile), true);
            if (is_array($data)) {
                if (!empty($data['status']) && $data['status'] === 'completed') {
                    $this->finalizeConversion($jobUuid, $data);
                }
                return $data;
            }
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM conversion_jobs WHERE job_uuid = ? LIMIT 1");
        $stmt->execute([$jobUuid]);
        $job = $stmt->fetch();

        if ($job) {
            return [
                'status'           => $job['status'],
                'progress'         => (float)$job['progress'],
                'output_filename'  => $job['output_filename'],
                'output_file_size' => (int)$job['output_file_size'],
                'error'            => $job['error_message']
            ];
        }

        return ['status' => 'not_found', 'progress' => 0];
    }

    private function finalizeConversion(string $jobUuid, array $data): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM conversion_jobs WHERE job_uuid = ? LIMIT 1");
            $stmt->execute([$jobUuid]);
            $job = $stmt->fetch();
            if (!$job || $job['status'] === 'completed') return;

            $outputFile = $data['output_file'] ?? $job['output_file_path'];
            $fileSize = file_exists($outputFile) ? filesize($outputFile) : 0;

            $stmtUp = $pdo->prepare("UPDATE conversion_jobs SET status = 'completed', progress = 100.0, output_file_size = ? WHERE job_uuid = ?");
            $stmtUp->execute([$fileSize, $jobUuid]);

            // Clean up original uploaded video to preserve disk space
            if (file_exists($job['source_file_path'])) {
                @unlink($job['source_file_path']);
            }
        } catch (\Exception $e) {}
    }
}

