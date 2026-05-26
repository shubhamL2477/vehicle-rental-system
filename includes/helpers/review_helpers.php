<?php

function vehicle_review_stats($vehicleId)
{
    $stats = db_one(
        'SELECT ROUND(AVG(rating), 1) AS average_rating, COUNT(*) AS review_count
         FROM reviews
         WHERE vehicle_id = ? AND status = "published"',
        [(int) $vehicleId]
    );

    return [
        'average_rating' => $stats && $stats['average_rating'] !== null ? (float) $stats['average_rating'] : 0,
        'review_count' => $stats ? (int) $stats['review_count'] : 0,
    ];
}

function site_review_stats()
{
    $stats = db_one(
        'SELECT ROUND(AVG(rating), 1) AS average_rating, COUNT(*) AS review_count
         FROM site_reviews
         WHERE status = "published"'
    );

    return [
        'average_rating' => $stats && $stats['average_rating'] !== null ? (float) $stats['average_rating'] : 0,
        'review_count' => $stats ? (int) $stats['review_count'] : 0,
    ];
}

function user_can_rate_site($userId)
{
    return !db_one('SELECT id FROM site_reviews WHERE user_id = ? LIMIT 1', [(int) $userId]);
}

function rating_text($averageRating, $reviewCount)
{
    if ((int) $reviewCount < 1) {
        return 'No reviews yet';
    }

    return number_format((float) $averageRating, 1) . '/5 from ' . (int) $reviewCount . ' review(s)';
}

function rating_html($averageRating, $reviewCount)
{
    $reviewCount = (int) $reviewCount;

    if ($reviewCount < 1) {
        return '<p class="rating-line"><span class="rating-stars empty" aria-hidden="true">&#9734;&#9734;&#9734;&#9734;&#9734;</span> No reviews yet</p>';
    }

    $averageRating = (float) $averageRating;
    $rounded = (int) round($averageRating);
    $rounded = max(1, min(5, $rounded));
    $stars = str_repeat('&#9733;', $rounded) . str_repeat('&#9734;', 5 - $rounded);

    return '<p class="rating-line" aria-label="' . e(rating_text($averageRating, $reviewCount)) . '"><span class="rating-stars" aria-hidden="true">' . $stars . '</span> ' . e(number_format($averageRating, 1)) . '/5 from ' . e($reviewCount) . ' review(s)</p>';
}

function user_can_review_booking($booking)
{
    if (!$booking) {
        return false;
    }

    if ($booking['status'] !== 'completed') {
        return false;
    }

    if (($booking['payment_status'] ?? '') === 'paid') {
        return true;
    }

    $paid = db_one('SELECT id FROM payments WHERE booking_id = ? AND status = "paid" LIMIT 1', [(int) $booking['id']]);
    return $paid !== null;
}
