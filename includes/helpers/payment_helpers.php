<?php

function payment_for_booking($bookingId)
{
    return db_one(
        'SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1',
        [(int) $bookingId]
    );
}

function payment_badge($status)
{
    if ($status === 'paid') {
        return 'badge good';
    }

    if ($status === 'pending' || $status === 'cash_due') {
        return 'badge wait';
    }

    return 'badge bad';
}

function make_transaction_id()
{
    return 'HYR-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function payment_webhook_signature($transactionId, $amount, $status)
{
    return hash_hmac('sha256', $transactionId . '|' . number_format((float) $amount, 2, '.', '') . '|' . $status, JWT_SECRET);
}

function rental_reminders_for_user($userId)
{
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    return db_all(
        'SELECT b.*, v.name AS vehicle_name,
                CASE
                    WHEN b.start_date BETWEEN ? AND ? THEN "upcoming"
                    WHEN b.end_date = ? THEN "return_due"
                    WHEN b.end_date < ? AND b.status IN ("approved", "confirmed") THEN "overdue"
                    ELSE "info"
                END AS reminder_type
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.user_id = ?
           AND b.status IN ("approved", "confirmed")
           AND (
                b.start_date BETWEEN ? AND ?
                OR b.end_date <= ?
           )
         ORDER BY b.start_date ASC, b.end_date ASC
         LIMIT 10',
        [$today, $tomorrow, $today, $today, (int) $userId, $today, $tomorrow, $today]
    );
}
