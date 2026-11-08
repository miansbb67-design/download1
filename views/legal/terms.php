<?php
$pageTitle = 'Terms & Conditions - FlowFetch';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5" style="max-width: 800px;">
    <h1 class="fw-bold mb-3">Terms and Conditions</h1>
    <p class="text-muted">Last updated: <?= date('F d, Y') ?></p>
    <hr class="mb-4">

    <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
        <h5>1. Acceptance of Terms</h5>
        <p class="text-muted">
            By accessing or using the FlowFetch website and downloading services, you agree to comply with and be bound by these Terms and Conditions and all applicable laws and regulations.
        </p>

        <h5 class="mt-4">2. Permitted Use</h5>
        <p class="text-muted">
            FlowFetch is engineered to download, transcode, and organize media streams solely from authorized sources, public domain repositories, creative commons distributions, or personal user-owned video and audio files. You must have explicit legal right or permission to download any media processed through this platform.
        </p>

        <h5 class="mt-4">3. Prohibited Activities</h5>
        <p class="text-muted">
            Users may not attempt to:
        </p>
        <ul class="text-muted">
            <li>Circumvent or defeat digital rights management (DRM) protections.</li>
            <li>Conduct denial-of-service, automated scraping, or server-side request forgery (SSRF) against internal or third-party networks.</li>
            <li>Process material that is defamatory, obscene, or infringing on intellectual property rights.</li>
        </ul>

        <h5 class="mt-4">4. Storage & File Retention</h5>
        <p class="text-muted">
            Files processed by FlowFetch are temporary cache copies for transmission to the user and are automatically purged after 48 hours according to platform retention parameters.
        </p>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
