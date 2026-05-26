<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Stripe Checkout session creation and webhook-driven booking payment updates.
 */

require_once __DIR__ . '/../../includes/functions.php';

class StripePaymentModel
{
    public static function requireCheckoutConfig()
    {
        if (STRIPE_SECRET_KEY === '') {
            throw new RuntimeException('Stripe secret key is missing. Set STRIPE_SECRET_KEY before using Stripe.');
        }
    }

    public static function requireWebhookConfig()
    {
        self::requireCheckoutConfig();

        if (STRIPE_WEBHOOK_SECRET === '') {
            throw new RuntimeException('Stripe webhook secret is missing. Set STRIPE_WEBHOOK_SECRET before testing webhooks.');
        }
    }

    public static function createCheckoutSession($booking)
    {
        self::configureStripe();

        $metadata = [
            'booking_id' => (string) $booking['id'],
            'user_id' => (string) $booking['user_id'],
            'vehicle_id' => (string) $booking['vehicle_id'],
            'company_id' => (string) $booking['company_id'],
        ];

        return \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'success_url' => absolute_url('payment-status.php?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => absolute_url('payment-status.php?booking_id=' . (int) $booking['id'] . '&payment=cancelled'),
            'client_reference_id' => (string) $booking['id'],
            'customer_email' => (string) ($booking['user_email'] ?? ''),
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => STRIPE_CURRENCY,
                    'unit_amount' => self::amountToMinorUnit((float) $booking['total_price'], STRIPE_CURRENCY),
                    'product_data' => [
                        'name' => 'Booking #' . (int) $booking['id'] . ' - ' . (string) $booking['vehicle_name'],
                        'description' => 'Rental from ' . (string) $booking['start_date'] . ' to ' . (string) $booking['end_date'],
                    ],
                ],
            ]],
        ]);
    }

    public static function storeCheckoutSession($bookingId, $session)
    {
        db_run(
            'UPDATE bookings
             SET stripe_session_id = ?,
                 stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id)
             WHERE id = ?',
            [
                self::objectValue($session, 'id'),
                self::objectValue($session, 'payment_intent') ?: null,
                (int) $bookingId,
            ]
        );
    }

    public static function syncCheckoutSessionStatus($sessionId)
    {
        if ($sessionId === '' || STRIPE_SECRET_KEY === '') {
            return null;
        }

        self::configureStripe();
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        $paymentStatus = self::objectValue($session, 'payment_status') === 'paid' ? 'paid' : 'pending';

        if (self::objectValue($session, 'status') === 'expired') {
            $paymentStatus = 'failed';
        }

        self::markByCheckoutSession($session, $paymentStatus);
        return $session;
    }

    public static function refundPaymentIntent($paymentIntentId, $reason = 'requested_by_customer')
    {
        $paymentIntentId = trim((string) $paymentIntentId);

        if ($paymentIntentId === '') {
            throw new InvalidArgumentException('Stripe payment intent id is required for a refund.');
        }

        self::configureStripe();

        return \Stripe\Refund::create([
            'payment_intent' => $paymentIntentId,
            'reason' => $reason,
        ]);
    }

    public static function verifyWebhookEvent($payload, $signatureHeader)
    {
        self::requireWebhookConfig();
        self::configureStripe();

        return \Stripe\Webhook::constructEvent($payload, $signatureHeader, STRIPE_WEBHOOK_SECRET);
    }

    public static function handleWebhookEvent($event)
    {
        $type = self::objectValue($event, 'type');
        $object = null;

        if (isset($event->data) && isset($event->data->object)) {
            $object = $event->data->object;
        }

        if ($type === 'payment_intent.succeeded') {
            self::markByPaymentIntent($object, 'paid');
            return ['handled' => true, 'message' => 'Booking payment marked paid.'];
        }

        if (in_array($type, ['payment_intent.payment_failed', 'payment_intent.canceled'], true)) {
            self::markByPaymentIntent($object, 'failed');
            return ['handled' => true, 'message' => 'Booking payment marked failed.'];
        }

        if ($type === 'checkout.session.completed') {
            $paymentStatus = self::objectValue($object, 'payment_status') === 'paid' ? 'paid' : 'pending';
            self::markByCheckoutSession($object, $paymentStatus);
            return ['handled' => true, 'message' => 'Checkout session completed.'];
        }

        if (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            self::markByCheckoutSession($object, 'failed');
            return ['handled' => true, 'message' => 'Checkout session failed.'];
        }

        return ['handled' => false, 'message' => 'Event ignored.'];
    }

    public static function amountToMinorUnit($amount, $currency)
    {
        $zeroDecimalCurrencies = [
            'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
            'pyg', 'rwf', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
        ];

        if (in_array(strtolower((string) $currency), $zeroDecimalCurrencies, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    private static function configureStripe()
    {
        self::requireCheckoutConfig();

        $autoloadPath = APP_ROOT . '/vendor/autoload.php';

        if (!file_exists($autoloadPath)) {
            throw new RuntimeException('Composer packages are missing. Run composer install.');
        }

        require_once $autoloadPath;

        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        if (method_exists('\Stripe\Stripe', 'setApiVersion')) {
            \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);
        }
    }

    private static function markByCheckoutSession($object, $paymentStatus)
    {
        $sessionId = self::objectValue($object, 'id');
        $paymentIntentId = self::objectValue($object, 'payment_intent');

        if ($sessionId === '') {
            return;
        }

        db_run(
            'UPDATE bookings
             SET payment_status = ?,
                 stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id)
             WHERE stripe_session_id = ?',
            [$paymentStatus, $paymentIntentId ?: null, $sessionId]
        );
    }

    private static function markByPaymentIntent($object, $paymentStatus)
    {
        $paymentIntentId = self::objectValue($object, 'id');
        $bookingId = (int) self::metadataValue($object, 'booking_id');

        if ($bookingId > 0) {
            db_run(
                'UPDATE bookings
                 SET payment_status = ?,
                     stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id)
                 WHERE id = ?',
                [$paymentStatus, $paymentIntentId ?: null, $bookingId]
            );

            return;
        }

        if ($paymentIntentId !== '') {
            db_run(
                'UPDATE bookings SET payment_status = ? WHERE stripe_payment_intent_id = ?',
                [$paymentStatus, $paymentIntentId]
            );
        }
    }

    private static function objectValue($object, $key)
    {
        if (is_array($object) && isset($object[$key]) && is_scalar($object[$key])) {
            return (string) $object[$key];
        }

        if (is_object($object) && isset($object->{$key}) && is_scalar($object->{$key})) {
            return (string) $object->{$key};
        }

        return '';
    }

    private static function metadataValue($object, $key)
    {
        $metadata = null;

        if (is_array($object) && isset($object['metadata'])) {
            $metadata = $object['metadata'];
        }

        if (is_object($object) && isset($object->metadata)) {
            $metadata = $object->metadata;
        }

        if (is_array($metadata) && isset($metadata[$key])) {
            return (string) $metadata[$key];
        }

        if (is_object($metadata) && isset($metadata->{$key})) {
            return (string) $metadata->{$key};
        }

        return '';
    }
}
