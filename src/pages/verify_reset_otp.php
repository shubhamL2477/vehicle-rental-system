<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Verify Reset OTP';
$pageDescription = 'Verify the OTP code for password reset.';
$credential = (string) ($_GET['credential'] ?? old('credential'));
$resendSeconds = 0;

if ($credential !== '') {
    $user = db_one('SELECT id FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

    if ($user) {
        $latestOtp = latest_sent_otp((int) $user['id'], 'password_reset');
        if ($latestOtp) {
            $resendSeconds = otp_resend_seconds_left($latestOtp);
        }

        $activeOtp = latest_active_otp((int) $user['id'], 'password_reset');
        if ($activeOtp && $resendSeconds === 0) {
            $resendSeconds = otp_seconds_left($activeOtp);
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Password Reset OTP</span>
        <h1>Verify reset code</h1>
        <p>Enter the OTP sent to your registered email address. The code expires in 60 seconds.</p>

        <form action="<?= e(url('actions/verify_reset_otp.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>Phone or email</span>
                <input type="text" name="credential" value="<?= e($credential) ?>" required>
            </label>
            <label>
                <span>OTP code</span>
                <input type="text" name="otp_code" maxlength="6" required>
            </label>
            <button type="submit" class="button button-primary">Verify OTP</button>
        </form>

        <form action="<?= e(url('actions/send_reset_otp.php')) ?>" method="post" class="form-stack" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <label>
                <span>Need a new OTP?</span>
                <input type="text" name="credential" value="<?= e($credential) ?>" placeholder="Enter phone or email again" required>
            </label>
            <button
                type="submit"
                class="button button-secondary"
                data-resend-button
                data-resend-seconds="<?= e((string) $resendSeconds) ?>"
                data-resend-default-text="Resend OTP"
                <?= $resendSeconds > 0 ? 'disabled' : '' ?>
            >
                <?= $resendSeconds > 0 ? 'Resend OTP in ' . $resendSeconds . 's' : 'Resend OTP' ?>
            </button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
