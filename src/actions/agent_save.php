<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('company');
require_csrf();

$companyId = managed_company_id();
$user = current_user();
$agentId = (int) ($_POST['agent_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$status = (string) ($_POST['status'] ?? 'active');
$notes = trim((string) ($_POST['notes'] ?? ''));

if (!$companyId || $name === '' || $email === '' || $phone === '') {
    set_flash('Name, email, and phone are required for agents.', 'danger');
    redirect('dashboard.php?section=agents');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('Please provide a valid agent email address.', 'danger');
    redirect('dashboard.php?section=agents');
}

if (!in_array($status, ['active', 'inactive'], true)) {
    set_flash('Invalid agent status.', 'danger');
    redirect('dashboard.php?section=agents');
}

$existingAgent = null;
$existingUserId = 0;

if ($agentId > 0) {
    $existingAgent = db_one(
        'SELECT a.*, u.id AS user_id
         FROM agents a
         INNER JOIN users u ON u.id = a.user_id
         WHERE a.id = ? AND a.company_id = ?',
        [$agentId, $companyId]
    );

    if (!$existingAgent) {
        set_flash('Agent not found.', 'danger');
        redirect('dashboard.php?section=agents');
    }

    $existingUserId = (int) $existingAgent['user_id'];
}

$duplicate = db_one(
    'SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id <> :ignore_id',
    [
        'email' => $email,
        'phone' => $phone,
        'ignore_id' => $existingUserId,
    ]
);

if ($duplicate) {
    set_flash('Another user already uses that email or phone.', 'danger');
    redirect('dashboard.php?section=agents');
}

if ($agentId === 0 && strlen($password) < 6) {
    set_flash('New agents need a password with at least 6 characters.', 'danger');
    redirect('dashboard.php?section=agents');
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    if ($existingAgent) {
        $userFields = [$name, $email, $phone, $status, $existingUserId];
        $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, status = ?';

        if ($password !== '') {
            $sql .= ', password = ?';
            $userFields = [$name, $email, $phone, $status, password_hash($password, PASSWORD_DEFAULT), $existingUserId];
        }

        $sql .= ' WHERE id = ?';
        $pdo->prepare($sql)->execute($userFields);

        $pdo->prepare(
            'UPDATE users
             SET is_verified = 1,
                 verified_at = COALESCE(verified_at, NOW())
             WHERE id = ?'
        )->execute([$existingUserId]);

        $pdo->prepare('UPDATE agents SET status = ?, notes = ? WHERE id = ?')->execute([
            $status,
            $notes,
            $agentId,
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO users (role_id, name, email, phone, password, role, status, is_verified, verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            role_id_by_name('agent'),
            $name,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            'agent',
            $status,
            1,
            date('Y-m-d H:i:s'),
        ]);

        $newUserId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO agents (user_id, company_id, status, notes)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $newUserId,
            $companyId,
            $status,
            $notes,
        ]);
    }

    $pdo->commit();
    set_flash($existingAgent ? 'Agent updated successfully.' : 'Agent created successfully.', 'success');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Agent save failed: ' . $throwable->getMessage(), 'danger');
}

redirect('dashboard.php?section=agents');


