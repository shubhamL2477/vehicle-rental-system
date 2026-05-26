<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) {
    go('dashboard.php');
}
$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post" data-login-form>
        <div class="auth-card-head">
            <span class="brand-icon auth-brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M5 15.5h1.1a2.3 2.3 0 0 0 4.5 0h3a2.3 2.3 0 0 0 4.5 0H19a1.7 1.7 0 0 0 1.7-1.7v-2.1c0-.7-.4-1.3-1-1.5l-2.4-.9-2.1-2.7A2.7 2.7 0 0 0 13.1 5H8.4a2.8 2.8 0 0 0-2.5 1.6L4 10.4a3.6 3.6 0 0 0-.4 1.6v1.8A1.5 1.5 0 0 0 5 15.5Z" fill="currentColor"/></svg>
            </span>
            <h1>Welcome Back</h1>
            <p>Sign in to continue managing your rentals.</p>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login">

        <label>Email or phone</label>
        <input type="text" name="login" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn" type="submit">Login</button>
        <p><a href="forgot-password.php">Forgot password?</a></p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
