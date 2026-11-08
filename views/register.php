<?php
use App\Core\Auth;
if (Auth::check()) {
    header('Location: dashboard');
    exit;
}
$pageTitle = 'Sign Up - FlowFetch';
require __DIR__ . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg p-4 p-md-5" style="border-radius: var(--radius-md);">
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Create Account</h3>
                    <p class="text-muted small">Unlock full playlist downloads, virtual folders, and history.</p>
                </div>

                <div class="alert alert-danger d-none" id="registerAlert"></div>

                <form id="registerForm" onsubmit="FlowApp.handleRegister(event)">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="name" required placeholder="Alex Mercer">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" name="email" required placeholder="alex@domain.com">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Password (min 6 characters)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" name="password" required minlength="6" placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3" id="registerSubmitBtn">
                        <i class="fa-solid fa-user-check me-1"></i> Register Free Account
                    </button>

                    <div class="text-center small text-muted">
                        Already have an account? <a href="login" class="fw-bold text-primary-custom text-decoration-none">Sign In Here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
