<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('company');
require_csrf();

$companyId = managed_company_id();
$agentId = (int) ($_POST['agent_id'] ?? 0);

if (!$companyId || $agentId < 1) {
    set_flash('Invalid agent delete request.', 'danger');
    redirect('dashboard.php?section=agents');
}

$agent = db_one(
    'SELECT a.id, a.user_id
     FROM agents a
     WHERE a.id = ? AND a.company_id = ?',
    [$agentId, $companyId]
);

if (!$agent) {
    set_flash('Agent not found.', 'danger');
    redirect('dashboard.php?section=agents');
}

$pdo = require_db();

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE bookings SET agent_id = NULL WHERE agent_id = ?')->execute([$agentId]);
    $pdo->prepare('DELETE FROM agents WHERE id = ?')->execute([$agentId]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $agent['user_id']]);
    $pdo->commit();

    set_flash('Agent deleted successfully.', 'success');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Unable to delete agent: ' . $throwable->getMessage(), 'danger');
}

redirect('dashboard.php?section=agents');


