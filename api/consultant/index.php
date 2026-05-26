<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-16
 * Purpose: POST /api/consultant returns AI-assisted vehicle recommendations.
 */

require_once __DIR__ . '/../../backend/routes/api_common.php';
require_once __DIR__ . '/../../backend/models/VehicleConsultantModel.php';

try {
    api_require_method('POST');

    $data = api_data();
    $result = VehicleConsultantModel::recommend($data);

    api_response(true, 'Vehicle consultant response ready.', $result);
} catch (Throwable $throwable) {
    db_log_error($throwable, 'api/consultant/index.php');
    api_response(false, 'Consultant could not load recommendations right now.', [], 500);
}
