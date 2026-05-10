<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post">
        <h1>Forgot password</h1>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="forgot">
        <label>Email</label>
        <input type="email" name="email" required>
        <button class="btn" type="submit">Send reset OTP</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

