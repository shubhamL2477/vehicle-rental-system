<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Verify OTP';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <div class="box">
        <form class="simple-form" action="actions/auth.php" method="post">
            <h1>Verify OTP</h1>
            <p>Enter the 6 digit code sent to your email.</p>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="verify_otp">
            <label>OTP code</label>
            <input type="text" name="otp_code" maxlength="6" required>
            <button class="btn" type="submit">Verify</button>
        </form>

        <form action="actions/auth.php" method="post" class="resend-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="resend_otp">
            <button class="btn light" type="submit">Resend OTP</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
