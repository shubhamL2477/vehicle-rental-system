    <?php
    if ($editVehicle) {
        $section = 'add_vehicle';
    }

    $vehicles = db_all(
        'SELECT v.*, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.company_id = ?
         ORDER BY v.id DESC',
        [$me['company_id']]
    );
    $bookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
         ORDER BY b.id DESC',
        [$me['company_id']]
    );
    $maintenanceRows = db_all(
        'SELECT mr.*, v.name AS vehicle_name
         FROM maintenance_records mr
         JOIN vehicles v ON v.id = mr.vehicle_id
         WHERE mr.company_id = ?
         ORDER BY mr.start_date DESC, mr.id DESC',
        [$me['company_id']]
    );
    $serviceHistoryRows = db_all(
        'SELECT sh.*, v.name AS vehicle_name
         FROM service_history sh
         JOIN vehicles v ON v.id = sh.vehicle_id
         WHERE sh.company_id = ?
         ORDER BY sh.service_date DESC, sh.id DESC',
        [$me['company_id']]
    );
    $overdueReturns = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
           AND b.status IN ("approved", "confirmed")
           AND b.end_date < CURDATE()
         ORDER BY b.end_date ASC',
        [$me['company_id']]
    );
    $agentMostRented = most_rented_vehicles((int) $me['company_id'], 5);

    $pendingCount = 0;
    $approvedCount = 0;

    foreach ($bookings as $bookingCountRow) {
        if ($bookingCountRow['status'] === 'pending') {
            $pendingCount++;
        }

        if ($bookingCountRow['status'] === 'approved') {
            $approvedCount++;
        }
    }
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Overview</a>
        <a class="<?= $section === 'add_vehicle' ? 'active' : '' ?>" href="dashboard.php?section=add_vehicle">Add vehicle</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
        <a class="<?= $section === 'vehicles' ? 'active' : '' ?>" href="dashboard.php?section=vehicles">Vehicles</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Booking requests</a>
        <a href="payments.php">Payments</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card">
            <strong><?= e(count($vehicles)) ?></strong>
            <span>Company vehicles</span>
        </article>
        <article class="metric-card">
            <strong><?= e($pendingCount) ?></strong>
            <span>Pending requests</span>
        </article>
        <article class="metric-card">
            <strong><?= e($approvedCount) ?></strong>
            <span>Approved bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e(count($maintenanceRows)) ?></strong>
            <span>Maintenance blocks</span>
        </article>
        <article class="metric-card">
            <strong><?= e(count($overdueReturns)) ?></strong>
            <span>Overdue returns</span>
        </article>
    </section>

    <?php if ($section === 'overview'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Agent overview</h2>
                    <p class="muted">Quick view of fleet work, maintenance and booking requests.</p>
                </div>
                <a class="btn" href="dashboard.php?section=add_vehicle">Add vehicle</a>
            </div>

            <div class="booking-card-grid">
                <?php foreach (array_slice($bookings, 0, 3) as $b): ?>
                    <article class="booking-summary-card">
                        <div class="card-line">
                            <strong><?= e($b['vehicle_name']) ?></strong>
                            <span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span>
                        </div>
                        <p><?= e($b['user_name']) ?></p>
                        <p><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></p>
                        <p><b><?= e(money($b['total_price'])) ?></b> Â| <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$bookings): ?>
                <div class="empty-state">
                    <h3>No booking requests yet</h3>
                    <p>New customer bookings will appear here for approval.</p>
                </div>
            <?php endif; ?>
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
                <?php foreach ($agentMostRented as $vehicleRank): ?>
                    <tr>
                        <td><?= e($vehicleRank['name']) ?></td>
                        <td><?= e($vehicleRank['location']) ?></td>
                        <td><?= e((int) $vehicleRank['rental_count']) ?></td>
                        <td><?= e(money($vehicleRank['paid_revenue'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (!$agentMostRented): ?>
                <p>No vehicle rentals yet.</p>
            <?php endif; ?>
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
                <?php foreach ($overdueReturns as $overdue): ?>
                    <tr>
                        <td><?= e($overdue['user_name']) ?></td>
                        <td><?= e($overdue['vehicle_name']) ?></td>
                        <td><?= e($overdue['end_date']) ?></td>
                        <td><span class="<?= e(role_badge($overdue['status'])) ?>"><?= e($overdue['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (!$overdueReturns): ?>
                <p class="muted">No overdue returns right now.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'add_vehicle'): ?>
        <form class="box simple-form" action="actions/vehicle.php" method="post" enctype="multipart/form-data">
            <h2><?= $editVehicle ? 'Update vehicle' : 'Add vehicle' ?></h2>
            <p class="muted">Keep vehicle details complete so customers can compare price, category and location clearly.</p>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="vehicle_id" value="<?= e($editVehicle['id'] ?? 0) ?>">

            <label>Name</label>
            <input type="text" name="name" value="<?= e($editVehicle['name'] ?? '') ?>" required>
            <label>Category</label>
            <select name="category_id" data-category-select required>
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>" <?= (int) ($editVehicle['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Type</label>
            <select name="type_id" data-type-select required>
                <option value="">Select type</option>
                <?php foreach ($types as $type): ?>
                    <option
                        value="<?= e($type['id']) ?>"
                        data-category="<?= e($type['category_id']) ?>"
                        <?= (int) ($editVehicle['type_id'] ?? 0) === (int) $type['id'] ? 'selected' : '' ?>
                    >
                        <?= e($type['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>Location</label>
            <input type="text" name="location" value="<?= e($editVehicle['location'] ?? '') ?>" required>
            <label>Self-drive price / day</label>
            <input type="number" name="self_drive_price" value="<?= e($editVehicle['self_drive_price'] ?? '') ?>" min="1" step="0.01" required>
            <label>With-driver price / day</label>
            <input type="number" name="with_driver_price" value="<?= e($editVehicle['with_driver_price'] ?? '') ?>" min="1" step="0.01" required>
            <label>Status</label>
            <select name="status">
                <option value="available" <?= ($editVehicle['status'] ?? '') === 'available' ? 'selected' : '' ?>>available</option>
                <option value="unavailable" <?= ($editVehicle['status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>unavailable</option>
            </select>
            <label>Latitude</label>
            <input type="text" name="latitude" value="<?= e($editVehicle['latitude'] ?? '') ?>">
            <label>Longitude</label>
            <input type="text" name="longitude" value="<?= e($editVehicle['longitude'] ?? '') ?>">
            <label>Image</label>
            <input type="file" name="image">
            <label>Description</label>
            <textarea name="description"><?= e($editVehicle['description'] ?? '') ?></textarea>
            <button class="btn" type="submit"><?= $editVehicle ? 'Update' : 'Add' ?> vehicle</button>
            <?php if ($editVehicle): ?>
                <a class="btn light" href="dashboard.php?section=vehicles">Cancel edit</a>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <?php if ($section === 'maintenance'): ?>
        <section class="grid two maintenance-workspace">
            <form class="box simple-form" action="actions/vehicle.php" method="post">
                <h2>Add maintenance record</h2>
                <p class="muted">Scheduled or in-progress records block customer bookings automatically.</p>
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

        <section class="box">
            <h2>Maintenance records</h2>
            <table>
                <tr>
                    <th>Vehicle</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($maintenanceRows as $m): ?>
                    <tr>
                        <td><?= e($m['vehicle_name']) ?></td>
                        <td><strong><?= e($m['title']) ?></strong><br><?= e($m['start_date']) ?> to <?= e($m['end_date']) ?><br><span class="muted"><?= e($m['description'] ?: 'No description') ?> Â| <?= e(money($m['cost'])) ?></span></td>
                        <td><span class="<?= e(role_badge($m['status'])) ?>"><?= e($m['status']) ?></span></td>
                        <td>
                            <form class="maintenance-inline-form" action="actions/vehicle.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="maintenance">
                                <input type="hidden" name="maintenance_id" value="<?= e($m['id']) ?>">
                                <input type="hidden" name="vehicle_id" value="<?= e($m['vehicle_id']) ?>">
                                <input type="text" name="title" value="<?= e($m['title']) ?>" required>
                                <input type="date" name="start_date" value="<?= e($m['start_date']) ?>" required>
                                <input type="date" name="end_date" value="<?= e($m['end_date']) ?>" required>
                                <input type="number" name="cost" min="0" step="0.01" value="<?= e($m['cost']) ?>">
                                <select name="status">
                                    <?php foreach (['scheduled', 'in_progress', 'completed', 'cancelled'] as $maintenanceStatus): ?>
                                        <option value="<?= e($maintenanceStatus) ?>" <?= $m['status'] === $maintenanceStatus ? 'selected' : '' ?>><?= e($maintenanceStatus) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="description" value="<?= e($m['description']) ?>" placeholder="description">
                                <button class="btn tiny" type="submit">Save</button>
                            </form>
                            <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete this maintenance record?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="maintenance_delete">
                                <input type="hidden" name="maintenance_id" value="<?= e($m['id']) ?>">
                                <button class="btn danger tiny" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (!$maintenanceRows): ?>
                <div class="empty-state">
                    <h3>No maintenance records</h3>
                    <p>Add a record when a vehicle is not ready for rental.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="box">
            <h2>Service history</h2>
            <table>
                <tr>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Cost</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($serviceHistoryRows as $serviceRow): ?>
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
            <?php if (!$serviceHistoryRows): ?>
                <div class="empty-state">
                    <h3>No service history</h3>
                    <p>Add service history after vehicle maintenance is completed.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'vehicles'): ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Vehicles</h2>
                <p class="muted">Edit, delete, or review fleet details separately from bookings.</p>
            </div>
            <a class="btn" href="dashboard.php?section=add_vehicle">Add vehicle</a>
        </div>
        <table>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Location</th>
                <th>Prices</th>
                <th>Status</th>
                <th>GPS</th>
                <th>Action</th>
            </tr>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?= e($v['name']) ?></td>
                    <td><?= e($v['category_name']) ?> - <?= e($v['type_name']) ?></td>
                    <td><?= e($v['location']) ?></td>
                    <td>Self: <?= e(money($v['self_drive_price'])) ?><br>Driver: <?= e(money($v['with_driver_price'])) ?></td>
                    <td><span class="<?= e(role_badge($v['status'])) ?>"><?= e($v['status']) ?></span></td>
                    <td><?= e($v['latitude'] ?: '-') ?>, <?= e($v['longitude'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn tiny" href="dashboard.php?section=add_vehicle&edit=<?= e($v['id']) ?>">Edit</a>
                        <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete vehicle?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="vehicle_id" value="<?= e($v['id']) ?>">
                            <button class="btn danger tiny" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$vehicles): ?>
            <div class="empty-state">
                <h3>No vehicles added</h3>
                <p>Add the first company vehicle from the Add vehicle tab.</p>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Booking requests</h2>
                <p class="muted">Review customer documents, approve valid requests, and reject unavailable trips.</p>
            </div>
        </div>
        <table>
            <tr>
                <th>User</th>
                <th>Vehicle</th>
                <th>Dates</th>
                <th>Mode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Files</th>
                <th>Action</th>
            </tr>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= e($b['user_name']) ?></td>
                    <td><?= e($b['vehicle_name']) ?></td>
                    <td><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></td>
                    <td><?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></td>
                    <td><?= e(money($b['total_price'])) ?></td>
                    <td><span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span></td>
                    <td>
                        <?php if ($b['with_driver']): ?>
                            Not needed
                        <?php else: ?>
                            <a href="uploads/documents/<?= e($b['document_file']) ?>" target="_blank">ID</a>
                            |
                            <a href="uploads/documents/<?= e($b['license_file']) ?>" target="_blank">License</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($b['status'] === 'pending'): ?>
                            <form class="decision-form" action="actions/booking.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="decide">
                                <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                <input type="text" name="agent_note" placeholder="small note">
                                <button class="btn tiny" name="status" value="approved">Approve</button>
                                <button class="btn danger tiny" name="status" value="rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            Done
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$bookings): ?>
            <div class="empty-state">
                <h3>No booking requests</h3>
                <p>Customer booking requests will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>


