<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (empty($_SESSION['password_reset_user_id']) || empty($_SESSION['password_reset_verified'])) {
    set_flash('Verify reset OTP first.', 'warning');
    redirect('forgot-password.php');
}

$pageTitle = 'Set New Password';
$pageDescription = 'Enter your new password.';

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">New Password</span>
        <h1>Set a new password</h1>
        <p>Enter a new password for your account.</p>

        <form action="<?= e(url('actions/reset_password.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>New password</span>
                <input type="password" name="password" minlength="6" required>
            </label>
            <label>
                <span>Confirm new password</span>
                <input type="password" name="password_confirmation" minlength="6" required>
            </label>
            <button type="submit" class="button button-primary">Update password</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
