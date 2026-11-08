<?php
$pageTitle = 'DMCA & Copyright Compliance - FlowFetch';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5" style="max-width: 800px;">
    <h1 class="fw-bold mb-3">Acceptable Use & DMCA Compliance</h1>
    <p class="text-muted">Last updated: <?= date('F d, Y') ?></p>
    <hr class="mb-4">

    <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
        <h5>Respect for Intellectual Property</h5>
        <p class="text-muted">
            FlowFetch respects intellectual property rights. This service operates purely as a technical utility similar to a browser or media caching proxy and does not host, index, or distribute media catalogs.
        </p>

        <h5 class="mt-4">Notice and Takedown Procedure</h5>
        <p class="text-muted">
            If you believe your copyrighted material is being accessed in violation of platform terms, please deliver a formal notification to our compliance officer with:
        </p>
        <ul class="text-muted">
            <li>Identification of the copyrighted work claimed to have been infringed.</li>
            <li>Specific URL or location on the internet where the material resides.</li>
            <li>Your contact address, phone number, and email.</li>
            <li>A statement made under penalty of perjury that the information in the notification is accurate.</li>
        </ul>
        <p class="text-muted">Contact: <code>compliance@download1.local</code></p>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

