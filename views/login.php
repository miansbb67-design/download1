<?php
use App\Core\Auth;
if (Auth::check()) {
    header('Location: dashboard');
    exit;
}
$pageTitle = 'Log In - FlowFetch';
require __DIR__ . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg p-4 p-md-5" style="border-radius: var(--radius-md);">
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Account Sign In</h3>
                    <p class="text-muted small">Access your downloads, saved folders, and settings.</p>
                </div>

                <div class="alert alert-danger d-none" id="loginAlert"></div>

                <form id="loginForm" onsubmit="FlowApp.handleLogin(event)">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" name="email" required placeholder="admin@download1.local">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-muted mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" name="password" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3" id="loginSubmitBtn">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign In
                    </button>

                    <div class="text-center small text-muted">
                        Don't have an account yet? <a href="register" class="fw-bold text-primary-custom text-decoration-none">Sign Up Free</a>
                    </div>

                    <hr class="my-4">
                    <div class="bg-light p-3 rounded-3 small">
                        <div class="fw-bold mb-1">Pre-configured Demo Credentials:</div>
                        <div class="text-muted">Admin: <code>admin@download1.local</code> / <code>Admin@123456</code></div>
                        <div class="text-muted">User: <code>user@download1.local</code> / <code>User@123456</code></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>

