<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-06
 * Purpose: Route map for clean URLs in this no-framework PHP project.
 */

return [
    'POST /api/bookings' => 'api/bookings/index.php',
    'GET /api/vehicles/booked_dates' => 'api/vehicles/booked_dates.php',
    'POST /api/maintenance' => 'api/maintenance/index.php',
    'POST /webhooks/stripe' => 'webhooks/stripe.php',
];
