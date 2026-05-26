<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Forgot Password';
$pageDescription = 'Request an OTP to reset your password.';

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Password Reset</span>
        <h1>Forgot your password?</h1>
        <p>Enter your registered phone number or email. We will send a 6-digit OTP to your registered email address.</p>

        <form action="<?= e(url('actions/send_reset_otp.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>Phone or email</span>
                <input type="text" name="credential" value="<?= e(old('credential')) ?>" placeholder="9768408956 or email@example.com" required>
            </label>
            <button type="submit" class="button button-primary">Send OTP</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
