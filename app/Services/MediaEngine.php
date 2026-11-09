<?php
namespace App\Services;

use App\Core\Security;

class MediaEngine
{
    private static string $pythonBin = 'C:\\Users\\FineTechShop\\python311\\python.exe';
    private static string $ffmpegBin = 'C:\\Users\\FineTechShop\\python311\\Scripts\\ffmpeg.exe';

    public static function getPythonBinary(): string
    {
        if (file_exists(self::$pythonBin)) {
            return self::$pythonBin;
        }
        return 'python';
    }

    public static function getFfmpegBinary(): string
    {
        if (file_exists(self::$ffmpegBin)) {
            return self::$ffmpegBin;
        }
        return 'ffmpeg';
    }

    public static function probeUrl(string $url): array
    {
        if (!Security::validateUrl($url)) {
            return ['success' => false, 'error' => 'Invalid or unpermitted URL format.'];
        }

        $scriptPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'extractor.py';
        $python = self::getPythonBinary();

        $descriptors = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        $cmd = '"' . $python . '" "' . $scriptPath . '" ' . escapeshellarg($url);
        
        $env = array_merge($_ENV, [
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1',
            'LANG' => 'en_US.UTF-8'
        ]);

        $process = proc_open($cmd, $descriptors, $pipes, dirname($scriptPath), $env);

        if (!is_resource($process)) {
            return ['success' => false, 'error' => 'Failed to initialize Python media probe process.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        $jsonStart = strpos($stdout, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($stdout, $jsonStart);
            $parsed = json_decode($jsonStr, true);
            if ($parsed && isset($parsed['success'])) {
                return $parsed;
            }
        }

        if (!empty($stderr)) {
            return ['success' => false, 'error' => 'Media engine probe error: ' . trim($stderr)];
        }

        return ['success' => false, 'error' => 'Unable to read media metadata from target URL.'];
    }

    public static function extractAudioLocal(string $inputFile, string $format = 'mp3', string $bitrate = '320k', string $outputDir = ''): array
    {
        if (!file_exists($inputFile)) {
            return ['success' => false, 'error' => 'Target video file does not exist on disk.'];
        }

        $scriptPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'audio_extractor.py';
        $python = self::getPythonBinary();

        $cmd = '"' . $python . '" "' . $scriptPath . '" ' . 
               escapeshellarg($inputFile) . ' ' . 
               escapeshellarg($format) . ' ' . 
               escapeshellarg($bitrate) . ' ' . 
               escapeshellarg($outputDir);

        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $env = array_merge($_ENV, [
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1'
        ]);

        $process = proc_open($cmd, $descriptors, $pipes, dirname($scriptPath), $env);

        if (!is_resource($process)) {
            return ['success' => false, 'error' => 'Failed to start FFmpeg audio extraction process.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $jsonStart = strpos($stdout, '{');
        if ($jsonStart !== false) {
            $parsed = json_decode(substr($stdout, $jsonStart), true);
            if ($parsed && isset($parsed['success'])) {
                return $parsed;
            }
        }

        return ['success' => false, 'error' => 'Audio extraction failed: ' . trim($stderr)];
    }
}
