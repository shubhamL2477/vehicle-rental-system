<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Verify Account';
$pageDescription = 'Enter the OTP sent to your phone or email to verify your account.';
$credential = (string) ($_GET['credential'] ?? old('credential'));

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">OTP Verification</span>
        <h1>Verify your account</h1>
        <p>Enter your phone or email and the OTP code. In this demo project, the OTP is shown on screen instead of being sent by SMS, and it expires in 60 seconds.</p>

        <form action="<?= e(url('actions/verify_account_otp.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>Phone or email</span>
                <input type="text" name="credential" value="<?= e($credential) ?>" required>
            </label>
            <label>
                <span>OTP code</span>
                <input type="text" name="otp_code" maxlength="6" required>
            </label>
            <button type="submit" class="button button-primary">Verify account</button>
        </form>

        <form action="<?= e(url('actions/send_account_otp.php')) ?>" method="post" class="form-stack" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <label>
                <span>Need a new OTP?</span>
                <input type="text" name="credential" value="<?= e($credential) ?>" placeholder="Enter phone or email again" required>
            </label>
            <button type="submit" class="button button-secondary">Send OTP again</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
