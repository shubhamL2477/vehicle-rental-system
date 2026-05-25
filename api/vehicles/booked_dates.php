<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Return booked and blocked ISO date ranges for a vehicle.
 */

require_once __DIR__ . '/../../backend/routes/api_common.php';

try {
    api_require_method('GET');

    $vehicleId = (int) ($_GET['vehicle_id'] ?? 0);

    if ($vehicleId < 1) {
        api_response(false, 'vehicle_id is required.', [], 422);
    }

    $vehicle = db_one('SELECT id, name FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId]);

    if (!$vehicle) {
        api_response(false, 'Vehicle not found.', [], 404);
    }

    $bookingRows = db_all(
        'SELECT id, start_datetime, end_datetime, start_date, end_date, status
         FROM bookings
         WHERE vehicle_id = ?
           AND status IN ("confirmed", "approved", "completed")
         ORDER BY start_date ASC',
        [$vehicleId]
    );

    $blockRows = db_all(
        'SELECT id, start_datetime, end_datetime, reason
         FROM availability_blocks
         WHERE vehicle_id = ?
         ORDER BY start_datetime ASC',
        [$vehicleId]
    );

    $maintenanceRows = db_all(
        'SELECT id, start_date, end_date, title, description, status
         FROM maintenance_records
         WHERE vehicle_id = ?
           AND status IN ("scheduled", "in_progress")
         ORDER BY start_date ASC',
        [$vehicleId]
    );

    $bookings = [];
    $availabilityBlocks = [];
    $ranges = [];

    foreach ($bookingRows as $booking) {
        $startDatetime = $booking['start_datetime'] ?: booking_start_datetime($booking['start_date']);
        $endDatetime = $booking['end_datetime'] ?: booking_end_datetime($booking['end_date']);
        $item = [
            'id' => (int) $booking['id'],
            'source' => 'booking',
            'status' => (string) $booking['status'],
            'start_datetime' => iso_datetime($startDatetime),
            'end_datetime' => iso_datetime($endDatetime),
            'start_date' => (string) $booking['start_date'],
            'end_date' => (string) $booking['end_date'],
        ];

        $bookings[] = $item;
        $ranges[] = $item;
    }

    foreach ($blockRows as $block) {
        $item = [
            'id' => (int) $block['id'],
            'source' => 'availability_block',
            'reason' => (string) ($block['reason'] ?? ''),
            'start_datetime' => iso_datetime($block['start_datetime']),
            'end_datetime' => iso_datetime($block['end_datetime']),
            'start_date' => date('Y-m-d', strtotime($block['start_datetime'])),
            'end_date' => date('Y-m-d', strtotime($block['end_datetime'])),
        ];

        $availabilityBlocks[] = $item;
        $ranges[] = $item;
    }

    foreach ($maintenanceRows as $maintenance) {
        $item = [
            'id' => (int) $maintenance['id'],
            'source' => 'maintenance',
            'reason' => (string) ($maintenance['title'] ?? $maintenance['description'] ?? 'Maintenance'),
            'start_datetime' => iso_datetime(booking_start_datetime($maintenance['start_date'])),
            'end_datetime' => iso_datetime(booking_end_datetime($maintenance['end_date'])),
            'start_date' => (string) $maintenance['start_date'],
            'end_date' => (string) $maintenance['end_date'],
        ];

        $availabilityBlocks[] = $item;
        $ranges[] = $item;
    }

    api_response(true, 'Booked and blocked dates loaded.', [
        'vehicle' => [
            'id' => (int) $vehicle['id'],
            'name' => (string) $vehicle['name'],
        ],
        'bookings' => $bookings,
        'availability_blocks' => $availabilityBlocks,
        'ranges' => $ranges,
    ]);
} catch (Throwable $throwable) {
    api_response(false, 'Unable to load booked dates: ' . $throwable->getMessage(), [], 500);
}
