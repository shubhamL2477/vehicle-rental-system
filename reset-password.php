<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post">
        <h1>Reset password</h1>
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

