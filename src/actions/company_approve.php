<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('super_admin');
require_csrf();

$companyId = (int) ($_POST['company_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');

if ($companyId < 1 || !in_array($status, ['approved', 'rejected'], true)) {
    set_flash('Invalid company approval request.', 'danger');
    redirect('dashboard.php?section=companies');
}

$company = db_one('SELECT * FROM companies WHERE id = ?', [$companyId]);

if (!$company) {
    set_flash('Company not found.', 'danger');
    redirect('dashboard.php?section=companies');
}

$pdo = require_db();
$pdo->beginTransaction();

try {
    $pdo->prepare('UPDATE companies SET status = ? WHERE id = ?')->execute([$status, $companyId]);
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([
        $status === 'approved' ? 'active' : 'inactive',
        (int) $company['owner_user_id'],
    ]);

    $pdo->commit();
    set_flash('Company status updated to ' . $status . '.', 'success');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Unable to update company status: ' . $throwable->getMessage(), 'danger');
}

redirect('dashboard.php?section=companies');


