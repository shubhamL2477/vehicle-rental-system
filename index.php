<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
$siteReviews = site_review_stats();
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div>
        <p class="small-title">Simple academic project</p>
        <h1>Hyrox Rental</h1>
        <p>
            Browse vehicles, book a ride, and let company agents approve or reject booking requests.
            This Sprint 1 version is kept simple for viva.
        </p>
        <a class="btn" href="vehicles.php">Browse vehicles</a>
    </div>
</section>

<section class="grid three">
    <div class="card">
        <h3>User</h3>
        <p>Registers, verifies OTP, browses vehicles and sends booking requests.</p>
    </div>
    <div class="card">
        <h3>Company</h3>
        <p>Adds, updates and removes vehicles. Also adds maintenance dates.</p>
    </div>
    <div class="card">
        <h3>Agent</h3>
        <p>Works under one company and approves or rejects bookings.</p>
    </div>
</section>

<section class="box rating-overview">
    <div>
        <h2>Website rating</h2>
        <p class="muted">Average score from logged-in customer feedback.</p>
    </div>
    <strong><?= e($siteReviews['review_count'] ? number_format($siteReviews['average_rating'], 1) . '/5' : 'No reviews yet') ?></strong>
    <span><?= e($siteReviews['review_count']) ?> review(s)</span>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
