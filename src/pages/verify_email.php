<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Verify Email';
$pageDescription = 'Enter the OTP sent to your email address.';
$email = (string) ($_GET['email'] ?? old('email'));
$resendSeconds = 0;

if ($email !== '') {
    $user = db_one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

    if ($user) {
        $latestOtp = latest_sent_otp((int) $user['id'], 'account_verification');

        if ($latestOtp) {
            $resendSeconds = otp_resend_seconds_left($latestOtp);
        }

        $activeOtp = latest_active_otp((int) $user['id'], 'account_verification');

        if ($activeOtp && $resendSeconds === 0) {
            $resendSeconds = otp_seconds_left($activeOtp);
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Email Verification</span>
        <h1>Verify your email</h1>
        <p>Enter the 6-digit OTP sent to your email address.</p>

        <form action="<?= e(url('api/auth/verify_otp.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($email) ?>" required>
            </label>
            <label>
                <span>OTP code</span>
                <input type="text" name="otp_code" maxlength="6" placeholder="Enter 6-digit OTP" required>
            </label>
            <button type="submit" class="button button-primary">Verify Email</button>
        </form>

        <form action="<?= e(url('api/auth/send_otp.php')) ?>" method="post" class="form-stack" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <label>
                <span>Need a new OTP?</span>
                <input type="email" name="email" value="<?= e($email) ?>" placeholder="Enter your email again" required>
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
