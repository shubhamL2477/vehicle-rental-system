<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/backend/models/StripePaymentModel.php';
require_login();

$me = current_user();
$paymentId = (int) ($_GET['payment_id'] ?? 0);
$bookingId = (int) ($_GET['booking_id'] ?? 0);
$sessionId = trim((string) ($_GET['session_id'] ?? ''));
$payment = null;
$stripeBooking = null;

if ($bookingId > 0 || $sessionId !== '') {
    if ($sessionId !== '') {
        try {
            StripePaymentModel::syncCheckoutSessionStatus($sessionId);
        } catch (Throwable $throwable) {
            // Webhooks remain the source of truth; page rendering should not fail if Stripe is unreachable.
        }
    }

    $params = [];
    $where = '';

    if ($bookingId > 0) {
        $where = 'b.id = ?';
        $params[] = $bookingId;
    } else {
        $where = 'b.stripe_session_id = ?';
        $params[] = $sessionId;
    }

    if ($me['role_name'] === 'user') {
        $where .= ' AND b.user_id = ?';
        $params[] = $me['id'];
    } elseif (in_array($me['role_name'], ['company', 'agent'], true)) {
        $companyId = $me['role_name'] === 'company' ? $me['id'] : $me['company_id'];
        $where .= ' AND b.company_id = ?';
        $params[] = $companyId;
    }

    $stripeBooking = db_one(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name, u.email AS user_email, c.company_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         JOIN users c ON c.id = b.company_id
         WHERE ' . $where . '
         LIMIT 1',
        $params
    );
}

if (!$stripeBooking && $paymentId > 0) {
    $params = [$paymentId];
    $scope = '';

    if ($me['role_name'] === 'user') {
        $scope = ' AND p.user_id = ?';
        $params[] = $me['id'];
    } elseif (in_array($me['role_name'], ['company', 'agent'], true)) {
        $companyId = $me['role_name'] === 'company' ? $me['id'] : $me['company_id'];
        $scope = ' AND b.company_id = ?';
        $params[] = $companyId;
    }

    $payment = db_one(
        'SELECT p.*, b.start_date, b.end_date, b.status AS booking_status,
                v.name AS vehicle_name, u.name AS user_name, u.email AS user_email, c.company_name
         FROM payments p
         JOIN bookings b ON b.id = p.booking_id
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = p.user_id
         JOIN users c ON c.id = b.company_id
         WHERE p.id = ?' . $scope,
        $params
    );
}

if (!$payment && !$stripeBooking) {
    flash('Payment not found.', 'danger');
    go('dashboard.php');
}

$pageTitle = 'Payment Status';
require __DIR__ . '/includes/header.php';
?>

<section class="payment-shell">
    <article class="payment-card">
        <?php if ($stripeBooking): ?>
            <div class="panel-title-row">
                <div>
                    <h1>Payment status</h1>
                    <p class="muted">Provider: Stripe Checkout</p>
                </div>
                <span class="<?= e(payment_badge($stripeBooking['payment_status'])) ?>"><?= e($stripeBooking['payment_status']) ?></span>
            </div>

            <div class="payment-summary">
                <span><b>Vehicle</b><?= e($stripeBooking['vehicle_name']) ?></span>
                <span><b>User</b><?= e($stripeBooking['user_name']) ?></span>
                <span><b>Company</b><?= e($stripeBooking['company_name']) ?></span>
                <span><b>Dates</b><?= e($stripeBooking['start_date']) ?> to <?= e($stripeBooking['end_date']) ?></span>
                <span><b>Amount</b><?= e(money($stripeBooking['total_price'])) ?></span>
                <span><b>Booking</b>#<?= e($stripeBooking['id']) ?> · <?= e($stripeBooking['status']) ?></span>
                <span><b>Stripe session</b><?= e($stripeBooking['stripe_session_id'] ?: 'Not generated yet') ?></span>
                <span><b>Payment intent</b><?= e($stripeBooking['stripe_payment_intent_id'] ?: 'Pending') ?></span>
            </div>

            <div class="payment-actions">
                <?php if ($me['role_name'] === 'user' && $stripeBooking['payment_status'] !== 'paid'): ?>
                    <form action="actions/payment.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="stripe_retry">
                        <input type="hidden" name="booking_id" value="<?= e($stripeBooking['id']) ?>">
                        <button class="btn" type="submit"><?= $stripeBooking['payment_status'] === 'failed' ? 'Retry payment' : 'Continue payment' ?></button>
                    </form>
                <?php endif; ?>
                <a class="btn light" href="dashboard.php?section=bookings">My bookings</a>
                <a class="btn light" href="payments.php">Payment history</a>
            </div>
        <?php else: ?>
        <div class="panel-title-row">
            <div>
                <h1>Payment status</h1>
                <p class="muted">Provider: <?= e(ucfirst($payment['provider'])) ?> mock gateway</p>
            </div>
            <span class="<?= e(payment_badge($payment['status'])) ?>"><?= e($payment['status']) ?></span>
        </div>

        <div class="payment-summary">
            <span><b>Vehicle</b><?= e($payment['vehicle_name']) ?></span>
            <span><b>User</b><?= e($payment['user_name']) ?></span>
            <span><b>Company</b><?= e($payment['company_name']) ?></span>
            <span><b>Dates</b><?= e($payment['start_date']) ?> to <?= e($payment['end_date']) ?></span>
            <span><b>Amount</b><?= e(money($payment['amount'])) ?></span>
            <span><b>Transaction</b><?= e($payment['transaction_id']) ?></span>
            <span><b>Gateway reference</b><?= e($payment['gateway_reference'] ?: 'Not generated yet') ?></span>
            <span><b>Webhook verified</b><?= $payment['webhook_verified'] ? 'Yes' : 'No' ?></span>
        </div>

        <div class="payment-actions">
            <?php if ($me['role_name'] === 'user' && $payment['status'] !== 'paid'): ?>
                <form action="actions/payment.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="booking_id" value="<?= e($payment['booking_id']) ?>">
                    <button class="btn" type="submit">Try payment again</button>
                </form>
            <?php endif; ?>
            <a class="btn light" href="payments.php">Payment history</a>
            <a class="btn light" href="dashboard.php">Dashboard</a>
        </div>
        <?php endif; ?>
    </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
