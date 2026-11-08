<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;

class FolderController
{
    public function list(Request $request): void
    {
        $userId = Auth::id() ?: 1; // Default to admin/demo if guest
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT f.*, 
                   COUNT(df.id) AS file_count, 
                   COALESCE(SUM(df.file_size), 0) AS total_size
            FROM folders f
            LEFT JOIN downloaded_files df ON f.id = df.folder_id
            WHERE f.user_id = ?
            GROUP BY f.id
            ORDER BY f.created_at ASC
        ");
        $stmt->execute([$userId]);
        $folders = $stmt->fetchAll();

        // Also get unfiled count
        $stmtUnfiled = $pdo->prepare("
            SELECT COUNT(id) as count, COALESCE(SUM(file_size), 0) as size 
            FROM downloaded_files 
            WHERE user_id = ? AND folder_id IS NULL
        ");
        $stmtUnfiled->execute([$userId]);
        $unfiled = $stmtUnfiled->fetch();

        Response::success([
            'folders' => $folders,
            'unfiled' => [
                'name'        => 'Unfiled Downloads',
                'file_count'  => (int)$unfiled['count'],
                'total_bytes' => (int)$unfiled['size']
            ]
        ]);
    }

    public function create(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Authentication required to create folders.', 401);
        }

        $name = trim($request->input('name', ''));
        $color = $request->input('color', '#16A36A');
        $icon = $request->input('icon', 'folder');

        if (empty($name)) {
            Response::error('Folder name cannot be blank.');
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) . '-' . bin2hex(random_bytes(2));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO folders (user_id, name, slug, color, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, Security::clean($name), $slug, $color, $icon]);

        Response::success([
            'folder' => [
                'id'    => (int)$pdo->lastInsertId(),
                'name'  => $name,
                'slug'  => $slug,
                'color' => $color,
                'icon'  => $icon
            ]
        ], 'Folder created successfully.');
    }

    public function rename(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) Response::error('Unauthorized', 401);

        $id = (int)$request->input('folder_id');
        $newName = trim($request->input('name', ''));

        if (empty($newName)) {
            Response::error('Folder name is required.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE folders SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([Security::clean($newName), $id, $userId]);

        Response::success([], 'Folder renamed successfully.');
    }

    public function delete(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) Response::error('Unauthorized', 401);

        $id = (int)$request->input('folder_id');

        $pdo = Database::getConnection();
        // Unfile files in this folder
        $stmtUnfile = $pdo->prepare("UPDATE downloaded_files SET folder_id = NULL WHERE folder_id = ? AND user_id = ?");
        $stmtUnfile->execute([$id, $userId]);

        $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        Response::success([], 'Folder deleted. Contained files have been moved to Unfiled.');
    }

    public function moveFile(Request $request): void
    {
        $userId = Auth::id();
        if (!$userId) Response::error('Unauthorized', 401);

        $fileId = (int)$request->input('file_id');
        $folderId = $request->input('folder_id');
        $folderIdVal = ($folderId !== null && $folderId !== '') ? (int)$folderId : null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE downloaded_files SET folder_id = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderIdVal, $fileId, $userId]);

        Response::success([], 'File moved successfully.');
    }

    public function getContents(Request $request, array $params = []): void
    {
        $userId = Auth::id() ?: 1;
        $folderId = $params['id'] ?? $request->input('folder_id');
        $mediaType = $request->input('type'); // video, audio

        $pdo = Database::getConnection();

        $query = "SELECT df.*, dj.job_uuid, dj.source_url, f.name as folder_name, f.color as folder_color 
                  FROM downloaded_files df 
                  LEFT JOIN download_jobs dj ON df.job_id = dj.id 
                  LEFT JOIN folders f ON df.folder_id = f.id 
                  WHERE df.user_id = ?";
        $binds = [$userId];

        if ($folderId === 'unfiled' || $folderId === '0') {
            $query .= " AND df.folder_id IS NULL";
        } elseif (!empty($folderId) && is_numeric($folderId)) {
            $query .= " AND df.folder_id = ?";
            $binds[] = (int)$folderId;
        }

        if (!empty($mediaType) && in_array($mediaType, ['video', 'audio', 'document'])) {
            $query .= " AND df.media_type = ?";
            $binds[] = $mediaType;
        }

        $query .= " ORDER BY df.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($binds);
        $files = $stmt->fetchAll();

        Response::success(['files' => $files]);
    }

    public function search(Request $request): void
    {
        $userId = Auth::id() ?: 1;
        $q = trim($request->input('q', ''));
        $type = $request->input('type');

        if (empty($q)) {
            Response::error('Search term required.');
        }

        $pdo = Database::getConnection();
        $query = "SELECT df.*, f.name as folder_name FROM downloaded_files df 
                  LEFT JOIN folders f ON df.folder_id = f.id 
                  WHERE df.user_id = ? AND (df.original_filename LIKE ? OR df.stored_filename LIKE ?)";
        $binds = [$userId, "%{$q}%", "%{$q}%"];

        if (!empty($type)) {
            $query .= " AND df.media_type = ?";
            $binds[] = $type;
        }

        $query .= " ORDER BY df.created_at DESC LIMIT 50";
        $stmt = $pdo->prepare($query);
        $stmt->execute($binds);
        $results = $stmt->fetchAll();

        Response::success(['results' => $results]);
    }
}
