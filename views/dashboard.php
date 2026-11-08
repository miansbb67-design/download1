<?php
use App\Core\Auth;

$currentUser = Auth::user();
if (!$currentUser) {
    header('Location: login');
    exit;
}

$pageTitle = 'User Dashboard - FlowFetch';
require __DIR__ . '/layouts/header.php';
?>

<div class="container py-5">
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($currentUser['name']) ?></h2>
            <p class="text-muted mb-0">Manage your active download streams, organized folders, and account preferences.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-custom" onclick="FlowDashboard.selectLocalFolder()">
                <i class="fa-solid fa-folder-open me-1"></i> Set Local Folder
            </button>
            <a href="./#video-downloader" class="btn btn-primary-custom">
                <i class="fa-solid fa-plus me-1"></i> New Download
            </a>
        </div>
    </div>

    <!-- 11. User Dashboard Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-left: 4px solid var(--primary) !important;">
                <div class="text-muted small fw-bold">Total Downloads</div>
                <div class="fs-3 fw-bold my-1" id="dashTotalDownloads">0</div>
                <small class="text-muted" id="dashTotalSize">0 MB Stored</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-left: 4px solid var(--secondary) !important;">
                <div class="text-muted small fw-bold">Active Jobs</div>
                <div class="fs-3 fw-bold my-1 text-primary-custom" id="dashActiveDownloads">0</div>
                <small class="text-muted">Currently processing</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-left: 4px solid #3B82F6 !important;">
                <div class="text-muted small fw-bold">Videos / Audio</div>
                <div class="fs-3 fw-bold my-1 text-info" id="dashMediaBreakdown">0 / 0</div>
                <small class="text-muted">Videos vs Audio</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-left: 4px solid #F59E0B !important;">
                <div class="text-muted small fw-bold">Playlists</div>
                <div class="fs-3 fw-bold my-1 text-warning" id="dashPlaylistsCount">0</div>
                <small class="text-muted">Batch jobs completed</small>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Dashboard -->
    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="dashboardTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tab-history-btn" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Download History
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-folders-btn" data-bs-toggle="tab" data-bs-target="#tab-folders" type="button">
                <i class="fa-solid fa-folder-tree me-1"></i> Folder Manager
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button">
                <i class="fa-solid fa-gear me-1"></i> Account Settings
            </button>
        </li>
    </ul>

    <div class="tab-content" id="dashboardTabContent">
        <!-- 1. Download History Tab -->
        <div class="tab-pane fade show active" id="tab-history">
            <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h5 class="fw-bold mb-0">Recent Download Jobs</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Search history..." id="historySearchInput" oninput="FlowDashboard.filterHistory(this.value)">
                        <button class="btn btn-sm btn-outline-secondary" onclick="FlowDashboard.loadHistory()"><i class="fa-solid fa-rotate-right"></i></button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Media Title</th>
                                <th>Type</th>
                                <th>Format / Quality</th>
                                <th>Progress / Status</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading download records...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Folder Manager Tab -->
        <div class="tab-pane fade" id="tab-folders">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: var(--radius-md);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Virtual Folders</h5>
                            <button class="btn btn-sm btn-primary-custom" onclick="FlowDashboard.openCreateFolderModal()">
                                <i class="fa-solid fa-plus"></i> New
                            </button>
                        </div>
                        <p class="text-muted small">Group your downloaded items into customized categories.</p>

                        <div id="foldersListContainer" class="mt-3">
                            <!-- Folders populated via JS -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: var(--radius-md);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0" id="currentFolderTitle">All Files in Folder</h5>
                            <span class="badge bg-soft-mint text-primary-custom" id="currentFolderCount">0 Files</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Saved Date</th>
                                        <th class="text-end">Manage</th>
                                    </tr>
                                </thead>
                                <tbody id="folderFilesTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Select a folder to view contents.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Account Settings Tab -->
        <div class="tab-pane fade" id="tab-settings">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                        <h5 class="fw-bold mb-3">Preferences & Default Qualities</h5>
                        <form id="profileForm" onsubmit="FlowDashboard.saveProfile(event)">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Display Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($currentUser['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Email Address</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be altered directly.</small>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted">Preferred Video Quality</label>
                                    <select class="form-select" name="preferred_quality">
                                        <option value="2160p" <?= ($currentUser['preferred_quality'] ?? '') === '2160p' ? 'selected' : '' ?>>2160p (4K)</option>
                                        <option value="1080p" <?= ($currentUser['preferred_quality'] ?? '') === '1080p' ? 'selected' : '' ?>>1080p (FHD)</option>
                                        <option value="720p" <?= ($currentUser['preferred_quality'] ?? '') === '720p' ? 'selected' : '' ?>>720p (HD)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted">Preferred Format</label>
                                    <select class="form-select" name="preferred_format">
                                        <option value="mp4" <?= ($currentUser['preferred_format'] ?? '') === 'mp4' ? 'selected' : '' ?>>MP4 Video</option>
                                        <option value="mp3" <?= ($currentUser['preferred_format'] ?? '') === 'mp3' ? 'selected' : '' ?>>MP3 Audio</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100">Save Preferences</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                        <h5 class="fw-bold mb-3">Change Security Password</h5>
                        <form id="passwordForm" onsubmit="FlowDashboard.changePassword(event)">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">New Password (min 6 chars)</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-outline-custom w-100">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create Folder -->
<div class="modal fade" id="createFolderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus text-primary-custom me-2"></i>Create New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Folder Name</label>
                    <input type="text" class="form-control" id="newFolderName" placeholder="e.g. 4K Music Videos, Tech Podcasts">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Folder Color</label>
                    <input type="color" class="form-control form-control-color w-100" id="newFolderColor" value="#16A36A">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="FlowDashboard.createFolder()">Create Folder</button>
            </div>
        </div>
    </div>
</div>

<script src="public/js/dashboard.js"></script>
<?php require __DIR__ . '/layouts/footer.php'; ?>

