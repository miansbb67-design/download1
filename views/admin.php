<?php
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Services/DownloadEngine.php';
require_once dirname(__DIR__) . '/app/Services/MediaEngine.php';

use App\Core\Database;
use App\Services\DownloadEngine;
use App\Services\MediaEngine;

$metrics = DownloadEngine::getMetrics();
$sidebar = DownloadEngine::getSidebarCounts();

$pythonPath = MediaEngine::getPythonBinary();
$ffmpegPath = MediaEngine::getFfmpegBinary();
$pythonAvailable = file_exists($pythonPath) || !empty(shell_exec('where python 2>nul'));
$ffmpegAvailable = file_exists($ffmpegPath) || !empty(shell_exec('where ffmpeg 2>nul'));

$storageDir = dirname(__DIR__) . '/storage/downloads';
$freeSpace = disk_free_space(dirname(__DIR__));
$freeSpaceFormatted = DownloadEngine::formatBytes($freeSpace ?: 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DownloadPro Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background-color: var(--bg-main); color: var(--text-primary); font-family: 'Plus Jakarta Sans', sans-serif; padding: 24px; }
    .admin-container { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
    .admin-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; }
    .admin-title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
    .info-box { background: #08101e; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 14px; }
    .info-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
    .info-val { font-size: 15px; font-weight: 700; color: #fff; margin-top: 4px; }
  </style>
</head>
<body>
  <div class="admin-container">
    <div style="display: flex; align-items: center; justify-content: space-between;">
      <h1 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
        <span style="color: var(--primary-cyan);">DownloadPro</span> Admin Management
      </h1>
      <a href="../index.php" class="btn-tool btn-primary-cyan" style="text-decoration: none;">&larr; Back to Dashboard</a>
    </div>

    <!-- System Diagnostics Card -->
    <div class="admin-card">
      <div class="admin-title">System & Media Engine Status</div>
      <div class="info-grid">
        <div class="info-box">
          <div class="info-label">PHP Environment</div>
          <div class="info-val"><?= PHP_VERSION ?> (<?= PHP_OS ?>)</div>
        </div>
        <div class="info-box">
          <div class="info-label">Python 3 Engine</div>
          <div class="info-val" style="color: <?= $pythonAvailable ? 'var(--emerald-green)' : 'var(--crimson-red)' ?>;">
            <?= $pythonAvailable ? '✓ Ready (UTF-8 Enforced)' : '✗ Not Detected' ?>
          </div>
        </div>
        <div class="info-box">
          <div class="info-label">FFmpeg 7.1 Media Encoder</div>
          <div class="info-val" style="color: <?= $ffmpegAvailable ? 'var(--emerald-green)' : 'var(--crimson-red)' ?>;">
            <?= $ffmpegAvailable ? '✓ Active & Configured' : '✗ Missing' ?>
          </div>
        </div>
        <div class="info-box">
          <div class="info-label">Free Storage Space</div>
          <div class="info-val"><?= $freeSpaceFormatted ?> Available</div>
        </div>
      </div>
    </div>

    <!-- Metrics Overview -->
    <div class="admin-card">
      <div class="admin-title">Queue & Historical Performance</div>
      <div class="info-grid">
        <div class="info-box">
          <div class="info-label">Total Downloads</div>
          <div class="info-val"><?= $metrics['total_downloads'] ?> Records</div>
        </div>
        <div class="info-box">
          <div class="info-label">Completed Files</div>
          <div class="info-val" style="color: var(--emerald-green);"><?= $metrics['completed'] ?> Finished</div>
        </div>
        <div class="info-box">
          <div class="info-label">Active / Queue</div>
          <div class="info-val" style="color: var(--amber-gold);"><?= $metrics['active_queue'] ?> Running</div>
        </div>
        <div class="info-box">
          <div class="info-label">Total Processed Data</div>
          <div class="info-val"><?= $metrics['total_data'] ?></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
