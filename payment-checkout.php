<?php
require_once __DIR__ . '/includes/functions.php';
require_role('user');

$me = current_user();
$paymentId = (int) ($_GET['payment_id'] ?? 0);
$payment = db_one(
    'SELECT p.*, b.start_date, b.end_date, v.name AS vehicle_name, u.company_name
     FROM payments p
     JOIN bookings b ON b.id = p.booking_id
     JOIN vehicles v ON v.id = b.vehicle_id
     JOIN users u ON u.id = b.company_id
     WHERE p.id = ? AND p.user_id = ?',
    [$paymentId, $me['id']]
);

if (!$payment) {
    flash('Payment not found.', 'danger');
    go('dashboard.php?section=bookings');
}

$pageTitle = 'Khalti Checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="payment-shell">
    <article class="payment-card">
        <div class="payment-logo">Khalti</div>
        <h1>Mock Khalti Checkout</h1>
        <p class="muted">This is a sandbox-style payment screen for assessment. No real money is transferred.</p>

        <div class="payment-summary">
            <span><b>Vehicle</b><?= e($payment['vehicle_name']) ?></span>
            <span><b>Company</b><?= e($payment['company_name']) ?></span>
            <span><b>Dates</b><?= e($payment['start_date']) ?> to <?= e($payment['end_date']) ?></span>
            <span><b>Amount</b><?= e(money($payment['amount'])) ?></span>
            <span><b>Transaction</b><?= e($payment['transaction_id']) ?></span>
            <span><b>Status</b><span class="<?= e(payment_badge($payment['status'])) ?>"><?= e($payment['status']) ?></span></span>
        </div>

        <?php if ($payment['status'] === 'pending'): ?>
            <div class="payment-actions">
                <form action="actions/payment.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="payment_id" value="<?= e($payment['id']) ?>">
                    <button class="btn" name="decision" value="paid" type="submit">Simulate Successful Payment</button>
                    <button class="btn danger" name="decision" value="failed" type="submit">Simulate Failed Payment</button>
                </form>
            </div>
        <?php else: ?>
            <a class="btn" href="payment-status.php?payment_id=<?= e($payment['id']) ?>">View payment status</a>
        <?php endif; ?>
    </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
