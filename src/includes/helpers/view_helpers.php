<?php

function format_money($amount)
{
    return 'Rs. ' . number_format((float) $amount, 2);
}

function format_datetime($datetime)
{
    if (!$datetime) {
        return 'N/A';
    }

    return date('M d, Y h:i A', strtotime($datetime));
}

function badge_class($status)
{
    if (
        $status === 'active' ||
        $status === 'approved' ||
        $status === 'available' ||
        $status === 'confirmed'
    ) {
        return 'badge badge-success';
    }

    if ($status === 'pending') {
        return 'badge badge-warning';
    }

    if (
        $status === 'maintenance' ||
        $status === 'inactive' ||
        $status === 'cancelled' ||
        $status === 'rejected'
    ) {
        return 'badge badge-danger';
    }

    return 'badge badge-neutral';
}

function vehicle_type_options()
{
    $options = [];

    foreach (VEHICLE_TYPES as $type) {
        $options[$type] = ucfirst($type);
    }

    return $options;
}

function bool_from_input($value)
{
    $value = (string) $value;

    if ($value === '1' || $value === 'true' || $value === 'on' || $value === 'yes') {
        return true;
    }

    return false;
}
