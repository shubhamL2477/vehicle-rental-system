<?php

function booking_days($startDatetime, $endDatetime)
{
    $start = strtotime($startDatetime);
    $end = strtotime($endDatetime);

    if ($start === false || $end === false || $end <= $start) {
        return 0;
    }

    $seconds = $end - $start;
    return max(1, (int) ceil($seconds / 86400));
}

function calculate_booking_total($vehicle, $startDatetime, $endDatetime, $withDriver)
{
    $days = booking_days($startDatetime, $endDatetime);

    if ($days < 1) {
        return 0;
    }

    $total = $days * (float) ($vehicle['price_per_day'] ?? 0);

    if ($withDriver) {
        $total += $days * (float) ($vehicle['driver_price_per_day'] ?? 0);
    }

    return $total;
}

function company_vehicle_scope_clause($alias = 'v')
{
    return $alias . '.company_id = :company_id';
}

function location_options()
{
    $locations = db_all('SELECT id, name FROM locations ORDER BY name');
    $options = [];

    foreach ($locations as $location) {
        $options[$location['name']] = $location['name'];
    }

    return $options;
}
