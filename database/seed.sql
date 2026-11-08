-- ==========================================================
-- FlowFetch Seed Data
-- ==========================================================

-- Admin user (Password: Admin@123456)
-- Demo user (Password: User@123456)

INSERT INTO users (id, name, email, password_hash, role, status, api_token, email_verified_at) VALUES
(1, 'System Administrator', 'admin@download1.local', '$2y$10$73I2ZfgH0n7xO/9u6G1/.u.vnUMi0e.WgHIsPTo.FfL4xDaVH5GKy', 'admin', 'active', 'adm_tok_8943729847293847293847', NOW()),
(2, 'Demo User', 'user@download1.local', '$2y$10$gqHS4et9zW48ihdy7Dggt.FDfLNbeRtLpCnDZWxZ64Jt57HnU0bVW', 'user', 'active', 'usr_tok_3920194820394820394820', NOW());

INSERT INTO user_profiles (user_id, avatar, bio, preferred_format, preferred_quality, max_concurrent_downloads, dark_mode) VALUES
(1, 'assets/img/avatar-admin.png', 'Lead System Administrator & DevOps Engineer', 'mp4', '2160p', 5, FALSE),
(2, 'assets/img/avatar-user.png', 'Creative video editor and audiophile', 'mp3', '1080p', 3, FALSE);

-- Default Virtual Folders
INSERT INTO folders (id, user_id, name, slug, color, icon) VALUES
(1, 1, '4K Ultra HD Videos', '4k-videos', '#16A36A', 'film'),
(2, 1, 'Podcasts & Music', 'music-audio', '#27C985', 'music'),
(3, 1, 'Playlists Archive', 'playlists', '#059669', 'list'),
(4, 2, 'My Downloads', 'my-downloads', '#16A36A', 'download'),
(5, 2, 'Audio Tracks', 'audio-tracks', '#27C985', 'headphones');

-- Supported Sources
INSERT INTO supported_sources (name, domain, icon, is_active, max_resolution, allow_audio_extract, requires_auth) VALUES
('YouTube', 'youtube.com', 'youtube', 1, '2160p', 1, 0),
('Vimeo', 'vimeo.com', 'vimeo', 1, '2160p', 1, 0),
('SoundCloud', 'soundcloud.com', 'soundcloud', 1, 'audio-only', 1, 0),
('TikTok', 'tiktok.com', 'tiktok', 1, '1080p', 1, 0),
('Dailymotion', 'dailymotion.com', 'dailymotion', 1, '1080p', 1, 0),
('Twitch Clips', 'twitch.tv', 'twitch', 1, '1080p', 1, 0),
('Direct MP4 / WebM', 'direct-stream', 'link', 1, '2160p', 1, 0),
('Podcast RSS / Audio', 'podcast-rss', 'rss', 1, 'audio-only', 1, 0);

-- Admin Global Settings
INSERT INTO admin_settings (setting_group, setting_key, setting_value, description) VALUES
('general', 'site_name', 'FlowFetch Pro', 'Application title displayed across views and meta tags'),
('general', 'site_tagline', 'High-Performance Video & Audio Extraction Suite', 'Subtitle on hero header'),
('general', 'allow_guest_downloads', '1', 'Permit unregistered visitors to download single media with rate limits'),
('limits', 'max_video_resolution', '2160p', 'Maximum resolution allowed globally (2160p, 1440p, 1080p, 720p)'),
('limits', 'guest_daily_limit', '5', 'Number of downloads allowed per guest IP per 24 hours'),
('limits', 'user_daily_limit', '50', 'Number of downloads allowed per registered user per 24 hours'),
('limits', 'max_upload_size_mb', '500', 'Maximum video upload file size in Megabytes for conversion'),
('storage', 'retention_hours', '48', 'Hours to keep completed downloaded files before automatic cleanup'),
('storage', 'cleanup_enabled', '1', 'Enable automated cron or request-triggered temp cleanup'),
('security', 'rate_limit_per_minute', '20', 'Max API requests per client IP per minute'),
('security', 'block_private_ips', '1', 'Prevent SSRF by rejecting private RFC 1918 and loopback IPs'),
('converter', 'allowed_audio_formats', 'mp3,wav,aac,m4a,flac,ogg', 'Permitted output audio formats for FFmpeg conversion'),
('converter', 'allowed_bitrates', '128k,192k,256k,320k', 'Permitted bitrates for audio conversion');

-- Initial System Notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(1, 'System Initialized', 'FlowFetch production engine initialized with FFmpeg 7.1 and yt-dlp stream prober.', 'success'),
(2, 'Welcome to FlowFetch', 'You can now extract 4K videos, playlists and convert video to MP3 with IDM-speed.', 'info');

-- Initial System Logs
INSERT INTO system_logs (level, category, message, context, ip_address) VALUES
('INFO', 'system', 'Database migrations and seeders executed successfully.', '{"env": "production"}', '127.0.0.1');
