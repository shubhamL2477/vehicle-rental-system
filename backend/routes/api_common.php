<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Shared API helpers for JSON responses, JWT auth, and role-based guards.
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

function api_response($success, $message, $data = [], $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_PRETTY_PRINT);

    exit;
}

function api_require_method($method)
{
    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $method = strtoupper((string) $method);

    if ($requestMethod !== $method) {
        api_response(false, 'Only ' . $method . ' request is allowed.', [], 405);
    }
}

function api_data()
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false) {
        $rawBody = '';
    }

    $json = json_decode($rawBody, true);

    if (is_array($json) && !empty($json)) {
        return $json;
    }

    return $_POST;
}

function api_require_user($roles = [], $allowSession = false)
{
    $user = api_token_user();

    if (!$user && $allowSession) {
        $user = current_user();
    }

    if (!$user) {
        api_response(false, 'JWT token required.', [], 401);
    }

    if ($roles && !role_allowed($user['role_name'], $roles)) {
        api_response(false, 'Not allowed for this role.', [], 403);
    }

    return $user;
}

function api_company_id_for_user($user, $requestedCompanyId = 0)
{
    if (is_platform_admin_role($user['role_name'])) {
        return (int) $requestedCompanyId;
    }

    if ($user['role_name'] === 'company') {
        return (int) $user['id'];
    }

    if ($user['role_name'] === 'agent') {
        return (int) ($user['company_id'] ?? 0);
    }

    return 0;
}

function api_require_company_access($user, $companyId)
{
    $companyId = (int) $companyId;

    if (is_platform_admin_role($user['role_name'])) {
        return $companyId;
    }

    $managedCompanyId = api_company_id_for_user($user);

    if ($managedCompanyId < 1 || $managedCompanyId !== $companyId) {
        api_response(false, 'You cannot access resources outside your company.', [], 403);
    }

    return $companyId;
}

function api_require_vehicle_access($user, $vehicleId)
{
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? LIMIT 1', [(int) $vehicleId]);

    if (!$vehicle) {
        api_response(false, 'Vehicle not found.', [], 404);
    }

    if (in_array($user['role_name'], ['company', 'agent'], true)) {
        api_require_company_access($user, (int) $vehicle['company_id']);
    }

    return $vehicle;
}
