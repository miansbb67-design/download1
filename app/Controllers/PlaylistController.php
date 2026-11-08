<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Services\MediaEngine;

class PlaylistController
{
    private MediaEngine $engine;

    public function __construct()
    {
        $this->engine = new MediaEngine();
    }

    public function inspect(Request $request): void
    {
        $url = trim($request->input('url', ''));
        if (empty($url)) {
            Response::error('Playlist URL is required.');
        }

        $limit = (int)$request->input('limit', 50);
        if ($limit < 1 || $limit > 100) $limit = 50;

        $result = $this->engine->inspectUrl($url, $limit);
        if (!$result['success']) {
            Response::error($result['error'] ?? 'Could not fetch playlist.');
        }

        if (empty($result['is_playlist'])) {
            Response::error('The provided URL is an individual video, not a playlist. Please use the Single Video downloader.');
        }

        Response::success(['playlist' => $result['data']]);
    }

    public function startBatch(Request $request): void
    {
        $playlistData = $request->input('playlist');
        $selectedIds  = $request->input('selected_ids', []);
        $quality      = $request->input('quality', '720p');
        $format       = $request->input('format', 'mp4');
        $createFolder = $request->input('create_folder', true);

        if (empty($playlistData) || empty($selectedIds) || !is_array($selectedIds)) {
            Response::error('Please select at least one video to download from the playlist.');
        }

        $userId = Auth::id();
        $pdo = Database::getConnection();
        $playlistUuid = 'pl_' . bin2hex(random_bytes(16));

        $pdo->beginTransaction();
        try {
            $folderId = null;
            if ($createFolder && $userId) {
                $folderName = Security::clean(mb_substr($playlistData['title'] ?? 'Playlist ' . date('Y-m-d'), 0, 80));
                $stmtF = $pdo->prepare("INSERT INTO folders (user_id, name, slug, color, icon) VALUES (?, ?, ?, '#16A36A', 'list')");
                $stmtF->execute([$userId, $folderName, 'pl-' . bin2hex(random_bytes(4))]);
                $folderId = (int)$pdo->lastInsertId();
            }

            // Create Playlist record
            $stmtPl = $pdo->prepare("INSERT INTO playlists (playlist_uuid, user_id, source_url, title, thumbnail_url, total_items, status, folder_id) VALUES (?, ?, ?, ?, ?, ?, 'processing', ?)");
            $stmtPl->execute([
                $playlistUuid,
                $userId,
                $playlistData['source_url'] ?? '',
                $playlistData['title'] ?? 'Batch Playlist',
                $playlistData['thumbnail'] ?? '',
                count($selectedIds),
                $folderId
            ]);
            $playlistId = (int)$pdo->lastInsertId();

            // Insert Playlist Items and queue the first one
            $stmtItem = $pdo->prepare("INSERT INTO playlist_items (playlist_id, video_id, item_index, title, duration, thumbnail_url, selected, selected_quality, status) VALUES (?, ?, ?, ?, ?, ?, 1, ?, 'queued')");

            $queuedJobs = [];
            foreach ($playlistData['entries'] as $idx => $entry) {
                if (in_array($entry['id'], $selectedIds)) {
                    $stmtItem->execute([
                        $playlistId,
                        $entry['id'],
                        $idx + 1,
                        $entry['title'],
                        $entry['duration'],
                        $entry['thumbnail'],
                        $quality
                    ]);
                    $queuedJobs[] = [
                        'video_id' => $entry['id'],
                        'title'    => $entry['title'],
                        'url'      => $entry['url']
                    ];
                }
            }

            $pdo->commit();

            // Spawn download for first item (or all concurrent items)
            $firstJobUuid = null;
            if (!empty($queuedJobs)) {
                $first = $queuedJobs[0];
                $firstJobUuid = 'job_pl_' . bin2hex(random_bytes(14));
                $this->engine->startDownload($first['url'], 'video', $quality, $format, $firstJobUuid, $userId, $folderId);
            }

            Response::success([
                'playlist_uuid' => $playlistUuid,
                'total_queued'  => count($queuedJobs),
                'first_job_uuid'=> $firstJobUuid,
                'folder_id'     => $folderId
            ], 'Playlist batch download scheduled successfully.');

        } catch (\Exception $e) {
            $pdo->rollBack();
            Response::error('Failed to initialize playlist batch: ' . $e->getMessage());
        }
    }
}
