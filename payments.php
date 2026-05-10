<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$me = current_user();
$params = [];
$where = 'WHERE 1 = 1';

if ($me['role_name'] === 'user') {
    $where .= ' AND p.user_id = ?';
    $params[] = $me['id'];
} elseif (in_array($me['role_name'], ['company', 'agent'], true)) {
    $companyId = $me['role_name'] === 'company' ? $me['id'] : $me['company_id'];
    $where .= ' AND b.company_id = ?';
    $params[] = $companyId;
}

$payments = db_all(
    'SELECT p.*, b.start_date, b.end_date, v.name AS vehicle_name, u.name AS user_name, c.company_name
     FROM payments p
     JOIN bookings b ON b.id = p.booking_id
     JOIN vehicles v ON v.id = b.vehicle_id
     JOIN users u ON u.id = p.user_id
     JOIN users c ON c.id = b.company_id
     ' . $where . '
     ORDER BY p.id DESC',
    $params
);

$pageTitle = 'Payment History';
require __DIR__ . '/includes/header.php';
?>

<section class="section-head">
    <h1>Payment history</h1>
    <p>Track Khalti mock payments, transaction IDs, receipt status and webhook verification.</p>
</section>

<section class="box">
    <table>
        <tr>
            <th>Transaction</th>
            <th>Booking</th>
            <th>User</th>
            <th>Provider</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Webhook</th>
            <th>Action</th>
        </tr>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= e($payment['transaction_id']) ?><br><span class="muted"><?= e($payment['created_at']) ?></span></td>
                <td><?= e($payment['vehicle_name']) ?><br><span class="muted"><?= e($payment['start_date']) ?> to <?= e($payment['end_date']) ?></span></td>
                <td><?= e($payment['user_name']) ?></td>
                <td><?= e(ucfirst($payment['provider'])) ?></td>
                <td><?= e(money($payment['amount'])) ?></td>
                <td><span class="<?= e(payment_badge($payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                <td><?= $payment['webhook_verified'] ? 'Verified' : 'Pending' ?></td>
                <td><a class="btn tiny" href="payment-status.php?payment_id=<?= e($payment['id']) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$payments): ?>
        <div class="empty-state">
            <h3>No payments yet</h3>
            <p>Payments appear after a user starts checkout for an approved booking.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
