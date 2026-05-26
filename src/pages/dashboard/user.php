<?php
?>

<section class="stacked-panel">
    <span class="eyebrow"><?= $section === 'overview' ? 'Account Overview' : 'Bookings' ?></span>
    <h2><?= $section === 'overview' ? 'Your latest bookings' : 'All bookings' ?></h2>
    <div class="table-wrapper">
        <table class="dashboard-table">
            <thead>
            <tr>
                <th>Vehicle</th>
                <th>Company</th>
                <th>Dates</th>
                <th>Total</th>
                <th>Status</th>
                <th>Docs</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($userBookings as $booking): ?>
                <tr>
                    <td><?= e($booking['vehicle_name']) ?> <small><?= e(ucfirst($booking['vehicle_type'])) ?></small></td>
                    <td><?= e($booking['company_name']) ?></td>
                    <td><?= e(format_datetime($booking['start_datetime'])) ?><br><small><?= e(format_datetime($booking['end_datetime'])) ?></small></td>
                    <td><?= e(format_money((float) $booking['total_price'])) ?></td>
                    <td><span class="<?= e(badge_class($booking['status'])) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                    <td><?= e((string) $booking['document_count']) ?></td>
                    <td>
                        <?php if ($booking['status'] === 'pending'): ?>
                            <form action="<?= e(url('actions/booking_cancel.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                <button type="submit" class="button button-small button-danger">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">No action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$userBookings): ?>
        <div class="empty-state">
            <h3>No bookings yet</h3>
            <p>Browse the fleet and submit your first booking request.</p>
            <a class="button button-primary" href="<?= e(url('vehicles.php')) ?>">Browse Vehicles</a>
        </div>
    <?php endif; ?>
</section>
