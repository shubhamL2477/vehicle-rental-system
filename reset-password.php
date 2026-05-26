<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post">
        <div class="auth-card-head">
            <span class="brand-icon auth-brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M5 15.5h1.1a2.3 2.3 0 0 0 4.5 0h3a2.3 2.3 0 0 0 4.5 0H19a1.7 1.7 0 0 0 1.7-1.7v-2.1c0-.7-.4-1.3-1-1.5l-2.4-.9-2.1-2.7A2.7 2.7 0 0 0 13.1 5H8.4a2.8 2.8 0 0 0-2.5 1.6L4 10.4a3.6 3.6 0 0 0-.4 1.6v1.8A1.5 1.5 0 0 0 5 15.5Z" fill="currentColor"/></svg>
            </span>
            <h1>Reset Password</h1>
            <p>Use the OTP from your email to set a new password.</p>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset_password">
        <label>Reset OTP</label>
        <input type="text" name="otp_code" maxlength="6" required>
        <label>New password</label>
        <input type="password" name="password" minlength="6" required>
        <button class="btn" type="submit">Change password</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
