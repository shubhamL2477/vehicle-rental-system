<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../backend/models/BookingModel.php';
require_once __DIR__ . '/../backend/models/StripePaymentModel.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'start') {
    check_csrf();
    require_role('user');
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    $booking = db_one(
        'SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = "approved"',
        [$bookingId, $me['id']]
    );

    if (!$booking) {
        flash('Only approved bookings can be paid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    $paid = db_one('SELECT id FROM payments WHERE booking_id = ? AND status = "paid"', [$bookingId]);
    if ($paid) {
        flash('This booking is already paid.', 'success');
        go('../payment-status.php?payment_id=' . $paid['id']);
    }

    $payment = payment_for_booking($bookingId);

    if (!$payment || $payment['status'] !== 'pending') {
        db_run(
            'INSERT INTO payments (booking_id, user_id, provider, amount, status, transaction_id)
             VALUES (?, ?, "khalti", ?, "pending", ?)',
            [$bookingId, $me['id'], $booking['total_price'], make_transaction_id()]
        );
        $payment = payment_for_booking($bookingId);
    }

    go('../payment-checkout.php?payment_id=' . $payment['id']);
}

if ($action === 'stripe_retry') {
    check_csrf();
    require_role('user');
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    $booking = db_one(
        'SELECT b.*, v.name AS vehicle_name, renter.email AS user_email, u.company_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users renter ON renter.id = b.user_id
         JOIN users u ON u.id = b.company_id
         WHERE b.id = ? AND b.user_id = ? AND b.payment_method = "stripe"',
        [$bookingId, $me['id']]
    );

    if (!$booking) {
        flash('Stripe booking not found.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if ($booking['payment_status'] === 'paid') {
        flash('This booking is already paid.', 'success');
        go('../payment-status.php?booking_id=' . $bookingId);
    }

    try {
        $session = StripePaymentModel::createCheckoutSession($booking);
        StripePaymentModel::storeCheckoutSession($bookingId, $session);
        db_run('UPDATE bookings SET payment_status = "pending" WHERE id = ?', [$bookingId]);

        if (!empty($session->url)) {
            go((string) $session->url);
        }

        flash('Stripe Checkout did not return a redirect URL.', 'danger');
    } catch (Throwable $throwable) {
        db_run('UPDATE bookings SET payment_status = "failed" WHERE id = ?', [$bookingId]);
        flash('Unable to restart Stripe checkout: ' . $throwable->getMessage(), 'danger');
    }

    go('../payment-status.php?booking_id=' . $bookingId);
}

if ($action === 'complete') {
    check_csrf();
    require_role('user');
    $me = current_user();
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';

    $payment = db_one(
        'SELECT p.*, b.user_id AS booking_user_id
         FROM payments p
         JOIN bookings b ON b.id = p.booking_id
         WHERE p.id = ? AND p.user_id = ?',
        [$paymentId, $me['id']]
    );

    if (!$payment || $payment['status'] !== 'pending') {
        flash('Payment request is not valid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (!in_array($decision, ['paid', 'failed'], true)) {
        flash('Choose payment success or failure.', 'danger');
        go('../payment-checkout.php?payment_id=' . $paymentId);
    }

    $signature = payment_webhook_signature($payment['transaction_id'], $payment['amount'], $decision);

    db_run(
        'UPDATE payments
         SET status = ?, gateway_reference = ?, webhook_signature = ?, webhook_verified = 1, paid_at = IF(? = "paid", NOW(), NULL)
         WHERE id = ?',
        [
            $decision,
            'KHALTI-MOCK-' . strtoupper(bin2hex(random_bytes(4))),
            $signature,
            $decision,
            $paymentId
        ]
    );

    db_run(
        'UPDATE bookings SET payment_method = "cash", payment_status = ? WHERE id = ?',
        [$decision === 'paid' ? 'paid' : 'failed', $payment['booking_id']]
    );

    if ($decision === 'paid') {
        send_payment_receipt_email($paymentId);
        send_booking_confirmation_email($payment['booking_id']);
        flash('Payment successful. Receipt and booking confirmation email were triggered.', 'success');
    } else {
        flash('Payment failed. You can try again from the payment status page.', 'danger');
    }

    go('../payment-status.php?payment_id=' . $paymentId);
}

if ($action === 'webhook') {
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $signature = $_POST['signature'] ?? '';

    $payment = db_one('SELECT * FROM payments WHERE id = ?', [$paymentId]);

    if (!$payment || !in_array($status, ['paid', 'failed'], true)) {
        http_response_code(422);
        echo 'Invalid webhook payload';
        exit;
    }

    $expected = payment_webhook_signature($payment['transaction_id'], $payment['amount'], $status);

    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        echo 'Invalid webhook signature';
        exit;
    }

    db_run(
        'UPDATE payments SET status = ?, webhook_signature = ?, webhook_verified = 1, paid_at = IF(? = "paid", NOW(), NULL) WHERE id = ?',
        [$status, $signature, $status, $paymentId]
    );

    if ($status === 'paid') {
        send_payment_receipt_email($paymentId);
        send_booking_confirmation_email($payment['booking_id']);
    }

    echo 'Webhook accepted';
    exit;
}

go('../dashboard.php');
