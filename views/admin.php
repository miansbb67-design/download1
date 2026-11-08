<?php
use App\Core\Auth;

if (!Auth::isAdmin()) {
    header('Location: login');
    exit;
}

$pageTitle = 'Administrator Command Center - FlowFetch';
require __DIR__ . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-danger px-3 py-1 fw-bold mb-1">
                <i class="fa-solid fa-lock me-1"></i> Admin Command Center
            </span>
            <h2 class="fw-bold mb-1">Platform Operations & Control</h2>
            <p class="text-muted mb-0">Control server limits, media sources, active user permissions, and storage retention.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger" onclick="FlowAdmin.purgeExpiredFiles()">
                <i class="fa-solid fa-broom me-1"></i> Purge Expired Storage
            </button>
            <button class="btn btn-outline-secondary" onclick="FlowAdmin.refreshAll()">
                <i class="fa-solid fa-rotate me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Admin Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-top: 4px solid var(--primary) !important;">
                <div class="text-muted small fw-bold">Total Platform Users</div>
                <div class="fs-3 fw-bold my-1" id="admUsersCount">0</div>
                <small class="text-muted" id="admActiveUsersCount">0 Active Accounts</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-top: 4px solid var(--secondary) !important;">
                <div class="text-muted small fw-bold">Total Jobs Processed</div>
                <div class="fs-3 fw-bold my-1 text-primary-custom" id="admTotalJobs">0</div>
                <small class="text-muted" id="admCompletedJobs">0 Completed Successfully</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-top: 4px solid #3B82F6 !important;">
                <div class="text-muted small fw-bold">Server Disk Stored</div>
                <div class="fs-3 fw-bold my-1 text-info" id="admStorageUsed">0 MB</div>
                <small class="text-muted" id="admDiskFree">Free: 0 GB</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: var(--radius-sm); border-top: 4px solid #EF4444 !important;">
                <div class="text-muted small fw-bold">FFmpeg Transcodes</div>
                <div class="fs-3 fw-bold my-1 text-danger" id="admConversionsCount">0</div>
                <small class="text-muted">Audio conversions</small>
            </div>
        </div>
    </div>

    <!-- Admin Tabs -->
    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="adminTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="adm-users-tab" data-bs-toggle="tab" data-bs-target="#adm-tab-users" type="button">
                <i class="fa-solid fa-users me-1"></i> User Management
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="adm-settings-tab" data-bs-toggle="tab" data-bs-target="#adm-tab-settings" type="button">
                <i class="fa-solid fa-sliders me-1"></i> Global Settings
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="adm-sources-tab" data-bs-toggle="tab" data-bs-target="#adm-tab-sources" type="button">
                <i class="fa-solid fa-globe me-1"></i> Supported Sources
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="adm-logs-tab" data-bs-toggle="tab" data-bs-target="#adm-tab-logs" type="button">
                <i class="fa-solid fa-terminal me-1"></i> System Logs
            </button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabContent">
        <!-- 1. User Management -->
        <div class="tab-pane fade show active" id="adm-tab-users">
            <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                <h5 class="fw-bold mb-3">Registered User Accounts</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Downloads</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admUsersTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i> Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Global Settings -->
        <div class="tab-pane fade" id="adm-tab-settings">
            <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Platform Settings & Restrictions</h5>
                    <button class="btn btn-primary-custom" onclick="FlowAdmin.saveSettings()">Save All Changes</button>
                </div>
                <div id="admSettingsContainer" class="row g-3">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>

        <!-- 3. Supported Sources -->
        <div class="tab-pane fade" id="adm-tab-sources">
            <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                <h5 class="fw-bold mb-3">Supported Video & Audio Providers</h5>
                <p class="text-muted small">Enable or disable specific platforms without touching source code.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Source Platform</th>
                                <th>Domain / Target</th>
                                <th>Max Allowed Quality</th>
                                <th>Audio Extraction</th>
                                <th>Operational Status</th>
                                <th class="text-end">Toggle</th>
                            </tr>
                        </thead>
                        <tbody id="admSourcesTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. System Logs -->
        <div class="tab-pane fade" id="adm-tab-logs">
            <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-md);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">System Activity & Audit Logs</h5>
                    <select class="form-select form-select-sm w-auto" id="admLogLevelSelect" onchange="FlowAdmin.loadLogs(this.value)">
                        <option value="">All Levels</option>
                        <option value="INFO">INFO</option>
                        <option value="WARNING">WARNING</option>
                        <option value="ERROR">ERROR</option>
                    </select>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-striped font-monospace small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Timestamp</th>
                                <th>Level</th>
                                <th>Category</th>
                                <th>IP Address</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody id="admLogsTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="public/js/admin.js"></script>
<?php require __DIR__ . '/layouts/footer.php'; ?>
