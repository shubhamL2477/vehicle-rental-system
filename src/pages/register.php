<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Register';
$pageDescription = 'Create a renter or company account for the vehicle rental system.';
$selectedRole = old('role', 'user');

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell auth-wide">
    <div class="auth-card">
        <span class="eyebrow">Create Account</span>
        <h1>Join the vehicle rental platform</h1>
        <p>Create your account first. After registration, we will send a 6-digit OTP to your email for verification.</p>

        <form action="<?= e(url('api/auth/register.php')) ?>" method="post" class="form-grid">
            <?= csrf_field() ?>

            <label class="full-width">
                <span>Register as</span>
                <select name="role" id="role-select" data-role-toggle>
                    <option value="user" <?= $selectedRole === 'user' ? 'selected' : '' ?>>User / Renter</option>
                    <option value="company" <?= $selectedRole === 'company' ? 'selected' : '' ?>>Company</option>
                </select>
            </label>

            <label>
                <span>Full name</span>
                <input type="text" name="name" value="<?= e(old('name')) ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" required>
            </label>
            <label>
                <span>Phone</span>
                <input type="text" name="phone" value="<?= e(old('phone')) ?>" required>
            </label>
            <label>
                <span>Address</span>
                <input type="text" name="address" value="<?= e(old('address')) ?>" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" minlength="6" required>
            </label>
            <label>
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" minlength="6" required>
            </label>

            <div class="full-width company-fields <?= $selectedRole === 'company' ? '' : 'is-hidden' ?>" data-company-fields>
                <div class="stacked-panel">
                    <h2>Company details</h2>
                    <div class="form-grid">
                        <label>
                            <span>Company name</span>
                            <input type="text" name="company_name" value="<?= e(old('company_name')) ?>">
                        </label>
                        <label class="full-width">
                            <span>Company description</span>
                            <textarea name="company_description" rows="4" placeholder="Tell renters about your fleet, service area, or specialties."><?= e(old('company_description')) ?></textarea>
                        </label>
                    </div>
                    <p class="muted">Company accounts are created with pending approval and become active after super admin review.</p>
                </div>
            </div>

            <div class="full-width">
                <button type="submit" class="button button-primary">Create account</button>
            </div>
        </form>

        <p class="auth-meta">
            Already registered?
            <a href="<?= e(url('login.php')) ?>">Log in here</a>.
        </p>
        <p class="auth-meta">
            Need to verify your email?
            <a href="<?= e(url('verify-email.php')) ?>">Open verification page</a>.
        </p>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
