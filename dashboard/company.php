    <?php
    $companyId = managed_company_id($me);
    $vehicles = db_all(
        'SELECT v.*, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.company_id = ?
         ORDER BY v.id DESC',
        [$companyId]
    );
    $agents = db_all('SELECT * FROM users WHERE company_id = ? ORDER BY id DESC', [$companyId]);
    $companyRequests = db_all('SELECT * FROM company_requests WHERE company_id = ? ORDER BY id DESC', [$companyId]);
    $companyBookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name, u.email AS user_email
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
         ORDER BY b.created_at DESC, b.id DESC',
        [$companyId]
    );
    $companyMaintenanceRows = db_all(
        'SELECT mr.*, v.name AS vehicle_name
         FROM maintenance_records mr
         JOIN vehicles v ON v.id = mr.vehicle_id
         WHERE mr.company_id = ?
         ORDER BY mr.start_date DESC, mr.id DESC',
        [$companyId]
    );
    $companyServiceHistoryRows = db_all(
        'SELECT sh.*, v.name AS vehicle_name
         FROM service_history sh
         JOIN vehicles v ON v.id = sh.vehicle_id
         WHERE sh.company_id = ?
         ORDER BY sh.service_date DESC, sh.id DESC',
        [$companyId]
    );
    $companyOverdueReturns = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
           AND b.status IN ("approved", "confirmed")
           AND b.end_date < CURDATE()
         ORDER BY b.end_date ASC',
        [$companyId]
    );
    $companyRevenueMonth = (int) ($_GET['month'] ?? date('n'));
    $companyRevenueYear = (int) ($_GET['year'] ?? date('Y'));
    $companyRevenueMonth = $companyRevenueMonth >= 1 && $companyRevenueMonth <= 12 ? $companyRevenueMonth : (int) date('n');
    $companyRevenueYear = $companyRevenueYear >= 2020 && $companyRevenueYear <= 2100 ? $companyRevenueYear : (int) date('Y');
    $companyRevenueStart = sprintf('%04d-%02d-01', $companyRevenueYear, $companyRevenueMonth);
    $companyRevenueEnd = date('Y-m-t', strtotime($companyRevenueStart));
    $companyRevenueRows = db_all(
        'SELECT v.name AS vehicle_name, COALESCE(SUM(b.total_price), 0) AS revenue_total, COUNT(*) AS paid_bookings
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.company_id = ?
           AND b.payment_status = "paid"
           AND b.start_date BETWEEN ? AND ?
         GROUP BY v.id, v.name
         ORDER BY revenue_total DESC',
        [$companyId, $companyRevenueStart, $companyRevenueEnd]
    );
    $companyRevenueTotal = 0;
    foreach ($companyRevenueRows as $companyRevenueRow) {
        $companyRevenueTotal += (float) $companyRevenueRow['revenue_total'];
    }
    $companyMostRented = most_rented_vehicles($companyId, 5);
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Overview</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Bookings</a>
        <a class="<?= $section === 'revenue' ? 'active' : '' ?>" href="dashboard.php?section=revenue">Revenue</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card"><strong><?= e(count($vehicles)) ?></strong><span>Vehicles</span></article>
        <article class="metric-card"><strong><?= e(count($agents)) ?></strong><span>Agents</span></article>
        <article class="metric-card"><strong><?= e(count($companyBookings)) ?></strong><span>Bookings</span></article>
        <article class="metric-card"><strong><?= e(count($companyOverdueReturns)) ?></strong><span>Overdue returns</span></article>
        <article class="metric-card"><strong><?= e(money($companyRevenueTotal)) ?></strong><span>Monthly paid revenue</span></article>
    </section>

    <?php if ($section === 'overview'): ?>
    <section class="grid two">
        <form class="box simple-form" action="actions/auth.php" method="post">
            <h2>Company profile</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_company">
            <label>Company name</label>
            <input type="text" name="company_name" value="<?= e($me['company_name']) ?>" required>
            <label>Phone</label>
            <input type="text" name="phone" value="<?= e($me['phone']) ?>" required>
            <label>Address</label>
            <input type="text" name="address" value="<?= e($me['address']) ?>">
            <button class="btn" type="submit">Send update request</button>
        </form>

        <form class="box simple-form" action="actions/auth.php" method="post">
            <h2>Add agent</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_agent">
            <label>Agent name</label>
            <input type="text" name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Phone</label>
            <input type="text" name="phone" required>
            <label>Password</label>
            <input type="password" name="password" minlength="6" required>
            <button class="btn" type="submit">Create agent</button>
        </form>
    </section>

    <section class="box">
        <h2>Most-rented vehicles</h2>
        <table>
            <tr>
                <th>Vehicle</th>
                <th>Location</th>
                <th>Rentals</th>
                <th>Paid revenue</th>
            </tr>
            <?php foreach ($companyMostRented as $vehicleRank): ?>
                <tr>
                    <td><?= e($vehicleRank['name']) ?></td>
                    <td><?= e($vehicleRank['location']) ?></td>
                    <td><?= e((int) $vehicleRank['rental_count']) ?></td>
                    <td><?= e(money($vehicleRank['paid_revenue'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$companyMostRented): ?>
            <p>No vehicle rentals yet.</p>
        <?php endif; ?>
    </section>

    <section class="box">
        <h2>Company approval requests</h2>
        <table>
            <tr>
                <th>Type</th>
                <th>Status</th>
                <th>Admin note</th>
                <th>Date</th>
            </tr>
            <?php foreach ($companyRequests as $request): ?>
                <tr>
                    <td><?= e($request['request_type']) ?></td>
                    <td><span class="<?= e(role_badge($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    <td><?= e($request['admin_note'] ?: '-') ?></td>
                    <td><?= e($request['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$companyRequests): ?>
            <p>No company requests yet.</p>
        <?php endif; ?>

        <form action="actions/auth.php" method="post" onsubmit="return confirm('Request admin to delete this company?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_company_delete">
            <button class="btn danger" type="submit">Request company deletion</button>
        </form>
    </section>

    <section class="box">
        <h2>Agents</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <?php foreach ($agents as $a): ?>
                <tr>
                    <td colspan="4">
                        <form class="decision-form" action="actions/auth.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="agent_id" value="<?= e($a['id']) ?>">
                            <input type="text" name="name" value="<?= e($a['name']) ?>" required>
                            <span><?= e($a['email']) ?></span>
                            <input type="text" name="phone" value="<?= e($a['phone']) ?>" required>
                            <button class="btn tiny" name="action" value="edit_agent">Save</button>
                            <button class="btn danger tiny" name="action" value="delete_agent" onclick="return confirm('Delete agent?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$agents): ?>
            <p>No agents added yet.</p>
        <?php endif; ?>
    </section>

    <section class="box">
        <h2>Company vehicles summary</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Location</th>
                <th>Self drive</th>
                <th>With driver</th>
                <th>Status</th>
            </tr>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?= e($v['name']) ?></td>
                    <td><?= e($v['category_name']) ?> - <?= e($v['type_name']) ?></td>
                    <td><?= e($v['location']) ?></td>
                    <td><?= e(money($v['self_drive_price'])) ?></td>
                    <td><?= e(money($v['with_driver_price'])) ?></td>
                    <td><span class="<?= e(role_badge($v['status'])) ?>"><?= e($v['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>

    <section class="box">
        <h2>Overdue returns</h2>
        <table>
            <tr>
                <th>User</th>
                <th>Vehicle</th>
                <th>Return date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($companyOverdueReturns as $overdue): ?>
                <tr>
                    <td><?= e($overdue['user_name']) ?></td>
                    <td><?= e($overdue['vehicle_name']) ?></td>
                    <td><?= e($overdue['end_date']) ?></td>
                    <td><span class="<?= e(role_badge($overdue['status'])) ?>"><?= e($overdue['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$companyOverdueReturns): ?>
            <p class="muted">No overdue returns right now.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($section === 'maintenance'): ?>
        <section class="grid two maintenance-workspace">
            <form class="box simple-form" action="actions/vehicle.php" method="post">
                <h2>Add maintenance record</h2>
                <p class="muted">Scheduled or in-progress records automatically block bookings for that date range.</p>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="maintenance">
                <label>Vehicle</label>
                <select name="vehicle_id" required>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?= e($v['id']) ?>"><?= e($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Title</label>
                <input type="text" name="title" required>
                <label>Start date</label>
                <input type="date" name="start_date" required>
                <label>End date</label>
                <input type="date" name="end_date" required>
                <label>Cost</label>
                <input type="number" name="cost" min="0" step="0.01" value="0">
                <label>Status</label>
                <select name="status">
                    <option value="scheduled">scheduled</option>
                    <option value="in_progress">in_progress</option>
                    <option value="completed">completed</option>
                    <option value="cancelled">cancelled</option>
                </select>
                <label>Description</label>
                <textarea name="description"></textarea>
                <button class="btn" type="submit">Save maintenance</button>
            </form>

            <form class="box simple-form" action="actions/vehicle.php" method="post">
                <h2>Add service history</h2>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="service_history">
                <label>Vehicle</label>
                <select name="vehicle_id" required>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?= e($v['id']) ?>"><?= e($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Service type</label>
                <input type="text" name="service_type" required>
                <label>Provider</label>
                <input type="text" name="provider">
                <label>Mileage</label>
                <input type="number" name="mileage" min="0" value="0">
                <label>Cost</label>
                <input type="number" name="cost" min="0" step="0.01" value="0">
                <label>Service date</label>
                <input type="date" name="service_date" required>
                <label>Notes</label>
                <textarea name="notes"></textarea>
                <button class="btn" type="submit">Save service history</button>
            </form>
        </section>

        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Maintenance records</h2>
                    <p class="muted">Edit or delete maintenance entries for company vehicles.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Vehicle</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                    <?php foreach ($companyMaintenanceRows as $maintenanceRow): ?>
                        <tr>
                            <td><?= e($maintenanceRow['vehicle_name']) ?></td>
                            <td>
                                <strong><?= e($maintenanceRow['title']) ?></strong><br>
                                <?= e($maintenanceRow['start_date']) ?> to <?= e($maintenanceRow['end_date']) ?><br>
                                <span class="muted"><?= e($maintenanceRow['description'] ?: 'No description') ?> Â| <?= e(money($maintenanceRow['cost'])) ?></span>
                            </td>
                            <td><span class="<?= e(role_badge($maintenanceRow['status'])) ?>"><?= e($maintenanceRow['status']) ?></span></td>
                            <td>
                                <form class="maintenance-inline-form" action="actions/vehicle.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="maintenance">
                                    <input type="hidden" name="maintenance_id" value="<?= e($maintenanceRow['id']) ?>">
                                    <input type="hidden" name="vehicle_id" value="<?= e($maintenanceRow['vehicle_id']) ?>">
                                    <input type="text" name="title" value="<?= e($maintenanceRow['title']) ?>" required>
                                    <input type="date" name="start_date" value="<?= e($maintenanceRow['start_date']) ?>" required>
                                    <input type="date" name="end_date" value="<?= e($maintenanceRow['end_date']) ?>" required>
                                    <input type="number" name="cost" min="0" step="0.01" value="<?= e($maintenanceRow['cost']) ?>">
                                    <select name="status">
                                        <?php foreach (['scheduled', 'in_progress', 'completed', 'cancelled'] as $maintenanceStatus): ?>
                                            <option value="<?= e($maintenanceStatus) ?>" <?= $maintenanceRow['status'] === $maintenanceStatus ? 'selected' : '' ?>><?= e($maintenanceStatus) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="description" value="<?= e($maintenanceRow['description']) ?>" placeholder="description">
                                    <button class="btn tiny" type="submit">Save</button>
                                </form>
                                <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete this maintenance record?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="maintenance_delete">
                                    <input type="hidden" name="maintenance_id" value="<?= e($maintenanceRow['id']) ?>">
                                    <button class="btn danger tiny" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$companyMaintenanceRows): ?>
                <div class="empty-state"><h3>No maintenance records</h3><p>Add a maintenance record to block unavailable rental dates.</p></div>
            <?php endif; ?>
        </section>

        <section class="box dashboard-panel">
            <h2>Vehicle service history</h2>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Vehicle</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Cost</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($companyServiceHistoryRows as $serviceRow): ?>
                        <tr>
                            <td><?= e($serviceRow['vehicle_name']) ?></td>
                            <td><strong><?= e($serviceRow['service_type']) ?></strong><br><span class="muted"><?= e($serviceRow['provider'] ?: 'No provider') ?> Â| <?= e($serviceRow['mileage']) ?> km</span><br><?= e($serviceRow['notes'] ?: '') ?></td>
                            <td><?= e($serviceRow['service_date']) ?></td>
                            <td><?= e(money($serviceRow['cost'])) ?></td>
                            <td>
                                <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete this service history record?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="service_history_delete">
                                    <input type="hidden" name="service_id" value="<?= e($serviceRow['id']) ?>">
                                    <button class="btn danger tiny" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$companyServiceHistoryRows): ?>
                <div class="empty-state"><h3>No service history</h3><p>Service records for fleet vehicles will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Company bookings</h2>
                    <p class="muted">Company-level view of customer bookings and payment states.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>User</th>
                        <th>Vehicle</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                    <?php foreach ($companyBookings as $companyBooking): ?>
                        <tr>
                            <td><?= e($companyBooking['user_name']) ?><br><span class="muted"><?= e($companyBooking['user_email']) ?></span></td>
                            <td><?= e($companyBooking['vehicle_name']) ?></td>
                            <td><?= e($companyBooking['start_date']) ?> to <?= e($companyBooking['end_date']) ?></td>
                            <td><?= e(money($companyBooking['total_price'])) ?></td>
                            <td><span class="<?= e(role_badge($companyBooking['status'])) ?>"><?= e($companyBooking['status']) ?></span></td>
                            <td><span class="<?= e(payment_badge($companyBooking['payment_status'])) ?>"><?= e($companyBooking['payment_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$companyBookings): ?>
                <div class="empty-state"><h3>No bookings yet</h3><p>Bookings for company vehicles will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'revenue'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Company revenue</h2>
                    <p class="muted">Only paid bookings for your company are included.</p>
                </div>
                <strong><?= e(money($companyRevenueTotal)) ?></strong>
            </div>
            <form class="search-bar" method="get">
                <input type="hidden" name="section" value="revenue">
                <label><span>Month</span><input type="number" min="1" max="12" name="month" value="<?= e($companyRevenueMonth) ?>"></label>
                <label><span>Year</span><input type="number" min="2020" max="2100" name="year" value="<?= e($companyRevenueYear) ?>"></label>
                <button class="btn" type="submit">Apply</button>
            </form>
            <div class="chart-bars revenue-bars">
                <?php foreach ($companyRevenueRows as $companyRevenueRow): ?>
                    <?php $barHeight = $companyRevenueTotal > 0 ? max(8, (int) (((float) $companyRevenueRow['revenue_total'] / $companyRevenueTotal) * 100)) : 8; ?>
                    <div><span style="height: <?= e($barHeight) ?>%"></span><b><?= e($companyRevenueRow['vehicle_name']) ?></b><small><?= e(money($companyRevenueRow['revenue_total'])) ?></small></div>
                <?php endforeach; ?>
            </div>
            <?php if (!$companyRevenueRows): ?>
                <div class="empty-state"><h3>No paid revenue</h3><p>No paid bookings were found for this month.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>


