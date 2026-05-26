<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('company');
require_csrf();

$companyId = managed_company_id();
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
$contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));

if (!$companyId || $name === '' || $address === '' || $contactPhone === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    set_flash('Please complete the company profile fields correctly.', 'danger');
    redirect('dashboard.php?section=profile');
}

$pdo = require_db();
$statement = $pdo->prepare(
    'UPDATE companies
     SET name = ?, description = ?, address = ?, contact_email = ?, contact_phone = ?
     WHERE id = ?'
);
$statement->execute([$name, $description, $address, $contactEmail, $contactPhone, $companyId]);

set_flash('Company profile updated.', 'success');
redirect('dashboard.php?section=profile');


