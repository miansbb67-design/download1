<?php
use App\Core\Auth;
use App\Core\Security;

$currentUser = Auth::user();
$csrfToken = Security::getCsrfToken();
$pageTitle = $pageTitle ?? 'FlowFetch - Advanced Video & Audio Downloader';
$isDarkMode = $currentUser['dark_mode'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="High-speed online video and audio downloader inspired by IDM. Download 4K videos, complete playlists, and transcode media with FFmpeg.">
    <meta name="keywords" content="video downloader, audio downloader, playlist downloader, 4k video, ffmpeg converter, idm web">
    <link rel="icon" type="image/svg+xml" href="public/assets/img/logo.svg">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- App Styles -->
    <link rel="stylesheet" href="public/css/app.css">
    <link rel="stylesheet" href="public/css/dark-mode.css">

    <!-- GSAP for Smooth Fluid UI Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</head>
<body class="<?= $isDarkMode ? 'dark-mode' : '' ?>">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg sticky-top custom-navbar">
    <div class="container">
        <a class="brand-logo" href="./">
            <div class="brand-icon">
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
            <span>Flow<span class="text-primary-custom">Fetch</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars-staggered text-dark"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#video-downloader"><i class="fa-solid fa-video me-1"></i> Video</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#playlist-downloader"><i class="fa-solid fa-list-check me-1"></i> Playlist</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#audio-downloader"><i class="fa-solid fa-headphones me-1"></i> Audio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#converter-section"><i class="fa-solid fa-repeat me-1"></i> Converter</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#features"><i class="fa-solid fa-shield-halved me-1"></i> Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="./#faq"><i class="fa-solid fa-circle-question me-1"></i> FAQ</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <!-- Theme Toggle Button -->
                <button type="button" class="btn btn-light rounded-circle p-2 text-muted border-0 shadow-none" id="themeToggleBtn" title="Toggle Light / Dark mode">
                    <i class="fa-solid fa-moon"></i>
                </button>

                <?php if ($currentUser): ?>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-custom dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($currentUser['name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><a class="dropdown-item py-2" href="dashboard"><i class="fa-solid fa-gauge-high me-2 text-primary-custom"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2" href="dashboard#folders"><i class="fa-solid fa-folder me-2 text-primary-custom"></i> My Folders</a></li>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2" href="admin"><i class="fa-solid fa-user-shield me-2 text-danger"></i> Admin Center</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="FlowApp.logout()"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login" class="btn btn-outline-custom">Log In</a>
                    <a href="register" class="btn btn-primary-custom">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- System Notification Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="liveToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Notification message here</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
