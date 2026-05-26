<?php

function b64url($text)
{
    return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
}

function b64url_decode($text)
{
    $pad = strlen($text) % 4;
    if ($pad) {
        $text .= str_repeat('=', 4 - $pad);
    }
    return base64_decode(strtr($text, '-_', '+/'));
}

function make_jwt($user)
{
    $header = b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = b64url(json_encode([
        'id' => (int) $user['id'],
        'role' => $user['role_name'],
        'exp' => time() + JWT_EXPIRE_SECONDS
    ]));

    $sign = hash_hmac('sha256', $header . '.' . $payload, JWT_SECRET, true);
    return $header . '.' . $payload . '.' . b64url($sign);
}

function read_jwt($token)
{
    $parts = explode('.', (string) $token);
    if (count($parts) !== 3) {
        return null;
    }

    $check = b64url(hash_hmac('sha256', $parts[0] . '.' . $parts[1], JWT_SECRET, true));
    if (!hash_equals($check, $parts[2])) {
        return null;
    }

    $data = json_decode(b64url_decode($parts[1]), true);
    if (!$data || empty($data['id']) || empty($data['exp']) || $data['exp'] < time()) {
        return null;
    }

    return $data;
}

function api_token_user()
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $header = $headers['authorization'];
        }
    }

    if (stripos($header, 'Bearer ') !== 0) {
        return null;
    }

    $data = read_jwt(trim(substr($header, 7)));
    if (!$data) {
        return null;
    }

    return find_user((int) $data['id']);
}

function make_refresh_token($userId)
{
    $token = bin2hex(random_bytes(24));
    $expires = date('Y-m-d H:i:s', strtotime('+' . REFRESH_EXPIRE_DAYS . ' days'));

    db_run(
        'INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)',
        [$userId, $token, $expires]
    );

    return ['token' => $token, 'expires_at' => $expires];
}
