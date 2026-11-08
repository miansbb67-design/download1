<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Services\MediaEngine;

class MediaController
{
    private MediaEngine $engine;

    public function __construct()
    {
        $this->engine = new MediaEngine();
    }

    public function inspect(Request $request): void
    {
        $ip = $request->getClientIp();
        if (!Security::checkRateLimit($ip, 30, 60)) {
            Response::error('Rate limit exceeded. Please wait a minute before analyzing more URLs.', 429);
        }

        $url = trim($request->input('url', ''));
        if (empty($url)) {
            Response::error('Please provide a valid media URL.');
        }

        // Validate SSRF
        $safety = Security::isSafeUrl($url);
        if (!$safety['safe']) {
            Response::error($safety['error']);
        }

        // Check if guest downloads are allowed
        if (!Auth::check()) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'allow_guest_downloads' LIMIT 1");
            $stmt->execute();
            $setting = $stmt->fetch();
            if ($setting && $setting['setting_value'] === '0') {
                Response::error('Guest downloads are currently disabled. Please log in or create an account to proceed.', 403);
            }
        }

        $result = $this->engine->inspectUrl($url);
        if (!$result['success']) {
            Response::error($result['error'] ?? 'Could not extract streams from this URL. Please verify the link is accessible.');
        }

        Response::success([
            'is_playlist' => $result['is_playlist'] ?? false,
            'media'       => $result['data'] ?? []
        ]);
    }
}
