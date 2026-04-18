<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Login';
$pageDescription = 'Log in with your email or phone number.';

require __DIR__ . '/../includes/header.php';
?>

<section class="container auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Welcome Back</span>
        <h1>Log in to your dashboard</h1>
        <p>Use the email or phone you registered with. Your email must be verified before login.</p>

        <form action="<?= e(url('api/auth/login.php')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>
                <span>Email or phone</span>
                <input type="text" name="credential" value="<?= e(old('credential')) ?>" placeholder="email@example.com or 98xxxxxxxx" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <button type="submit" class="button button-primary">Login</button>
        </form>

        <div class="auth-links">
            <a class="text-link" href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a>
            <a class="text-link" href="<?= e(url('verify-email.php')) ?>">Verify email</a>
        </div>

        <p class="auth-meta">
            No account yet?
            <a href="<?= e(url('register.php')) ?>">Create one here</a>.
        </p>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
