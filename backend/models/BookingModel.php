<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Booking validation, availability checks, pricing, persistence, and API formatting.
 */

require_once __DIR__ . '/../../includes/functions.php';

class BookingModel
{
    public static function validateBookingInput($data)
    {
        $vehicleId = (int) ($data['vehicle_id'] ?? 0);
        $startInput = trim((string) ($data['start_datetime'] ?? $data['start_date'] ?? ''));
        $endInput = trim((string) ($data['end_datetime'] ?? $data['end_date'] ?? ''));
        $withDriver = !empty($data['with_driver']);
        $paymentMethod = trim((string) ($data['payment_method'] ?? 'cash'));
        $pickupLocation = trim((string) ($data['pickup_location'] ?? ''));
        $destination = trim((string) ($data['destination'] ?? ''));
        $termsAccepted = array_key_exists('terms_accepted', $data) ? !empty($data['terms_accepted']) : true;

        if ($vehicleId < 1) {
            throw new InvalidArgumentException('vehicle_id is required.');
        }

        $startTimestamp = strtotime($startInput);
        $endTimestamp = strtotime($endInput);

        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp < $startTimestamp) {
            throw new InvalidArgumentException('Start and end dates are required, and end date must be after start date.');
        }

        if (!$termsAccepted) {
            throw new InvalidArgumentException('Booking terms must be accepted.');
        }

        if (!in_array($paymentMethod, ['cash', 'stripe'], true)) {
            throw new InvalidArgumentException('payment_method must be cash or stripe.');
        }

        return [
            'vehicle_id' => $vehicleId,
            'start_date' => date('Y-m-d', $startTimestamp),
            'end_date' => date('Y-m-d', $endTimestamp),
            'start_datetime' => date('Y-m-d H:i:s', $startTimestamp),
            'end_datetime' => date('Y-m-d H:i:s', $endTimestamp),
            'with_driver' => $withDriver,
            'payment_method' => $paymentMethod,
            'pickup_location' => $pickupLocation,
            'destination' => $destination,
            'terms_accepted' => $termsAccepted,
        ];
    }

    public static function createForUser($user, $data)
    {
        $payload = self::validateBookingInput($data);
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $vehicle = db_one(
                'SELECT v.*, u.status AS company_status, u.company_name
                 FROM vehicles v
                 JOIN users u ON u.id = v.company_id
                 WHERE v.id = ?
                 LIMIT 1
                 FOR UPDATE',
                [$payload['vehicle_id']]
            );

            if (!$vehicle || $vehicle['status'] !== 'available') {
                throw new RuntimeException('The selected vehicle is not available.');
            }

            if ($vehicle['company_status'] !== 'active') {
                throw new RuntimeException('The vehicle company is not active.');
            }

            $bookingConflict = self::bookingConflict(
                $payload['vehicle_id'],
                $payload['start_date'],
                $payload['end_date']
            );

            if ($bookingConflict) {
                throw new RuntimeException(self::bookingConflictMessage($bookingConflict));
            }

            $conflict = self::availabilityConflict(
                $payload['vehicle_id'],
                $payload['start_date'],
                $payload['end_date'],
                0,
                false
            );

            if ($conflict !== '') {
                throw new RuntimeException(self::conflictMessage($conflict));
            }

            $days = booking_days($payload['start_date'], $payload['end_date']);
            $dailyRate = $payload['with_driver']
                ? (float) $vehicle['with_driver_price']
                : (float) $vehicle['self_drive_price'];
            $totalPrice = $days * $dailyRate;

            if ($totalPrice <= 0) {
                throw new RuntimeException('Vehicle price is not configured.');
            }

            $paymentStatus = $payload['payment_method'] === 'stripe' ? 'pending' : 'cash_due';

            db_run(
                'INSERT INTO bookings
                 (user_id, vehicle_id, company_id, start_datetime, end_datetime, start_date, end_date, pickup_location, destination,
                  with_driver, daily_rate, total_price, payment_method, payment_status, terms_accepted)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $user['id'],
                    $payload['vehicle_id'],
                    (int) $vehicle['company_id'],
                    $payload['start_datetime'],
                    $payload['end_datetime'],
                    $payload['start_date'],
                    $payload['end_date'],
                    $payload['pickup_location'],
                    $payload['destination'],
                    $payload['with_driver'] ? 1 : 0,
                    $dailyRate,
                    $totalPrice,
                    $payload['payment_method'],
                    $paymentStatus,
                    $payload['terms_accepted'] ? 1 : 0,
                ]
            );

            $bookingId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return self::findConfirmation($bookingId);
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    public static function bookingConflict($vehicleId, $startDate, $endDate, $excludeBookingId = 0)
    {
        return db_one(
            'SELECT id, start_date, end_date, status
             FROM bookings
             WHERE vehicle_id = ?
               AND id <> ?
               AND status IN ("pending", "approved", "confirmed")
               AND ? <= end_date
               AND ? >= start_date
             ORDER BY start_date ASC
             LIMIT 1',
            [(int) $vehicleId, (int) $excludeBookingId, $startDate, $endDate]
        );
    }

    public static function availabilityConflict($vehicleId, $startDate, $endDate, $excludeBookingId = 0, $includeBookings = true)
    {
        if ($includeBookings && self::bookingConflict($vehicleId, $startDate, $endDate, $excludeBookingId)) {
            return 'booking';
        }

        $maintenance = db_one(
            'SELECT id FROM maintenance
             WHERE vehicle_id = ?
               AND ? <= end_date
               AND ? >= start_date
             LIMIT 1',
            [(int) $vehicleId, $startDate, $endDate]
        );

        if ($maintenance) {
            return 'maintenance';
        }

        $block = db_one(
            'SELECT id FROM availability_blocks
             WHERE vehicle_id = ?
               AND ? <= DATE(end_datetime)
               AND ? >= DATE(start_datetime)
             LIMIT 1',
            [(int) $vehicleId, $startDate, $endDate]
        );

        if ($block) {
            return 'availability_block';
        }

        return '';
    }

    public static function bookingConflictMessage($booking)
    {
        $status = trim(str_replace('_', ' ', (string) ($booking['status'] ?? '')));
        $startDate = (string) ($booking['start_date'] ?? '');
        $endDate = (string) ($booking['end_date'] ?? '');

        if ($status === '') {
            $status = 'existing';
        }

        if ($startDate !== '' && $endDate !== '') {
            return 'Vehicle is unavailable for selected dates. Existing ' . $status . ' booking covers ' . $startDate . ' to ' . $endDate . '.';
        }

        return 'Vehicle already has a booking for selected dates.';
    }

    public static function conflictMessage($conflict)
    {
        if ($conflict === 'booking') {
            return 'Vehicle already has a booking for selected dates.';
        }

        if ($conflict === 'maintenance') {
            return 'Vehicle is under maintenance for selected dates.';
        }

        return 'Vehicle is blocked for selected dates.';
    }

    public static function findConfirmation($bookingId)
    {
        return db_one(
            'SELECT b.*, v.name AS vehicle_name, v.self_drive_price, v.with_driver_price,
                    u.company_name, renter.email AS user_email
             FROM bookings b
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN users u ON u.id = b.company_id
             JOIN users renter ON renter.id = b.user_id
             WHERE b.id = ?
             LIMIT 1',
            [(int) $bookingId]
        );
    }

    public static function confirmationData($booking)
    {
        return [
            'booking_id' => (int) $booking['id'],
            'vehicle_id' => (int) $booking['vehicle_id'],
            'vehicle_name' => (string) $booking['vehicle_name'],
            'company_id' => (int) $booking['company_id'],
            'company_name' => (string) $booking['company_name'],
            'start_datetime' => iso_datetime($booking['start_datetime']),
            'end_datetime' => iso_datetime($booking['end_datetime']),
            'start_date' => (string) $booking['start_date'],
            'end_date' => (string) $booking['end_date'],
            'with_driver' => (bool) $booking['with_driver'],
            'daily_rate' => (float) $booking['daily_rate'],
            'total_price' => (float) $booking['total_price'],
            'status' => (string) $booking['status'],
            'payment_method' => (string) $booking['payment_method'],
            'payment_status' => (string) $booking['payment_status'],
            'stripe_session_id' => (string) ($booking['stripe_session_id'] ?? ''),
        ];
    }
}
