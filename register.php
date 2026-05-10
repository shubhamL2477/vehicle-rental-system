<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) {
    go('dashboard.php');
}
$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>

<section class="form-page">
    <form class="box simple-form" action="actions/auth.php" method="post" data-register-form>
        <img class="form-logo" src="assets/images/logo.png" alt="Hyrox Rental">
        <h1>Create account</h1>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register">

        <label>Register as</label>
        <select name="role" data-role-select>
            <option value="user">User</option>
            <option value="company">Company</option>
        </select>

        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone</label>
        <input type="text" name="phone" required>

        <label>Address</label>
        <input type="text" name="address">

        <div data-company-box hidden>
            <label>Company name</label>
            <input type="text" name="company_name" data-company-name>
        </div>

        <label>Password</label>
        <input type="password" name="password" minlength="6" required data-password>

        <label>Confirm password</label>
        <input type="password" name="confirm_password" minlength="6" required data-confirm>
        <p class="form-error" data-form-error></p>

        <button class="btn" type="submit">Register</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
