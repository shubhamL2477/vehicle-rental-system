<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Verify OTP';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <div class="box">
        <form class="simple-form" action="actions/auth.php" method="post">
            <div class="auth-card-head">
                <span class="brand-icon auth-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M5 15.5h1.1a2.3 2.3 0 0 0 4.5 0h3a2.3 2.3 0 0 0 4.5 0H19a1.7 1.7 0 0 0 1.7-1.7v-2.1c0-.7-.4-1.3-1-1.5l-2.4-.9-2.1-2.7A2.7 2.7 0 0 0 13.1 5H8.4a2.8 2.8 0 0 0-2.5 1.6L4 10.4a3.6 3.6 0 0 0-.4 1.6v1.8A1.5 1.5 0 0 0 5 15.5Z" fill="currentColor"/></svg>
                </span>
                <h1>Verify OTP</h1>
                <p>Enter the 6 digit code sent to your email.</p>
            </div>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="verify_otp">
            <label>OTP code</label>
            <input type="text" name="otp_code" maxlength="6" required>
            <button class="btn" type="submit">Verify</button>
        </form>

        <form action="actions/auth.php" method="post" class="resend-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="resend_otp">
            <button class="btn light" type="submit" data-resend-button data-resend-seconds="<?= e(OTP_RESEND_SECONDS) ?>">
                Resend OTP <span data-resend-timer></span>
            </button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
