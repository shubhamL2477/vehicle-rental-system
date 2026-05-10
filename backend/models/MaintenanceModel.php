<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Maintenance records that keep availability blocks synchronized for booking safety.
 */

require_once __DIR__ . '/../../includes/functions.php';

class MaintenanceModel
{
    public static function validateInput($data)
    {
        $vehicleId = (int) ($data['vehicle_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? $data['reason'] ?? 'Maintenance'));
        $description = trim((string) ($data['description'] ?? $data['reason'] ?? ''));
        $startInput = trim((string) ($data['start_datetime'] ?? $data['start_date'] ?? ''));
        $endInput = trim((string) ($data['end_datetime'] ?? $data['end_date'] ?? ''));
        $status = trim((string) ($data['status'] ?? 'scheduled'));
        $cost = (float) ($data['cost'] ?? 0);

        if ($vehicleId < 1) {
            throw new InvalidArgumentException('vehicle_id is required.');
        }

        if ($title === '') {
            throw new InvalidArgumentException('Maintenance title is required.');
        }

        $startTimestamp = strtotime($startInput);
        $endTimestamp = strtotime($endInput);

        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp < $startTimestamp) {
            throw new InvalidArgumentException('Maintenance dates are invalid.');
        }

        if (!in_array($status, ['scheduled', 'in_progress', 'completed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Invalid maintenance status.');
        }

        return [
            'maintenance_id' => (int) ($data['maintenance_id'] ?? 0),
            'vehicle_id' => $vehicleId,
            'title' => $title,
            'description' => $description,
            'cost' => max(0, $cost),
            'start_datetime' => date('Y-m-d H:i:s', $startTimestamp),
            'end_datetime' => date('Y-m-d H:i:s', $endTimestamp),
            'start_date' => date('Y-m-d', $startTimestamp),
            'end_date' => date('Y-m-d', $endTimestamp),
            'status' => $status,
        ];
    }

    public static function save($user, $data)
    {
        $payload = self::validateInput($data);
        $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$payload['vehicle_id']]);

        if (!$vehicle) {
            throw new RuntimeException('Vehicle not found.');
        }

        if (in_array($user['role_name'], ['company', 'agent'], true)) {
            $companyId = $user['role_name'] === 'company' ? (int) $user['id'] : (int) $user['company_id'];

            if ((int) $vehicle['company_id'] !== $companyId) {
                throw new RuntimeException('You cannot manage maintenance outside your company.');
            }
        }

        $pdo = db();

        try {
            $pdo->beginTransaction();

            if ($payload['maintenance_id'] > 0) {
                $record = db_one('SELECT * FROM maintenance_records WHERE id = ? LIMIT 1', [$payload['maintenance_id']]);

                if (!$record) {
                    throw new RuntimeException('Maintenance record not found.');
                }

                $blockId = self::syncAvailabilityBlock($payload, $vehicle, $user, (int) ($record['availability_block_id'] ?? 0));

                db_run(
                    'UPDATE maintenance_records
                     SET title = ?, description = ?, cost = ?, start_date = ?, end_date = ?, start_datetime = ?, end_datetime = ?, status = ?, availability_block_id = ?
                     WHERE id = ?',
                    [
                        $payload['title'],
                        $payload['description'],
                        $payload['cost'],
                        $payload['start_date'],
                        $payload['end_date'],
                        $payload['start_datetime'],
                        $payload['end_datetime'],
                        $payload['status'],
                        $blockId > 0 ? $blockId : null,
                        $payload['maintenance_id'],
                    ]
                );

                $maintenanceId = $payload['maintenance_id'];
            } else {
                $blockId = self::syncAvailabilityBlock($payload, $vehicle, $user, 0);

                db_run(
                    'INSERT INTO maintenance_records
                     (vehicle_id, company_id, title, description, cost, start_date, end_date, start_datetime, end_datetime, status, availability_block_id, created_by_user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $payload['vehicle_id'],
                        (int) $vehicle['company_id'],
                        $payload['title'],
                        $payload['description'],
                        $payload['cost'],
                        $payload['start_date'],
                        $payload['end_date'],
                        $payload['start_datetime'],
                        $payload['end_datetime'],
                        $payload['status'],
                        $blockId > 0 ? $blockId : null,
                        (int) $user['id'],
                    ]
                );

                $maintenanceId = (int) $pdo->lastInsertId();
            }

            $pdo->commit();
            self::syncLegacyMaintenance($maintenanceId);
            return self::find($maintenanceId);
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    public static function find($maintenanceId)
    {
        return db_one('SELECT * FROM maintenance_records WHERE id = ? LIMIT 1', [(int) $maintenanceId]);
    }

    public static function responseData($record)
    {
        return [
            'maintenance_id' => (int) $record['id'],
            'vehicle_id' => (int) $record['vehicle_id'],
            'company_id' => (int) $record['company_id'],
            'title' => (string) $record['title'],
            'description' => (string) ($record['description'] ?? ''),
            'cost' => (float) $record['cost'],
            'start_date' => (string) ($record['start_date'] ?? date('Y-m-d', strtotime($record['start_datetime']))),
            'end_date' => (string) ($record['end_date'] ?? date('Y-m-d', strtotime($record['end_datetime']))),
            'start_datetime' => iso_datetime($record['start_datetime']),
            'end_datetime' => iso_datetime($record['end_datetime']),
            'status' => (string) $record['status'],
            'availability_block_id' => (int) ($record['availability_block_id'] ?? 0),
        ];
    }

    public static function delete($user, $maintenanceId)
    {
        $record = db_one('SELECT * FROM maintenance_records WHERE id = ? LIMIT 1', [(int) $maintenanceId]);

        if (!$record) {
            throw new RuntimeException('Maintenance record not found.');
        }

        if (in_array($user['role_name'], ['company', 'agent'], true)) {
            $allowedCompanyId = require_company_resource_access($user, (int) $record['company_id']);

            if ($allowedCompanyId < 1) {
                throw new RuntimeException('You cannot manage maintenance outside your company.');
            }
        }

        if (!empty($record['availability_block_id'])) {
            db_run('DELETE FROM availability_blocks WHERE id = ?', [(int) $record['availability_block_id']]);
        }

        db_run('DELETE FROM maintenance WHERE vehicle_id = ? AND start_date = ? AND end_date = ?', [
            (int) $record['vehicle_id'],
            (string) ($record['start_date'] ?? date('Y-m-d', strtotime($record['start_datetime']))),
            (string) ($record['end_date'] ?? date('Y-m-d', strtotime($record['end_datetime']))),
        ]);
        db_run('DELETE FROM maintenance_records WHERE id = ?', [(int) $maintenanceId]);
    }

    private static function syncAvailabilityBlock($payload, $vehicle, $user, $blockId)
    {
        $shouldBlock = in_array($payload['status'], ['scheduled', 'in_progress'], true);

        if (!$shouldBlock) {
            if ($blockId > 0) {
                db_run('DELETE FROM availability_blocks WHERE id = ?', [$blockId]);
            }

            return 0;
        }

        if ($blockId > 0) {
            db_run(
                'UPDATE availability_blocks
                 SET start_datetime = ?, end_datetime = ?, reason = ?
                 WHERE id = ?',
                [$payload['start_datetime'], $payload['end_datetime'], 'Maintenance: ' . $payload['title'], $blockId]
            );

            return $blockId;
        }

        db_run(
            'INSERT INTO availability_blocks (vehicle_id, company_id, start_datetime, end_datetime, reason, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $payload['vehicle_id'],
                (int) $vehicle['company_id'],
                $payload['start_datetime'],
                $payload['end_datetime'],
                'Maintenance: ' . $payload['title'],
                (int) $user['id'],
            ]
        );

        return (int) db()->lastInsertId();
    }

    private static function syncLegacyMaintenance($maintenanceId)
    {
        $record = self::find($maintenanceId);

        if (!$record) {
            return;
        }

        $startDate = (string) ($record['start_date'] ?? date('Y-m-d', strtotime($record['start_datetime'])));
        $endDate = (string) ($record['end_date'] ?? date('Y-m-d', strtotime($record['end_datetime'])));
        $reason = (string) ($record['title'] ?: 'Maintenance');

        db_run('DELETE FROM maintenance WHERE vehicle_id = ? AND start_date = ? AND end_date = ?', [
            (int) $record['vehicle_id'],
            $startDate,
            $endDate,
        ]);

        if (in_array($record['status'], ['scheduled', 'in_progress'], true)) {
            db_run(
                'INSERT INTO maintenance (vehicle_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)',
                [(int) $record['vehicle_id'], $startDate, $endDate, $reason]
            );
        }
    }
}
