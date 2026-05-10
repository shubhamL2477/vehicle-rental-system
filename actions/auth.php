<?php
require_once __DIR__ . '/../includes/functions.php';

check_csrf();
$action = $_POST['action'] ?? '';

function pending_company_request($companyId, $type)
{
    return db_one(
        'SELECT id FROM company_requests WHERE company_id = ? AND request_type = ? AND status = "pending"',
        [$companyId, $type]
    );
}

if ($action === 'register') {
    $role = $_POST['role'] ?? 'user';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $companyName = trim($_POST['company_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!in_array($role, ['user', 'company'], true)) {
        flash('You can register only as user or company.', 'danger');
        go('../register.php');
    }

    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        flash('Please fill all required fields.', 'danger');
        go('../register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        flash('Email or password is not valid.', 'danger');
        go('../register.php');
    }

    if ($password !== $confirm) {
        flash('Passwords do not match.', 'danger');
        go('../register.php');
    }

    if ($role === 'company' && $companyName === '') {
        flash('Company name is required.', 'danger');
        go('../register.php');
    }

    if (db_one('SELECT id FROM users WHERE email = ?', [$email])) {
        flash('Email already exists.', 'danger');
        go('../register.php');
    }

    if (db_one('SELECT id FROM users WHERE phone = ?', [$phone])) {
        flash('Phone already exists.', 'danger');
        go('../register.php');
    }

    db_run(
        'INSERT INTO users (role_id, name, email, phone, password, company_name, address)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            role_id($role),
            $name,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $role === 'company' ? $companyName : null,
            $address
        ]
    );

    $userId = (int) db()->lastInsertId();
    $otp = create_otp($userId, 'verify');
    $sent = send_otp_email($email, $name, $otp['code']);

    $_SESSION['verify_user_id'] = $userId;
    flash($sent ? 'Account created. OTP sent to your email.' : 'Account created, but email failed. ' . get_mail_error(), $sent ? 'success' : 'warning');
    go('../verify-otp.php');
}

if ($action === 'login') {
    $login = trim($_POST['login'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = db_one(
        'SELECT u.*, r.name AS role_name
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.email = ? OR u.phone = ?
         LIMIT 1',
        [$login, $login]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        flash('Wrong email/phone or password.', 'danger');
        go('../login.php');
    }

    if (!$user['is_verified']) {
        $_SESSION['verify_user_id'] = $user['id'];
        flash('Verify your OTP first.', 'warning');
        go('../verify-otp.php');
    }

    if ($user['status'] === 'pending_admin') {
        flash('Company account is waiting for admin approval.', 'warning');
        go('../login.php');
    }

    if ($user['status'] === 'rejected') {
        flash('Company account was rejected by admin.', 'danger');
        go('../login.php');
    }

    if ($user['status'] === 'inactive') {
        flash('Account is inactive.', 'danger');
        go('../login.php');
    }

    if ($user['status'] !== 'active') {
        flash('Account is not active yet.', 'warning');
        go('../login.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    flash('Login successful.', 'success');
    go('../dashboard.php');
}

if ($action === 'verify_otp') {
    $userId = (int) ($_SESSION['verify_user_id'] ?? $_POST['user_id'] ?? 0);
    $code = trim($_POST['otp_code'] ?? '');

    if (!$userId || $code === '') {
        flash('OTP is required.', 'danger');
        go('../verify-otp.php');
    }

    if (!verify_otp_code($userId, $code, 'verify')) {
        flash('OTP is wrong or expired.', 'danger');
        go('../verify-otp.php');
    }

    $user = find_user($userId);
    if (!$user) {
        flash('User not found.', 'danger');
        go('../verify-otp.php');
    }

    if ($user['role_name'] === 'company') {
        db_run('UPDATE users SET is_verified = 1, status = "pending_admin" WHERE id = ?', [$userId]);

        if (!pending_company_request($userId, 'create')) {
            db_run(
                'INSERT INTO company_requests (company_id, request_type, requested_data)
                 VALUES (?, "create", ?)',
                [$userId, json_encode([
                    'company_name' => $user['company_name'],
                    'phone' => $user['phone'],
                    'address' => $user['address']
                ])]
            );
        }

        unset($_SESSION['verify_user_id']);
        flash('Company verified. Wait for admin approval before login.', 'success');
        go('../login.php');
    }

    db_run('UPDATE users SET is_verified = 1, status = "active" WHERE id = ?', [$userId]);
    unset($_SESSION['verify_user_id']);
    flash('Account verified. You can login now.', 'success');
    go('../login.php');
}

if ($action === 'resend_otp') {
    $userId = (int) ($_SESSION['verify_user_id'] ?? 0);
    $user = $userId ? find_user($userId) : null;

    if (!$user) {
        flash('Register or login first.', 'danger');
        go('../login.php');
    }

    $otp = create_otp($userId, 'verify');
    if (!empty($otp['error'])) {
        flash($otp['error'], 'warning');
        go('../verify-otp.php');
    }

    $sent = send_otp_email($user['email'], $user['name'], $otp['code']);
    flash($sent ? 'New OTP sent to your email.' : 'Email failed. ' . get_mail_error(), $sent ? 'success' : 'warning');
    go('../verify-otp.php');
}

if ($action === 'forgot') {
    $email = trim($_POST['email'] ?? '');
    $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    if (!$user) {
        flash('No account found with that email.', 'danger');
        go('../forgot-password.php');
    }

    $otp = create_otp($user['id'], 'reset');
    if (!empty($otp['error'])) {
        flash($otp['error'], 'warning');
        go('../forgot-password.php');
    }

    $_SESSION['reset_user_id'] = $user['id'];
    $sent = send_otp_email($email, $user['name'], $otp['code']);
    flash($sent ? 'Password reset OTP sent to email.' : 'Email failed. ' . get_mail_error(), $sent ? 'success' : 'warning');
    go('../reset-password.php');
}

if ($action === 'reset_password') {
    $userId = (int) ($_SESSION['reset_user_id'] ?? 0);
    $code = trim($_POST['otp_code'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$userId || $code === '' || strlen($password) < 6) {
        flash('OTP and new password are required.', 'danger');
        go('../reset-password.php');
    }

    if (!verify_otp_code($userId, $code, 'reset')) {
        flash('Reset OTP is wrong or expired.', 'danger');
        go('../reset-password.php');
    }

    db_run('UPDATE users SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $userId]);
    unset($_SESSION['reset_user_id']);
    flash('Password changed. Please login.', 'success');
    go('../login.php');
}

if ($action === 'add_agent') {
    require_role('company');
    $me = current_user();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || strlen($password) < 6) {
        flash('Agent details are incomplete.', 'danger');
        go('../dashboard.php');
    }

    if (db_one('SELECT id FROM users WHERE email = ? OR phone = ?', [$email, $phone])) {
        flash('Agent email or phone already exists.', 'danger');
        go('../dashboard.php');
    }

    db_run(
        'INSERT INTO users (role_id, company_id, name, email, phone, password, status, is_verified)
         VALUES (?, ?, ?, ?, ?, ?, "active", 1)',
        [role_id('agent'), $me['id'], $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]
    );

    flash('Agent account created.', 'success');
    go('../dashboard.php');
}

if ($action === 'update_company') {
    require_role('company');
    $me = current_user();
    $companyName = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($companyName === '' || $phone === '') {
        flash('Company name and phone are required.', 'danger');
        go('../dashboard.php');
    }

    if (db_one('SELECT id FROM users WHERE phone = ? AND id != ?', [$phone, $me['id']])) {
        flash('Phone already exists.', 'danger');
        go('../dashboard.php');
    }

    if (pending_company_request($me['id'], 'update')) {
        flash('Company profile update is already waiting for admin approval.', 'warning');
        go('../dashboard.php');
    }

    db_run(
        'INSERT INTO company_requests (company_id, request_type, requested_data)
         VALUES (?, "update", ?)',
        [$me['id'], json_encode([
            'company_name' => $companyName,
            'phone' => $phone,
            'address' => $address
        ])]
    );

    flash('Company profile update sent to admin for approval.', 'success');
    go('../dashboard.php');
}

if ($action === 'request_company_delete') {
    require_role('company');
    $me = current_user();

    if (pending_company_request($me['id'], 'delete')) {
        flash('Company delete request is already waiting for admin approval.', 'warning');
        go('../dashboard.php');
    }

    db_run(
        'INSERT INTO company_requests (company_id, request_type, requested_data)
         VALUES (?, "delete", ?)',
        [$me['id'], json_encode([
            'company_name' => $me['company_name'],
            'phone' => $me['phone'],
            'address' => $me['address']
        ])]
    );

    flash('Company delete request sent to admin.', 'success');
    go('../dashboard.php');
}

if ($action === 'edit_agent') {
    require_role('company');
    $me = current_user();
    $agentId = (int) ($_POST['agent_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (db_one('SELECT id FROM users WHERE phone = ? AND id != ?', [$phone, $agentId])) {
        flash('Phone already exists.', 'danger');
        go('../dashboard.php');
    }

    db_run(
        'UPDATE users SET name = ?, phone = ? WHERE id = ? AND company_id = ?',
        [$name, $phone, $agentId, $me['id']]
    );

    flash('Agent updated.', 'success');
    go('../dashboard.php');
}

if ($action === 'delete_agent') {
    require_role('company');
    $me = current_user();
    $agentId = (int) ($_POST['agent_id'] ?? 0);

    db_run('DELETE FROM users WHERE id = ? AND company_id = ?', [$agentId, $me['id']]);
    flash('Agent deleted.', 'success');
    go('../dashboard.php');
}

if ($action === 'review_company_request') {
    require_role('admin');
    $me = current_user();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        flash('Wrong admin decision.', 'danger');
        go('../dashboard.php');
    }

    $request = db_one(
        'SELECT cr.*, u.status AS company_status
         FROM company_requests cr JOIN users u ON u.id = cr.company_id
         WHERE cr.id = ? AND cr.status = "pending"',
        [$requestId]
    );

    if (!$request) {
        flash('Company request not found.', 'danger');
        go('../dashboard.php');
    }

    if ($decision === 'approved') {
        if ($request['request_type'] === 'create') {
            db_run('UPDATE users SET status = "active" WHERE id = ?', [$request['company_id']]);
        }

        if ($request['request_type'] === 'update') {
            $data = json_decode($request['requested_data'] ?? '', true);
            if (!is_array($data)) {
                $data = [];
            }

            $companyName = trim($data['company_name'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $address = trim($data['address'] ?? '');

            if ($companyName === '' || $phone === '') {
                flash('Pending company profile data is incomplete.', 'danger');
                go('../dashboard.php');
            }

            if (db_one('SELECT id FROM users WHERE phone = ? AND id != ?', [$phone, $request['company_id']])) {
                flash('Phone already belongs to another account.', 'danger');
                go('../dashboard.php');
            }

            db_run(
                'UPDATE users SET company_name = ?, phone = ?, address = ? WHERE id = ?',
                [$companyName, $phone, $address, $request['company_id']]
            );
        }

        if ($request['request_type'] === 'delete') {
            db_run('UPDATE users SET status = "inactive" WHERE id = ?', [$request['company_id']]);
            db_run('UPDATE users SET status = "inactive" WHERE company_id = ?', [$request['company_id']]);
            db_run('UPDATE vehicles SET status = "unavailable" WHERE company_id = ?', [$request['company_id']]);
        }
    }

    if ($decision === 'rejected' && $request['request_type'] === 'create') {
        db_run('UPDATE users SET status = "rejected" WHERE id = ?', [$request['company_id']]);
    }

    db_run(
        'UPDATE company_requests SET status = ?, admin_id = ?, admin_note = ?, reviewed_at = NOW() WHERE id = ?',
        [$decision, $me['id'], $note, $requestId]
    );

    flash('Company request ' . $decision . '.', 'success');
    go('../dashboard.php');
}

if ($action === 'admin_delete_company') {
    require_role('admin');
    $companyId = (int) ($_POST['company_id'] ?? 0);

    $company = db_one(
        'SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.name = "company"',
        [$companyId]
    );

    if (!$company) {
        flash('Company not found.', 'danger');
        go('../dashboard.php');
    }

    db_run('UPDATE users SET status = "inactive" WHERE id = ?', [$companyId]);
    db_run('UPDATE users SET status = "inactive" WHERE company_id = ?', [$companyId]);
    db_run('UPDATE vehicles SET status = "unavailable" WHERE company_id = ?', [$companyId]);

    flash('Company deactivated by admin.', 'success');
    go('../dashboard.php');
}

go('../login.php');
