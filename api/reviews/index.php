<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-12
 * Purpose: POST /api/reviews stores a completed rental vehicle review.
 */

require_once __DIR__ . '/../../backend/routes/api_common.php';

try {
    api_require_method('POST');

    $user = api_require_user(['user']);
    $data = api_data();

    $bookingId = (int) ($data['booking_id'] ?? 0);
    $rating = (int) ($data['rating'] ?? 0);
    $comment = trim((string) ($data['comment'] ?? ''));

    if ($bookingId < 1) {
        api_response(false, 'booking_id is required.', [], 422);
    }

    if ($rating < 1 || $rating > 5) {
        api_response(false, 'Rating must be between 1 and 5.', [], 422);
    }

    $booking = db_one(
        'SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1',
        [$bookingId, (int) $user['id']]
    );

    if (!$booking) {
        api_response(false, 'Booking not found for this user.', [], 404);
    }

    if (!user_can_review_booking($booking)) {
        api_response(false, 'Review allowed only after a paid rental is completed.', [], 403);
    }

    if (db_one('SELECT id FROM reviews WHERE booking_id = ? LIMIT 1', [$bookingId])) {
        api_response(false, 'This booking already has a review.', [], 409);
    }

    db_run(
        'INSERT INTO reviews (booking_id, vehicle_id, user_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)',
        [
            $bookingId,
            (int) $booking['vehicle_id'],
            (int) $user['id'],
            $rating,
            $comment,
        ]
    );

    $reviewId = (int) db()->lastInsertId();

    api_response(true, 'Review submitted successfully.', [
        'review_id' => $reviewId,
        'booking_id' => $bookingId,
        'vehicle_id' => (int) $booking['vehicle_id'],
        'user_id' => (int) $user['id'],
        'rating' => $rating,
        'comment' => $comment,
    ], 201);
} catch (Throwable $throwable) {
    db_log_error($throwable, 'api/reviews/index.php');
    api_response(false, 'Unable to submit review.', [], 500);
}
