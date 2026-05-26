<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Company, agent, and admin API for maintenance records and availability blocks.
 */

require_once __DIR__ . '/../../backend/routes/api_common.php';
require_once __DIR__ . '/../../backend/models/MaintenanceModel.php';

try {
    $user = api_require_user(['admin', 'super_admin', 'company', 'agent']);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET') {
        $vehicleId = (int) ($_GET['vehicle_id'] ?? 0);
        $where = [];
        $params = [];

        if ($vehicleId > 0) {
            $vehicle = api_require_vehicle_access($user, $vehicleId);
            $where[] = 'mr.vehicle_id = ?';
            $params[] = $vehicleId;
        }

        if (!is_platform_admin_role($user['role_name'])) {
            $companyId = managed_company_id($user);
            if ($companyId < 1) {
                api_response(false, 'Company scope is required.', [], 403);
            }
            $where[] = 'mr.company_id = ?';
            $params[] = $companyId;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $records = db_all(
            'SELECT mr.*, v.name AS vehicle_name
             FROM maintenance_records mr
             JOIN vehicles v ON v.id = mr.vehicle_id
             ' . $whereSql . '
             ORDER BY mr.start_date DESC, mr.id DESC',
            $params
        );

        api_response(true, 'Maintenance records loaded.', ['maintenance_records' => $records]);
    }

    if ($method === 'DELETE') {
        $data = api_data();
        MaintenanceModel::delete($user, (int) ($data['maintenance_id'] ?? $_GET['maintenance_id'] ?? 0));
        api_response(true, 'Maintenance record deleted.');
    }

    if ($method === 'POST') {
        $data = api_data();
        $vehicle = api_require_vehicle_access($user, (int) ($data['vehicle_id'] ?? 0));

        if (in_array($user['role_name'], ['company', 'agent'], true)) {
            api_require_company_access($user, (int) $vehicle['company_id']);
        }

        $record = MaintenanceModel::save($user, $data);

        api_response(true, 'Maintenance saved and availability block synced.', [
            'maintenance' => MaintenanceModel::responseData($record),
        ], 201);
    }

    api_response(false, 'Unsupported method.', [], 405);
} catch (Throwable $throwable) {
    $statusCode = $throwable instanceof InvalidArgumentException ? 422 : 500;

    if (preg_match('/outside your company|access/i', $throwable->getMessage())) {
        $statusCode = 403;
    }

    api_response(false, 'Unable to save maintenance: ' . $throwable->getMessage(), [], $statusCode);
}
