<?php
$pageTitle = 'Server Error - FlowFetch';
require __DIR__ . '/../layouts/header.php';
?>
<div class="container py-5 text-center my-5">
    <div class="display-1 text-danger fw-bold">500</div>
    <h2 class="fw-bold mb-3">Internal Processing Error</h2>
    <p class="text-muted mb-4">An error occurred while processing your request. Please try again in a few moments.</p>
    <a href="./" class="btn btn-primary-custom"><i class="fa-solid fa-house me-1"></i> Return Home</a>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

