<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: POST /api/bookings creates a booking reservation and optional Stripe Checkout session.
 */

require_once __DIR__ . '/../../backend/routes/api_common.php';
require_once __DIR__ . '/../../backend/models/BookingModel.php';
require_once __DIR__ . '/../../backend/models/StripePaymentModel.php';

try {
    api_require_method('POST');

    $user = api_require_user(['user']);
    $data = api_data();

    if (($data['payment_method'] ?? 'cash') === 'stripe') {
        StripePaymentModel::requireCheckoutConfig();
    }

    $booking = BookingModel::createForUser($user, $data);
    $response = BookingModel::confirmationData($booking);

    if ($booking['payment_method'] === 'stripe') {
        $session = StripePaymentModel::createCheckoutSession($booking);
        StripePaymentModel::storeCheckoutSession((int) $booking['id'], $session);

        $booking = BookingModel::findConfirmation((int) $booking['id']);
        $response = BookingModel::confirmationData($booking);
        $response['checkout_url'] = (string) ($session->url ?? '');

        api_response(true, 'Booking created. Redirect user to Stripe Checkout.', $response, 201);
    }

    api_response(true, 'Booking created successfully.', $response, 201);
} catch (Throwable $throwable) {
    $statusCode = 500;

    if ($throwable instanceof InvalidArgumentException) {
        $statusCode = 422;
    }

    if ($throwable instanceof RuntimeException && preg_match('/booking|maintenance|blocked|available|active|price/i', $throwable->getMessage())) {
        $statusCode = 409;
    }

    if ($statusCode === 500) {
        db_log_error($throwable, 'api/bookings/index.php');
    }

    api_response(false, 'Unable to create booking: ' . $throwable->getMessage(), [], $statusCode);
}
