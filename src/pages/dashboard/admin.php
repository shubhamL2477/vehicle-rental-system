<?php
?>

<?php if ($section === 'overview' || $section === 'companies'): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Company Approval</span>
        <h2>Review companies</h2>
        <div class="table-wrapper">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Company</th>
                    <th>Owner</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($adminCompanies as $company): ?>
                    <tr>
                        <td><?= e($company['name']) ?></td>
                        <td><?= e($company['owner_name']) ?></td>
                        <td><?= e($company['owner_email']) ?><br><small><?= e($company['owner_phone']) ?></small></td>
                        <td><span class="<?= e(badge_class($company['status'])) ?>"><?= e(ucfirst($company['status'])) ?></span></td>
                        <td>
                            <div class="action-row">
                                <form action="<?= e(url('actions/company_approve.php')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="company_id" value="<?= e((string) $company['id']) ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="button button-small button-primary">Approve</button>
                                </form>
                                <form action="<?= e(url('actions/company_approve.php')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="company_id" value="<?= e((string) $company['id']) ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="button button-small button-danger">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($section === 'users'): ?>
    <section class="stacked-panel">
        <span class="eyebrow">System Users</span>
        <h2>All accounts</h2>
        <div class="table-wrapper">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($adminUsers as $user): ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', $user['role']))) ?></td>
                        <td><?= e($user['email']) ?><br><small><?= e($user['phone']) ?></small></td>
                        <td><span class="<?= e(badge_class($user['status'])) ?>"><?= e(ucfirst($user['status'])) ?></span></td>
                        <td><?= e(date('M d, Y', strtotime($user['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($section === 'bookings'): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Platform Bookings</span>
        <h2>All bookings</h2>
        <div class="table-wrapper">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>User</th>
                    <th>Company</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($adminBookings as $booking): ?>
                    <tr>
                        <td><?= e($booking['vehicle_name']) ?></td>
                        <td><?= e($booking['user_name']) ?></td>
                        <td><?= e($booking['company_name']) ?></td>
                        <td><?= e(format_money((float) $booking['total_price'])) ?></td>
                        <td><span class="<?= e(badge_class($booking['status'])) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <div class="action-row">
                                    <form action="<?= e(url('actions/booking_update.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="button button-small button-primary">Confirm</button>
                                    </form>
                                    <form action="<?= e(url('actions/booking_update.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="button button-small button-danger">Cancel</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted">Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
