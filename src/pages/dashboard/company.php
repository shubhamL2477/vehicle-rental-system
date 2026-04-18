<?php
?>

<?php if ($role === 'company' && $section === 'profile' && $companyProfile): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Company Profile</span>
        <h2>Manage company details</h2>
        <form action="<?= e(url('actions/company_profile.php')) ?>" method="post" class="form-grid">
            <?= csrf_field() ?>
            <label>
                <span>Company name</span>
                <input type="text" name="name" value="<?= e($companyProfile['name']) ?>" required>
            </label>
            <label>
                <span>Contact email</span>
                <input type="email" name="contact_email" value="<?= e($companyProfile['contact_email']) ?>" required>
            </label>
            <label>
                <span>Contact phone</span>
                <input type="text" name="contact_phone" value="<?= e($companyProfile['contact_phone']) ?>" required>
            </label>
            <label>
                <span>Address</span>
                <input type="text" name="address" value="<?= e($companyProfile['address']) ?>" required>
            </label>
            <label class="full-width">
                <span>Description</span>
                <textarea name="description" rows="4"><?= e($companyProfile['description']) ?></textarea>
            </label>
            <div class="full-width">
                <button type="submit" class="button button-primary">Save company profile</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($role === 'company' && $section === 'agents'): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Agent Management</span>
        <h2><?= $editAgent ? 'Edit agent' : 'Add a new agent' ?></h2>
        <form action="<?= e(url('actions/agent_save.php')) ?>" method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="agent_id" value="<?= e((string) ($editAgent['id'] ?? 0)) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($editAgent['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($editAgent['email'] ?? '') ?>" required>
            </label>
            <label>
                <span>Phone</span>
                <input type="text" name="phone" value="<?= e($editAgent['phone'] ?? '') ?>" required>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="active" <?= ($editAgent['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editAgent['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
            <label>
                <span>Password <?= $editAgent ? '(leave blank to keep current)' : '' ?></span>
                <input type="password" name="password" <?= $editAgent ? '' : 'required' ?>>
            </label>
            <label class="full-width">
                <span>Notes</span>
                <textarea name="notes" rows="3"><?= e($editAgent['notes'] ?? '') ?></textarea>
            </label>
            <div class="full-width">
                <button type="submit" class="button button-primary"><?= $editAgent ? 'Update agent' : 'Create agent' ?></button>
            </div>
        </form>
    </section>

    <section class="stacked-panel">
        <h2>Your agents</h2>
        <div class="table-wrapper">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($agents as $agent): ?>
                    <tr>
                        <td><?= e($agent['name']) ?></td>
                        <td><?= e($agent['email']) ?><br><small><?= e($agent['phone']) ?></small></td>
                        <td><span class="<?= e(badge_class($agent['status'])) ?>"><?= e(ucfirst($agent['status'])) ?></span></td>
                        <td class="action-row">
                            <a class="button button-small button-secondary" href="<?= e(url('dashboard.php?section=agents&edit_agent=' . $agent['id'])) ?>">Edit</a>
                            <form action="<?= e(url('actions/agent_delete.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="agent_id" value="<?= e((string) $agent['id']) ?>">
                                <button type="submit" class="button button-small button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (in_array($role, ['company', 'agent'], true) && $section === 'vehicles'): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Fleet CRUD</span>
        <h2><?= $editVehicle ? 'Edit vehicle' : 'Add a new vehicle' ?></h2>
        <form action="<?= e(url('actions/vehicle_save.php')) ?>" method="post" enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="vehicle_id" value="<?= e((string) ($editVehicle['id'] ?? 0)) ?>">
            <label>
                <span>Vehicle name</span>
                <input type="text" name="name" value="<?= e($editVehicle['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Type</span>
                <select name="type" required>
                    <?php foreach (vehicle_type_options() as $optionValue => $optionLabel): ?>
                        <option value="<?= e($optionValue) ?>" <?= ($editVehicle['type'] ?? '') === $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Price per day</span>
                <input type="number" step="0.01" min="1" name="price_per_day" value="<?= e((string) ($editVehicle['price_per_day'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Driver price per day</span>
                <input type="number" step="0.01" min="0" name="driver_price_per_day" value="<?= e((string) ($editVehicle['driver_price_per_day'] ?? '0')) ?>" required>
            </label>
            <label>
                <span>Seating capacity</span>
                <input type="number" min="1" name="seating_capacity" value="<?= e((string) ($editVehicle['seating_capacity'] ?? 4)) ?>" required>
            </label>
            <label>
                <span>Status</span>
                <select name="status" required>
                    <?php foreach (VEHICLE_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($editVehicle['status'] ?? 'available') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Transmission</span>
                <input type="text" name="transmission" value="<?= e($editVehicle['transmission'] ?? '') ?>">
            </label>
            <label>
                <span>Fuel type</span>
                <input type="text" name="fuel_type" value="<?= e($editVehicle['fuel_type'] ?? '') ?>">
            </label>
            <label>
                <span>Location</span>
                <input type="text" name="location" value="<?= e($editVehicle['location'] ?? '') ?>" required>
            </label>
            <label>
                <span>Latitude</span>
                <input type="text" name="latitude" value="<?= e($editVehicle['latitude'] ?? '') ?>">
            </label>
            <label>
                <span>Longitude</span>
                <input type="text" name="longitude" value="<?= e($editVehicle['longitude'] ?? '') ?>">
            </label>
            <label>
                <span>Vehicle images</span>
                <input type="file" name="vehicle_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
            </label>
            <label class="full-width">
                <span>Description</span>
                <textarea name="description" rows="4"><?= e($editVehicle['description'] ?? '') ?></textarea>
            </label>
            <div class="full-width">
                <button type="submit" class="button button-primary"><?= $editVehicle ? 'Update vehicle' : 'Create vehicle' ?></button>
            </div>
        </form>
    </section>

    <section class="stacked-panel">
        <h2>Company fleet</h2>
        <div class="dashboard-card-grid">
            <?php foreach ($vehicles as $vehicle): ?>
                <?php $vehicleBlocks = db_all('SELECT * FROM availability_blocks WHERE vehicle_id = ? ORDER BY start_datetime DESC LIMIT 5', [$vehicle['id']]); ?>
                <article class="stacked-panel soft">
                    <?php if (!empty($vehicle['image_name'])): ?>
                        <img src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, $vehicle['image_name'])) ?>" alt="<?= e($vehicle['name']) ?>" class="listing-image dashboard-image">
                    <?php endif; ?>
                    <div class="card-topline">
                        <span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e(ucfirst($vehicle['status'])) ?></span>
                        <span class="muted"><?= e(ucfirst($vehicle['type'])) ?></span>
                    </div>
                    <h3><?= e($vehicle['name']) ?></h3>
                    <p><?= e($vehicle['location']) ?></p>
                    <p><?= e(format_money((float) $vehicle['price_per_day'])) ?>/day</p>
                    <div class="action-row">
                        <a class="button button-small button-secondary" href="<?= e(url('dashboard.php?section=vehicles&edit_vehicle=' . $vehicle['id'])) ?>">Edit</a>
                        <form action="<?= e(url('actions/vehicle_delete.php')) ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="vehicle_id" value="<?= e((string) $vehicle['id']) ?>">
                            <button type="submit" class="button button-small button-danger">Delete</button>
                        </form>
                    </div>

                    <div class="divider"></div>
                    <h4>Add blackout period</h4>
                    <form action="<?= e(url('actions/availability_save.php')) ?>" method="post" class="form-stack compact-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="vehicle_id" value="<?= e((string) $vehicle['id']) ?>">
                        <label>
                            <span>Start</span>
                            <input type="datetime-local" name="start_datetime" required>
                        </label>
                        <label>
                            <span>End</span>
                            <input type="datetime-local" name="end_datetime" required>
                        </label>
                        <label>
                            <span>Reason</span>
                            <input type="text" name="reason" placeholder="Maintenance, holiday, etc.">
                        </label>
                        <button type="submit" class="button button-small button-primary">Save block</button>
                    </form>

                    <?php if ($vehicleBlocks): ?>
                        <div class="table-stack">
                            <?php foreach ($vehicleBlocks as $block): ?>
                                <div class="mini-row">
                                    <strong><?= e(format_datetime($block['start_datetime'])) ?></strong>
                                    <span>to <?= e(format_datetime($block['end_datetime'])) ?></span>
                                    <p><?= e($block['reason'] ?: 'Manual block') ?></p>
                                    <form action="<?= e(url('actions/availability_delete.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="block_id" value="<?= e((string) $block['id']) ?>">
                                        <button type="submit" class="button button-small button-danger">Remove</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (in_array($role, ['company', 'agent'], true) && ($section === 'bookings' || $section === 'overview')): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Booking Queue</span>
        <h2><?= $section === 'overview' ? 'Recent company bookings' : 'Manage bookings' ?></h2>
        <div class="table-wrapper">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>User</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pickup</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($companyBookings as $booking): ?>
                    <tr>
                        <td><?= e($booking['vehicle_name']) ?></td>
                        <td><?= e($booking['user_name']) ?><br><small><?= e($booking['user_phone']) ?></small></td>
                        <td><?= e(format_datetime($booking['start_datetime'])) ?><br><small><?= e(format_datetime($booking['end_datetime'])) ?></small></td>
                        <td><?= e(format_money((float) $booking['total_price'])) ?><br><small><?= $booking['with_driver'] ? 'With driver' : 'Self drive' ?></small></td>
                        <td><span class="<?= e(badge_class($booking['status'])) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                        <td><?= e($booking['pickup_location']) ?><br><small><?= e($booking['destination']) ?></small></td>
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
                                <span class="muted"><?= e($booking['agent_name'] ?: 'Handled automatically') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($role === 'company' && $section === 'overview' && $companyProfile): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Company Snapshot</span>
        <h2><?= e($companyProfile['name']) ?></h2>
        <p><?= e($companyProfile['description'] ?: 'Add a company description from the profile section.') ?></p>
        <p class="muted"><?= e($companyProfile['address']) ?> â€¢ <?= e($companyProfile['contact_phone']) ?></p>
    </section>
<?php endif; ?>

<?php if ($role === 'agent' && $section === 'overview' && $companyProfile): ?>
    <section class="stacked-panel">
        <span class="eyebrow">Company Context</span>
        <h2><?= e($companyProfile['name']) ?></h2>
        <p><?= e($companyProfile['description'] ?: 'No company description yet.') ?></p>
    </section>
<?php endif; ?>
