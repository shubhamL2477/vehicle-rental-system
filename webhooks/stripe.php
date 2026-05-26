<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: POST /webhooks/stripe verifies Stripe signatures and updates booking payment status.
 */

require_once __DIR__ . '/../backend/routes/api_common.php';
require_once __DIR__ . '/../backend/models/StripePaymentModel.php';

try {
    api_require_method('POST');

    $payload = file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if ($payload === false || $payload === '') {
        api_response(false, 'Webhook payload is empty.', [], 400);
    }

    if ($signatureHeader === '') {
        api_response(false, 'Stripe signature header is missing.', [], 400);
    }

    $event = StripePaymentModel::verifyWebhookEvent($payload, $signatureHeader);
    $result = StripePaymentModel::handleWebhookEvent($event);

    api_response(true, 'Stripe webhook processed.', $result);
} catch (Throwable $throwable) {
    api_response(false, 'Unable to process Stripe webhook: ' . $throwable->getMessage(), [], 400);
}
