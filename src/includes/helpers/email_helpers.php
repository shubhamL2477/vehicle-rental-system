<?php

use PHPMailer\PHPMailer\PHPMailer;

function mail_settings_ready()
{
    if (MAIL_USERNAME === 'your_email@gmail.com') {
        return false;
    }

    if (MAIL_PASSWORD === 'your_app_password') {
        return false;
    }

    if (MAIL_FROM_EMAIL === 'your_email@gmail.com') {
        return false;
    }

    return true;
}

function send_otp_email($email, $name, $otpCode, $subject, $title, $message)
{
    if (!mail_settings_ready()) {
        throw new RuntimeException(
            'Set one sender email in src/config/app.php first. User email is used automatically from the form.'
        );
    }

    $autoloadPath = dirname(APP_ROOT) . '/vendor/autoload.php';

    if (!file_exists($autoloadPath)) {
        throw new RuntimeException('Composer packages are missing. Run composer install first.');
    }

    require_once $autoloadPath;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->Port = MAIL_PORT;
    $mail->CharSet = 'UTF-8';

    if (MAIL_ENCRYPTION === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($email, $name);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = '
        <h2>' . e($title) . '</h2>
        <p>Hello ' . e($name) . ',</p>
        <p>' . e($message) . '</p>
        <p style="font-size:24px;font-weight:bold;letter-spacing:4px;">' . e($otpCode) . '</p>
        <p>This code will expire soon. Please enter it on the verification page.</p>
    ';
    $mail->AltBody = 'Hello ' . $name . '. Your OTP code is ' . $otpCode . '.';
    $mail->send();
}

function send_verification_otp_email($email, $name, $otpCode)
{
    send_otp_email(
        $email,
        $name,
        $otpCode,
        'Your email verification code',
        'Email Verification',
        'Your verification code is:'
    );
}

function send_password_reset_otp_email($email, $name, $otpCode)
{
    send_otp_email(
        $email,
        $name,
        $otpCode,
        'Your password reset code',
        'Password Reset',
        'Use this OTP to reset your password:'
    );
}
