    <?php
    $adminSearch = trim((string) ($_GET['search'] ?? ''));
    $adminStatus = trim((string) ($_GET['status'] ?? ''));
    $adminUserId = (int) ($_GET['user_id'] ?? 0);
    $adminStartDate = trim((string) ($_GET['start_date'] ?? ''));
    $adminEndDate = trim((string) ($_GET['end_date'] ?? ''));
    $revenueMonth = (int) ($_GET['month'] ?? date('n'));
    $revenueYear = (int) ($_GET['year'] ?? date('Y'));
    $revenueMonth = $revenueMonth >= 1 && $revenueMonth <= 12 ? $revenueMonth : (int) date('n');
    $revenueYear = $revenueYear >= 2020 && $revenueYear <= 2100 ? $revenueYear : (int) date('Y');
    $revenueStart = sprintf('%04d-%02d-01', $revenueYear, $revenueMonth);
    $revenueEnd = date('Y-m-t', strtotime($revenueStart));

    $bookingWhere = [];
    $bookingParams = [];

    if ($adminSearch !== '') {
        $bookingWhere[] = '(v.name LIKE ? OR renter.name LIKE ? OR renter.email LIKE ? OR company.company_name LIKE ?)';
        $like = '%' . $adminSearch . '%';
        array_push($bookingParams, $like, $like, $like, $like);
    }

    if ($adminStatus !== '' && in_array($adminStatus, ['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'], true)) {
        $bookingWhere[] = 'b.status = ?';
        $bookingParams[] = $adminStatus;
    }

    if ($adminUserId > 0) {
        $bookingWhere[] = 'b.user_id = ?';
        $bookingParams[] = $adminUserId;
    }

    if ($adminStartDate !== '') {
        $bookingWhere[] = 'b.start_date >= ?';
        $bookingParams[] = $adminStartDate;
    }

    if ($adminEndDate !== '') {
        $bookingWhere[] = 'b.end_date <= ?';
        $bookingParams[] = $adminEndDate;
    }

    $bookingWhereSql = $bookingWhere ? 'WHERE ' . implode(' AND ', $bookingWhere) : '';

    $adminBookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, renter.name AS user_name, renter.email AS user_email,
                company.company_name, company.name AS company_contact_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users renter ON renter.id = b.user_id
         JOIN users company ON company.id = b.company_id
         ' . $bookingWhereSql . '
         ORDER BY b.created_at DESC, b.id DESC
         LIMIT 100',
        $bookingParams
    );

    $bookingUsers = db_all(
        'SELECT DISTINCT u.id, u.name, u.email
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         ORDER BY u.name'
    );

    $allVehicles = db_all(
        'SELECT v.id, v.name, v.company_id, u.company_name
         FROM vehicles v
         JOIN users u ON u.id = v.company_id
         ORDER BY u.company_name, v.name'
    );
    $adminVehicleRows = db_all(
        'SELECT v.*, c.name AS category_name, t.name AS type_name, u.company_name, u.name AS company_contact_name
         FROM vehicles v
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         JOIN users u ON u.id = v.company_id
         ORDER BY u.company_name, v.name'
    );
    $companyOptions = db_all(
        'SELECT u.id, u.name, u.company_name
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE r.name = "company" AND u.status = "active"
         ORDER BY u.company_name, u.name'
    );

    $adminStats = db_one(
        'SELECT COUNT(*) AS total_bookings,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_bookings,
                SUM(CASE WHEN status IN ("approved", "confirmed", "completed") THEN 1 ELSE 0 END) AS active_bookings,
                SUM(CASE WHEN payment_status = "paid" THEN total_price ELSE 0 END) AS paid_revenue
         FROM bookings'
    );

    $ratingStats = site_review_stats();
    $siteRatings = db_all(
        'SELECT sr.*, u.name AS user_name, u.email AS user_email
         FROM site_reviews sr
         JOIN users u ON u.id = sr.user_id
         ORDER BY sr.created_at DESC'
    );
    $adminMaintenanceRows = db_all(
        'SELECT mr.*, v.name AS vehicle_name, company.company_name, company.name AS company_contact_name
         FROM maintenance_records mr
         JOIN vehicles v ON v.id = mr.vehicle_id
         JOIN users company ON company.id = mr.company_id
         ORDER BY mr.start_date DESC, mr.id DESC'
    );
    $adminServiceHistoryRows = db_all(
        'SELECT sh.*, v.name AS vehicle_name, company.company_name, company.name AS company_contact_name
         FROM service_history sh
         JOIN vehicles v ON v.id = sh.vehicle_id
         JOIN users company ON company.id = sh.company_id
         ORDER BY sh.service_date DESC, sh.id DESC'
    );
    $revenueRows = db_all(
        'SELECT DATE_FORMAT(b.start_date, "%Y-%m") AS revenue_month,
                COALESCE(SUM(b.total_price), 0) AS revenue_total,
                COUNT(*) AS paid_bookings
         FROM bookings b
         WHERE b.payment_status = "paid"
           AND b.start_date BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(b.start_date, "%Y-%m")
         ORDER BY revenue_month',
        [$revenueStart, $revenueEnd]
    );
    $monthlyRevenueTotal = 0;
    foreach ($revenueRows as $revenueRow) {
        $monthlyRevenueTotal += (float) $revenueRow['revenue_total'];
    }
    $adminMostRented = most_rented_vehicles(0, 5);

    $requests = db_all(
        'SELECT cr.*, u.name, u.email, u.phone, u.company_name, u.address, u.status AS company_status
         FROM company_requests cr
         JOIN users u ON u.id = cr.company_id
         ORDER BY cr.status = "pending" DESC, cr.id DESC'
    );

    $companies = db_all(
        'SELECT u.*
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.name = "company"
         ORDER BY u.id DESC'
    );
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Analytics</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">All bookings</a>
        <a class="<?= $section === 'revenue' ? 'active' : '' ?>" href="dashboard.php?section=revenue">Revenue</a>
        <a class="<?= $section === 'vehicles' ? 'active' : '' ?>" href="dashboard.php?section=vehicles">Vehicles</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
        <a class="<?= $section === 'site_ratings' ? 'active' : '' ?>" href="dashboard.php?section=site_ratings">Website ratings</a>
        <a class="<?= $section === 'companies' ? 'active' : '' ?>" href="dashboard.php?section=companies">Companies</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card">
            <strong><?= e($adminStats['total_bookings'] ?? 0) ?></strong>
            <span>Total bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e($adminStats['pending_bookings'] ?? 0) ?></strong>
            <span>Pending bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e(money($adminStats['paid_revenue'] ?? 0)) ?></strong>
            <span>Paid platform revenue</span>
        </article>
        <article class="metric-card">
            <strong><?= e(rating_text($ratingStats['average_rating'], $ratingStats['review_count'])) ?></strong>
            <span>Website ratings</span>
        </article>
    </section>

    <?php if ($section === 'overview'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Platform analytics</h2>
                    <p class="muted">Bookings, paid revenue, and public website rating health.</p>
                </div>
                <a class="btn light" href="dashboard.php?section=bookings">Manage bookings</a>
            </div>
            <div class="chart-bars">
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['pending_bookings'] ?? 0) * 12))) ?>%"></span><b>Pending</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['active_bookings'] ?? 0) * 12))) ?>%"></span><b>Active</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($ratingStats['average_rating'] * 20)))) ?>%"></span><b>Rating</b></div>
            </div>
            <h3>Most-rented vehicles</h3>
            <table>
                <tr>
                    <th>Vehicle</th>
                    <th>Location</th>
                    <th>Rentals</th>
                    <th>Paid revenue</th>
                </tr>
                <?php foreach ($adminMostRented as $vehicleRank): ?>
                    <tr>
                        <td><?= e($vehicleRank['name']) ?></td>
                        <td><?= e($vehicleRank['location']) ?></td>
                        <td><?= e((int) $vehicleRank['rental_count']) ?></td>
                        <td><?= e(money($vehicleRank['paid_revenue'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (!$adminMostRented): ?>
                <p class="muted">No vehicle rentals yet.</p>
            <?php endif; ?>
            <h3>Recent service ratings</h3>
            <div class="review-grid">
                <?php foreach ($siteRatings as $siteRating): ?>
                    <article class="review-card">
                        <div class="card-line">
                            <strong><?= e($siteRating['user_name']) ?></strong>
                            <span class="rating-pill"><?= e($siteRating['rating']) ?>/5</span>
                        </div>
                        <p><?= e($siteRating['feedback'] ?: 'No written feedback.') ?></p>
                        <small class="muted"><?= e($siteRating['user_email']) ?> Â| <?= e($siteRating['created_at']) ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$siteRatings): ?>
                <p class="muted">No website/service ratings yet.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'vehicles'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Add vehicle</h2>
                    <p class="muted">Platform admins can add vehicles for any active company.</p>
                </div>
            </div>
            <form class="admin-vehicle-form" action="actions/vehicle.php" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="vehicle_id" value="0">
                <select name="company_id" required>
                    <option value="">Company</option>
                    <?php foreach ($companyOptions as $companyOption): ?>
                        <option value="<?= e($companyOption['id']) ?>"><?= e($companyOption['company_name'] ?: $companyOption['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="name" placeholder="Vehicle name" required>
                <select name="category_id" required>
                    <option value="">Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type_id" required>
                    <option value="">Type</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= e($type['id']) ?>"><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="location" placeholder="Location" required>
                <input type="number" name="self_drive_price" min="1" step="0.01" placeholder="Self-drive price" required>
                <input type="number" name="with_driver_price" min="1" step="0.01" placeholder="With-driver price" required>
                <select name="status">
                    <option value="available">available</option>
                    <option value="unavailable">unavailable</option>
                </select>
                <input type="text" name="latitude" placeholder="Latitude">
                <input type="text" name="longitude" placeholder="Longitude">
                <input type="file" name="image">
                <input type="text" name="description" placeholder="Description">
                <button class="btn tiny" type="submit">Add</button>
            </form>
        </section>

        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>All vehicles</h2>
                    <p class="muted">Edit or delete platform vehicle records.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Company</th>
                        <th>Vehicle</th>
                        <th>Prices</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                    <?php foreach ($adminVehicleRows as $vehicleRow): ?>
                        <tr>
                            <td><?= e($vehicleRow['company_name'] ?: $vehicleRow['company_contact_name']) ?></td>
                            <td><?= e($vehicleRow['name']) ?><br><span class="muted"><?= e($vehicleRow['category_name'] . ' - ' . $vehicleRow['type_name'] . ' Â| ' . $vehicleRow['location']) ?></span></td>
                            <td>Self: <?= e(money($vehicleRow['self_drive_price'])) ?><br>Driver: <?= e(money($vehicleRow['with_driver_price'])) ?></td>
                            <td><span class="<?= e(role_badge($vehicleRow['status'])) ?>"><?= e($vehicleRow['status']) ?></span></td>
                            <td>
                                <form class="admin-vehicle-form compact" action="actions/vehicle.php" method="post" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="save">
                                    <input type="hidden" name="vehicle_id" value="<?= e($vehicleRow['id']) ?>">
                                    <select name="company_id" required>
                                        <?php foreach ($companyOptions as $companyOption): ?>
                                            <option value="<?= e($companyOption['id']) ?>" <?= (int) $vehicleRow['company_id'] === (int) $companyOption['id'] ? 'selected' : '' ?>><?= e($companyOption['company_name'] ?: $companyOption['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="name" value="<?= e($vehicleRow['name']) ?>" required>
                                    <select name="category_id" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e($category['id']) ?>" <?= (int) $vehicleRow['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="type_id" required>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?= e($type['id']) ?>" <?= (int) $vehicleRow['type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="location" value="<?= e($vehicleRow['location']) ?>" required>
                                    <input type="number" name="self_drive_price" min="1" step="0.01" value="<?= e($vehicleRow['self_drive_price']) ?>" required>
                                    <input type="number" name="with_driver_price" min="1" step="0.01" value="<?= e($vehicleRow['with_driver_price']) ?>" required>
                                    <select name="status">
                                        <option value="available" <?= $vehicleRow['status'] === 'available' ? 'selected' : '' ?>>available</option>
                                        <option value="unavailable" <?= $vehicleRow['status'] === 'unavailable' ? 'selected' : '' ?>>unavailable</option>
                                    </select>
                                    <input type="text" name="latitude" value="<?= e($vehicleRow['latitude']) ?>" placeholder="lat">
                                    <input type="text" name="longitude" value="<?= e($vehicleRow['longitude']) ?>" placeholder="lng">
                                    <input type="text" name="description" value="<?= e($vehicleRow['description']) ?>" placeholder="description">
                                    <input type="file" name="image">
                                    <button class="btn tiny" type="submit">Save</button>
                                </form>
                                <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete vehicle?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="vehicle_id" value="<?= e($vehicleRow['id']) ?>">
                                    <button class="btn danger tiny" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$adminVehicleRows): ?>
                <div class="empty-state"><h3>No vehicles</h3><p>Add vehicles for active companies above.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>All bookings</h2>
                    <p class="muted">Search, filter, modify, or cancel any booking on the platform.</p>
                </div>
            </div>
            <form class="search-bar admin-filter" method="get">
                <input type="hidden" name="section" value="bookings">
                <label><span>Search</span><input type="text" name="search" value="<?= e($adminSearch) ?>" placeholder="vehicle, user, company"></label>
                <label><span>Status</span>
                    <select name="status">
                        <option value="">All</option>
                        <?php foreach (['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'] as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= $adminStatus === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>User</span>
                    <select name="user_id">
                        <option value="0">All users</option>
                        <?php foreach ($bookingUsers as $bookingUser): ?>
                            <option value="<?= e($bookingUser['id']) ?>" <?= $adminUserId === (int) $bookingUser['id'] ? 'selected' : '' ?>><?= e($bookingUser['name'] . ' - ' . $bookingUser['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Start after</span><input type="date" name="start_date" value="<?= e($adminStartDate) ?>"></label>
                <label><span>End before</span><input type="date" name="end_date" value="<?= e($adminEndDate) ?>"></label>
                <button class="btn" type="submit">Filter</button>
            </form>

            <div class="table-scroll">
                <table>
                    <tr>
                        <th>User</th>
                        <th>Vehicle</th>
                        <th>Company</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Modify</th>
                    </tr>
                    <?php foreach ($adminBookings as $bookingRow): ?>
                        <tr>
                            <td><?= e($bookingRow['user_name']) ?><br><span class="muted"><?= e($bookingRow['user_email']) ?></span></td>
                            <td><?= e($bookingRow['vehicle_name']) ?></td>
                            <td><?= e($bookingRow['company_name'] ?: $bookingRow['company_contact_name']) ?></td>
                            <td><?= e($bookingRow['start_date']) ?> to <?= e($bookingRow['end_date']) ?></td>
                            <td><?= e(money($bookingRow['total_price'])) ?></td>
                            <td><span class="<?= e(role_badge($bookingRow['status'])) ?>"><?= e($bookingRow['status']) ?></span></td>
                            <td><span class="<?= e(payment_badge($bookingRow['payment_status'])) ?>"><?= e($bookingRow['payment_status']) ?></span></td>
                            <td>
                                <form class="admin-booking-form" action="actions/booking.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="admin_update">
                                    <input type="hidden" name="booking_id" value="<?= e($bookingRow['id']) ?>">
                                    <select name="vehicle_id" required>
                                        <?php foreach ($allVehicles as $vehicleOption): ?>
                                            <option value="<?= e($vehicleOption['id']) ?>" <?= (int) $bookingRow['vehicle_id'] === (int) $vehicleOption['id'] ? 'selected' : '' ?>><?= e($vehicleOption['name'] . ' - ' . $vehicleOption['company_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="date" name="start_date" value="<?= e($bookingRow['start_date']) ?>" required>
                                    <input type="date" name="end_date" value="<?= e($bookingRow['end_date']) ?>" required>
                                    <label class="check-line compact"><input type="checkbox" name="with_driver" value="1" <?= $bookingRow['with_driver'] ? 'checked' : '' ?>> Driver</label>
                                    <select name="status" required>
                                        <?php foreach (['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'] as $statusOption): ?>
                                            <option value="<?= e($statusOption) ?>" <?= $bookingRow['status'] === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="payment_status" required>
                                        <?php foreach (['cash_due', 'pending', 'paid', 'failed', 'refunded'] as $payOption): ?>
                                            <option value="<?= e($payOption) ?>" <?= $bookingRow['payment_status'] === $payOption ? 'selected' : '' ?>><?= e($payOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="agent_note" value="<?= e($bookingRow['agent_note']) ?>" placeholder="note">
                                    <button class="btn tiny" type="submit">Save</button>
                                </form>
                                <form action="actions/booking.php" method="post" onsubmit="return confirm('Cancel this booking?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="admin_cancel">
                                    <input type="hidden" name="booking_id" value="<?= e($bookingRow['id']) ?>">
                                    <button class="btn danger tiny" type="submit">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$adminBookings): ?>
                <div class="empty-state"><h3>No bookings found</h3><p>Try changing the filters.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'revenue'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Monthly revenue</h2>
                    <p class="muted">Platform revenue is calculated only from bookings marked paid.</p>
                </div>
                <strong><?= e(money($monthlyRevenueTotal)) ?></strong>
            </div>
            <form class="search-bar" method="get">
                <input type="hidden" name="section" value="revenue">
                <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= e($revenueMonth) ?>"></label>
                <label><span>Year</span><input type="number" name="year" min="2020" max="2100" value="<?= e($revenueYear) ?>"></label>
                <button class="btn" type="submit">Apply</button>
            </form>
            <div class="chart-bars revenue-bars">
                <?php foreach ($revenueRows as $revenueRow): ?>
                    <?php $barHeight = $monthlyRevenueTotal > 0 ? max(8, (int) (((float) $revenueRow['revenue_total'] / $monthlyRevenueTotal) * 100)) : 8; ?>
                    <div><span style="height: <?= e($barHeight) ?>%"></span><b><?= e($revenueRow['revenue_month']) ?></b><small><?= e(money($revenueRow['revenue_total'])) ?></small></div>
                <?php endforeach; ?>
            </div>
            <?php if (!$revenueRows): ?>
                <div class="empty-state"><h3>No paid revenue</h3><p>No paid bookings were found for this month.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'site_ratings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Website/service ratings</h2>
                    <p class="muted">All feedback submitted by logged-in users.</p>
                </div>
                <strong><?= e(rating_text($ratingStats['average_rating'], $ratingStats['review_count'])) ?></strong>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Submitted</th>
                    </tr>
                    <?php foreach ($siteRatings as $siteRating): ?>
                        <tr>
                            <td><?= e($siteRating['user_name']) ?><br><span class="muted"><?= e($siteRating['user_email']) ?></span></td>
                            <td><span class="rating-pill"><?= e($siteRating['rating']) ?>/5</span></td>
                            <td><?= e($siteRating['feedback'] ?: 'No written feedback.') ?></td>
                            <td><?= e($siteRating['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$siteRatings): ?>
                <div class="empty-state"><h3>No website ratings</h3><p>User service ratings will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'maintenance'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>All maintenance records</h2>
                    <p class="muted">Platform-wide view of company and agent maintenance blocks.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Company</th>
                        <th>Vehicle</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Cost</th>
                    </tr>
                    <?php foreach ($adminMaintenanceRows as $maintenanceRow): ?>
                        <tr>
                            <td><?= e($maintenanceRow['company_name'] ?: $maintenanceRow['company_contact_name']) ?></td>
                            <td><?= e($maintenanceRow['vehicle_name']) ?><br><span class="muted"><?= e($maintenanceRow['title']) ?></span></td>
                            <td><?= e($maintenanceRow['start_date']) ?> to <?= e($maintenanceRow['end_date']) ?></td>
                            <td><span class="<?= e(role_badge($maintenanceRow['status'])) ?>"><?= e($maintenanceRow['status']) ?></span></td>
                            <td><?= e(money($maintenanceRow['cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$adminMaintenanceRows): ?>
                <div class="empty-state"><h3>No maintenance records</h3><p>Maintenance records from companies and agents will appear here.</p></div>
            <?php endif; ?>
        </section>

        <section class="box dashboard-panel">
            <h2>All service history</h2>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Company</th>
                        <th>Vehicle</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Cost</th>
                    </tr>
                    <?php foreach ($adminServiceHistoryRows as $serviceRow): ?>
                        <tr>
                            <td><?= e($serviceRow['company_name'] ?: $serviceRow['company_contact_name']) ?></td>
                            <td><?= e($serviceRow['vehicle_name']) ?></td>
                            <td><strong><?= e($serviceRow['service_type']) ?></strong><br><span class="muted"><?= e($serviceRow['provider'] ?: 'No provider') ?> Â| <?= e($serviceRow['mileage']) ?> km</span></td>
                            <td><?= e($serviceRow['service_date']) ?></td>
                            <td><?= e(money($serviceRow['cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$adminServiceHistoryRows): ?>
                <div class="empty-state"><h3>No service history</h3><p>Fleet service history from companies and agents will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'companies'): ?>
    <section class="box">
        <h2>Company approval requests</h2>
        <table>
            <tr>
                <th>Company</th>
                <th>Request</th>
                <th>Requested data</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($requests as $request): ?>
                <?php
                $data = json_decode($request['requested_data'] ?? '', true);
                if (!is_array($data)) {
                    $data = [];
                }
                ?>
                <tr>
                    <td>
                        <?= e($request['company_name'] ?: $request['name']) ?><br>
                        <span class="muted"><?= e($request['email']) ?></span>
                    </td>
                    <td><?= e($request['request_type']) ?></td>
                    <td>
                        <?php if ($data): ?>
                            Name: <?= e($data['company_name'] ?? '-') ?><br>
                            Phone: <?= e($data['phone'] ?? '-') ?><br>
                            Address: <?= e($data['address'] ?? '-') ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= e(role_badge($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <form class="decision-form" action="actions/auth.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="review_company_request">
                                <input type="hidden" name="request_id" value="<?= e($request['id']) ?>">
                                <input type="text" name="admin_note" placeholder="admin note">
                                <button class="btn tiny" name="decision" value="approved">Approve</button>
                                <button class="btn danger tiny" name="decision" value="rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            Reviewed
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$requests): ?>
            <p>No company requests yet.</p>
        <?php endif; ?>
    </section>

    <section class="box">
        <h2>Companies</h2>
        <table>
            <tr>
                <th>Company</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?= e($company['company_name'] ?: $company['name']) ?></td>
                    <td><?= e($company['email']) ?></td>
                    <td><?= e($company['phone']) ?></td>
                    <td><span class="<?= e(role_badge($company['status'])) ?>"><?= e($company['status']) ?></span></td>
                    <td>
                        <?php if ($company['status'] !== 'inactive'): ?>
                            <form action="actions/auth.php" method="post" onsubmit="return confirm('Deactivate this company?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="admin_delete_company">
                                <input type="hidden" name="company_id" value="<?= e($company['id']) ?>">
                                <button class="btn danger tiny" type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            Inactive
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <?php endif; ?>


