<?php

function clean_value($value)
{
    return trim((string) $value);
}

function is_valid_email($value)
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_phone($value)
{
    return preg_match('/^\+?[0-9][0-9\s\-]{6,19}$/', $value) === 1;
}
