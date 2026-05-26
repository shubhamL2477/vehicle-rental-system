<?php

function set_mail_error($message)
{
    $GLOBALS['mail_error'] = $message;

    $folder = APP_ROOT . '/logs/';
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    file_put_contents(
        $folder . 'email-error.log',
        date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function get_mail_error()
{
    return $GLOBALS['mail_error'] ?? 'Check SMTP username, app password and internet connection.';
}

function send_otp_email($to, $name, $code)
{
    return send_plain_email(
        $to,
        $name,
        'Hyrox Rental OTP',
        'Your OTP code is ' . $code . '. It expires in ' . OTP_EXPIRE_MINUTES . ' minutes.'
    );
}

function send_plain_email($to, $name, $subject, $body)
{
    $autoload = APP_ROOT . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        set_mail_error('PHPMailer is missing. Run composer install first.');
        return false;
    }

    if (MAIL_USERNAME === 'your-email@gmail.com' || MAIL_PASSWORD === 'your-gmail-app-password' || MAIL_PASSWORD === 'PASTE_GMAIL_APP_PASSWORD_HERE') {
        set_mail_error('SMTP is not configured. Update MAIL_USERNAME and MAIL_PASSWORD in includes/config.php.');
        return false;
    }

    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $smtpPassword = str_replace(' ', '', MAIL_PASSWORD);

    if (MAIL_HOST === 'smtp.gmail.com' && !preg_match('/^[a-zA-Z0-9]{16}$/', $smtpPassword)) {
        set_mail_error('Gmail needs a 16-character App Password. Your normal Gmail password will not work.');
        return false;
    }

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;

        $from = MAIL_FROM ?: MAIL_USERNAME;
        $mail->setFrom($from, APP_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Throwable $e) {
        set_mail_error($e->getMessage());
        return false;
    }
}

function send_booking_confirmation_email($bookingId)
{
    $booking = db_one(
        'SELECT b.*, u.name AS user_name, u.email AS user_email, v.name AS vehicle_name
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.id = ?',
        [$bookingId]
    );

    if (!$booking) {
        set_mail_error('Booking not found for confirmation email.');
        return false;
    }

    $body = "Dear {$booking['user_name']},\n\n"
        . "Your booking is confirmed after payment.\n\n"
        . "Vehicle: {$booking['vehicle_name']}\n"
        . "Dates: {$booking['start_date']} to {$booking['end_date']}\n"
        . "Total: " . money($booking['total_price']) . "\n\n"
        . "Thank you for using " . APP_NAME . '.';

    return send_plain_email($booking['user_email'], $booking['user_name'], 'Booking confirmed - ' . APP_NAME, $body);
}

function send_payment_receipt_email($paymentId)
{
    $payment = db_one(
        'SELECT p.*, b.start_date, b.end_date, u.name AS user_name, u.email AS user_email, v.name AS vehicle_name
         FROM payments p
         JOIN bookings b ON b.id = p.booking_id
         JOIN users u ON u.id = p.user_id
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE p.id = ?',
        [$paymentId]
    );

    if (!$payment) {
        set_mail_error('Payment not found for receipt email.');
        return false;
    }

    $body = "Dear {$payment['user_name']},\n\n"
        . "Payment receipt\n\n"
        . "Vehicle: {$payment['vehicle_name']}\n"
        . "Booking dates: {$payment['start_date']} to {$payment['end_date']}\n"
        . "Provider: " . ucfirst($payment['provider']) . "\n"
        . "Transaction ID: {$payment['transaction_id']}\n"
        . "Amount: " . money($payment['amount']) . "\n"
        . "Status: {$payment['status']}\n\n"
        . "Thank you for your payment.";

    return send_plain_email($payment['user_email'], $payment['user_name'], 'Payment receipt - ' . APP_NAME, $body);
}
