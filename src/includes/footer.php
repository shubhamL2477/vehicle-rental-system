<?php

$pageScripts = $pageScripts ?? [];
$footerViewer = current_user();
?>
</main>

<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-top-copy">
            <span class="eyebrow footer-eyebrow">Ready To Start</span>
            <h3>Book trusted vehicles with a cleaner rental flow.</h3>
            <p>Browse verified listings, compare daily pricing, and manage bookings in one simple platform.</p>
        </div>
        <div class="footer-top-actions">
            <a class="button button-secondary footer-button" href="<?= e(url('vehicles.php')) ?>">Browse Vehicles</a>
            <?php if ($footerViewer): ?>
                <a class="button button-accent footer-button" href="<?= e(dashboard_url()) ?>">Open Dashboard</a>
            <?php else: ?>
                <a class="button button-accent footer-button" href="<?= e(url('register.php')) ?>">Create Account</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container footer-grid professional-footer-grid">
        <div>
            <div class="footer-brand-row">
                <img src="<?= e(asset_url('images/hyrox-logo.png')) ?>" alt="<?= e(APP_NAME) ?>" class="footer-brand-logo">
                <div>
                    <h3><?= e(APP_NAME) ?></h3>
                    <p><?= e(APP_TAGLINE) ?></p>
                </div>
            </div>
            <p>Made for super admins, companies, agents, and renters who want one clean booking and management flow.</p>
        </div>
        <div>
            <h4>Explore</h4>
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('vehicles.php')) ?>">Vehicles</a>
            <a href="<?= e(url('register.php')) ?>">Create Account</a>
            <a href="<?= e(url('login.php')) ?>">Login</a>
        </div>
        <div>
            <h4>Platform</h4>
            <a href="<?= e(url('dashboard.php')) ?>">Dashboard</a>
            <a href="<?= e(url('forgot-password.php')) ?>">Forgot Password</a>
            <p>Cash payment, manual approval, and role-based access are built into the system.</p>
        </div>
        <div>
            <h4>Booking Flow</h4>
            <p>1. Browse a vehicle</p>
            <p>2. Send booking request</p>
            <p>3. Company confirms request</p>
            <p>4. Complete the ride with cash payment</p>
        </div>
    </div>

    <div class="container footer-bottom">
        <p><?= e(APP_NAME) ?> &copy; <?= e(date('Y')) ?>. Built for a simple and professional vehicle rental experience.</p>
        <div class="footer-badges">
            <span>Verified accounts</span>
            <span>Role dashboards</span>
            <span>Manual booking approval</span>
        </div>
    </div>
</footer>

<script src="<?= e(asset_url('js/app.js')) ?>"></script>
<?php foreach ($pageScripts as $scriptUrl): ?>
    <script src="<?= e((string) $scriptUrl) ?>"></script>
<?php endforeach; ?>
</body>
</html>
