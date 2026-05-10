<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) {
    go('dashboard.php');
}
$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post">
        <img class="form-logo" src="assets/images/logo.png" alt="Hyrox Rental">
        <h1>Login</h1>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login">

        <label>Email or phone</label>
        <input type="text" name="login" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn" type="submit">Login</button>
        <p><a href="forgot-password.php">Forgot password?</a></p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
