<?php
$pageTitle = 'Privacy Policy - FlowFetch';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5" style="max-width: 800px;">
    <h1 class="fw-bold mb-3">Privacy Policy</h1>
    <p class="text-muted">Last updated: <?= date('F d, Y') ?></p>
    <hr class="mb-4">

    <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
        <h5>1. Information We Collect</h5>
        <p class="text-muted">
            We collect minimal account information necessary for service delivery, including account name, email address, password hash, and user preference flags (such as preferred resolution or dark mode).
        </p>

        <h5 class="mt-4">2. Processing Data & Temporary Files</h5>
        <p class="text-muted">
            When you request a download or conversion, media files are temporarily staged in isolated server storage directories. All files are automatically permanently deleted upon retention expiration (48 hours) or upon explicit user deletion.
        </p>

        <h5 class="mt-4">3. Security Safeguards</h5>
        <p class="text-muted">
            We employ Argon2id / Bcrypt password hashing, session tokens, rate limiters, strict SSRF firewalls, and HTTPS transport encryption to protect system integrity and privacy.
        </p>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

