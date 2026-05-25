<?php
require_once __DIR__ . '/../../includes/functions.php';

class NotificationService
{
    public static function notify($userId, $title, $message, $type = 'info')
    {
        $userId = (int) $userId;
        $title = trim((string) $title);
        $message = trim((string) $message);
        $type = trim((string) $type);

        if ($userId < 1 || $title === '' || $message === '') {
            return;
        }

        if (!in_array($type, ['booking', 'payment', 'reminder', 'review', 'system', 'info'], true)) {
            $type = 'info';
        }

        db_run(
            'INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)',
            [$userId, $title, $message, $type]
        );
    }

    public static function notifyMany($userIds, $title, $message, $type = 'info')
    {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            self::notify($userId, $title, $message, $type);
        }
    }

    public static function companyRecipients($companyId)
    {
        $rows = db_all(
            'SELECT id FROM users
             WHERE id = ?
                OR company_id = ?
             ORDER BY id',
            [(int) $companyId, (int) $companyId]
        );

        return array_map('intval', array_column($rows, 'id'));
    }

    public static function bookingRecipients($booking)
    {
        $recipients = [(int) $booking['user_id']];
        return array_merge($recipients, self::companyRecipients((int) $booking['company_id']));
    }

    public static function notifyBookingSubmitted($bookingId)
    {
        $booking = self::bookingDetails($bookingId);
        if (!$booking) {
            return;
        }

        self::notify((int) $booking['user_id'], 'Booking submitted', 'Your booking request for ' . $booking['vehicle_name'] . ' was submitted.', 'booking');
        self::notifyMany(self::companyRecipients((int) $booking['company_id']), 'New booking request', $booking['user_name'] . ' requested ' . $booking['vehicle_name'] . '.', 'booking');
    }

    public static function notifyBookingStatus($bookingId, $status)
    {
        $booking = self::bookingDetails($bookingId);
        if (!$booking) {
            return;
        }

        $status = (string) $status;
        $title = 'Booking ' . $status;
        $message = 'Booking #' . (int) $booking['id'] . ' for ' . $booking['vehicle_name'] . ' is now ' . $status . '.';
        self::notifyMany(self::bookingRecipients($booking), $title, $message, 'booking');
    }

    public static function notifyBookingCompleted($bookingId)
    {
        $booking = self::bookingDetails($bookingId);
        if (!$booking) {
            return;
        }

        self::notifyMany(
            self::bookingRecipients($booking),
            'Rental completed',
            'Booking #' . (int) $booking['id'] . ' for ' . $booking['vehicle_name'] . ' has been completed.',
            'booking'
        );
    }

    public static function unreadCount($userId)
    {
        return (int) db_value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [(int) $userId]);
    }

    public static function latestForUser($userId, $limit = 8)
    {
        $limit = max(1, min(20, (int) $limit));
        return db_all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY read_at IS NULL DESC, created_at DESC, id DESC LIMIT ' . $limit,
            [(int) $userId]
        );
    }

    public static function markRead($notificationId, $userId)
    {
        db_run(
            'UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ? AND user_id = ?',
            [(int) $notificationId, (int) $userId]
        );
    }

    public static function markAllRead($userId)
    {
        db_run(
            'UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE user_id = ? AND read_at IS NULL',
            [(int) $userId]
        );
    }

    private static function bookingDetails($bookingId)
    {
        return db_one(
            'SELECT b.*, v.name AS vehicle_name, renter.name AS user_name, company.company_name
             FROM bookings b
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN users renter ON renter.id = b.user_id
             JOIN users company ON company.id = b.company_id
             WHERE b.id = ?
             LIMIT 1',
            [(int) $bookingId]
        );
    }
}
