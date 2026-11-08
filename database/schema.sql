-- ==========================================================
-- FlowFetch / IDM-Web Advanced Downloader Platform
-- Database Schema (MySQL 8.0+ / MariaDB 10.4+)
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS admin_settings;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS supported_sources;
DROP TABLE IF EXISTS conversion_jobs;
DROP TABLE IF EXISTS downloaded_files;
DROP TABLE IF EXISTS folders;
DROP TABLE IF EXISTS playlist_items;
DROP TABLE IF EXISTS playlists;
DROP TABLE IF EXISTS download_items;
DROP TABLE IF EXISTS download_jobs;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'guest') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    api_token VARCHAR(80) NULL UNIQUE,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Profiles Table
CREATE TABLE user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    preferred_format VARCHAR(20) DEFAULT 'mp4',
    preferred_quality VARCHAR(20) DEFAULT '1080p',
    max_concurrent_downloads INT UNSIGNED DEFAULT 3,
    dark_mode BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Folders Table
CREATE TABLE folders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    color VARCHAR(20) DEFAULT '#16A36A',
    icon VARCHAR(40) DEFAULT 'folder',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_folders_user (user_id),
    CONSTRAINT fk_folders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_folders_parent FOREIGN KEY (parent_id) REFERENCES folders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Download Jobs Table
CREATE TABLE download_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,
    source_url TEXT NOT NULL,
    media_title VARCHAR(255) NULL,
    media_type ENUM('video', 'audio', 'playlist') NOT NULL DEFAULT 'video',
    target_format VARCHAR(20) NOT NULL DEFAULT 'mp4',
    target_quality VARCHAR(20) NOT NULL DEFAULT '1080p',
    status ENUM('queued', 'processing', 'downloading', 'completed', 'failed', 'paused', 'cancelled') NOT NULL DEFAULT 'queued',
    progress DECIMAL(5,2) DEFAULT 0.00,
    speed VARCHAR(50) DEFAULT '0 KB/s',
    eta VARCHAR(50) DEFAULT '--:--',
    total_bytes BIGINT UNSIGNED DEFAULT 0,
    downloaded_bytes BIGINT UNSIGNED DEFAULT 0,
    segments_active INT UNSIGNED DEFAULT 1,
    folder_id BIGINT UNSIGNED NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_user (user_id),
    INDEX idx_jobs_status (status),
    INDEX idx_jobs_created (created_at),
    CONSTRAINT fk_jobs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_jobs_folder FOREIGN KEY (folder_id) REFERENCES folders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Download Items (Sub-streams or playlist video entries)
CREATE TABLE download_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    item_index INT UNSIGNED DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    source_url TEXT NOT NULL,
    stream_url TEXT NULL,
    format VARCHAR(20) NOT NULL DEFAULT 'mp4',
    quality VARCHAR(20) NOT NULL DEFAULT '720p',
    resolution VARCHAR(30) NULL,
    file_size BIGINT UNSIGNED DEFAULT 0,
    progress DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('pending', 'downloading', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_items_job (job_id),
    CONSTRAINT fk_items_job FOREIGN KEY (job_id) REFERENCES download_jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Playlists Table
CREATE TABLE playlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    playlist_uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,
    source_url TEXT NOT NULL,
    title VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    total_items INT UNSIGNED DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'partial', 'failed') DEFAULT 'pending',
    folder_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_playlists_user (user_id),
    CONSTRAINT fk_playlists_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_playlists_folder FOREIGN KEY (folder_id) REFERENCES folders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Playlist Items Table
CREATE TABLE playlist_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    playlist_id BIGINT UNSIGNED NOT NULL,
    video_id VARCHAR(100) NOT NULL,
    item_index INT UNSIGNED DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    duration VARCHAR(30) DEFAULT '00:00',
    thumbnail_url VARCHAR(500) NULL,
    selected BOOLEAN DEFAULT TRUE,
    selected_quality VARCHAR(20) DEFAULT '720p',
    status ENUM('queued', 'downloading', 'completed', 'failed', 'skipped') DEFAULT 'queued',
    download_job_id BIGINT UNSIGNED NULL,
    file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pl_items_playlist (playlist_id),
    CONSTRAINT fk_pl_items_playlist FOREIGN KEY (playlist_id) REFERENCES playlists (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Downloaded Files Table (Catalog of completed files on disk)
CREATE TABLE downloaded_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    folder_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    mime_type VARCHAR(100) NOT NULL,
    media_type ENUM('video', 'audio', 'document', 'archive') NOT NULL DEFAULT 'video',
    duration_seconds INT UNSIGNED DEFAULT 0,
    download_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_files_user (user_id),
    INDEX idx_files_folder (folder_id),
    CONSTRAINT fk_files_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_files_folder FOREIGN KEY (folder_id) REFERENCES folders (id) ON DELETE SET NULL,
    CONSTRAINT fk_files_job FOREIGN KEY (job_id) REFERENCES download_jobs (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Conversion Jobs Table (FFmpeg Video to Audio)
CREATE TABLE conversion_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,
    original_filename VARCHAR(255) NOT NULL,
    source_file_path VARCHAR(255) NOT NULL,
    target_format ENUM('mp3', 'wav', 'aac', 'm4a', 'flac', 'ogg') NOT NULL DEFAULT 'mp3',
    target_bitrate VARCHAR(20) NOT NULL DEFAULT '192k',
    status ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    progress DECIMAL(5,2) DEFAULT 0.00,
    output_filename VARCHAR(255) NULL,
    output_file_path VARCHAR(255) NULL,
    output_file_size BIGINT UNSIGNED DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_conv_user (user_id),
    INDEX idx_conv_status (status),
    CONSTRAINT fk_conv_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Supported Sources Table
CREATE TABLE supported_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    domain VARCHAR(150) NOT NULL UNIQUE,
    icon VARCHAR(60) DEFAULT 'globe',
    is_active BOOLEAN DEFAULT TRUE,
    max_resolution VARCHAR(20) DEFAULT '2160p',
    allow_audio_extract BOOLEAN DEFAULT TRUE,
    requires_auth BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. User Settings Table
CREATE TABLE user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_setting (user_id, setting_key),
    CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Notifications Table
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Admin Settings Table
CREATE TABLE admin_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_settings_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. System Logs Table
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level ENUM('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    category VARCHAR(60) NOT NULL DEFAULT 'general',
    message TEXT NOT NULL,
    context JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_level (level),
    INDEX idx_logs_category (category),
    INDEX idx_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
