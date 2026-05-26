<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Book vehicles from trusted local companies';
$pageDescription = 'Browse cars, bikes, buses, and vans with optional drivers and approval-based booking.';

$stats = [
    'vehicles' => (int) db_value('SELECT COUNT(*) FROM vehicles'),
    'companies' => (int) db_value("SELECT COUNT(*) FROM companies WHERE status = 'approved'"),
    'bookings' => (int) db_value('SELECT COUNT(*) FROM bookings'),
];

$allCompanies = db_all("SELECT * FROM companies WHERE status = 'approved' ORDER BY name");
$companyMap = [];

foreach ($allCompanies as $company) {
    $companyMap[$company['id']] = $company;
}

$allVehicles = db_all('SELECT * FROM vehicles ORDER BY created_at DESC');
$featuredVehicles = [];
$vehicleTypeCount = [];
$vehicleTypeImage = [];

foreach ($allVehicles as $vehicle) {
    if (!isset($companyMap[$vehicle['company_id']])) {
        continue;
    }

    $company = $companyMap[$vehicle['company_id']];
    $imageName = db_value(
        'SELECT file_name FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
        [$vehicle['id']]
    );

    $vehicle['company_name'] = $company['name'];
    $vehicle['image_name'] = $imageName;

    if (count($featuredVehicles) < 6) {
        $featuredVehicles[] = $vehicle;
    }

    if (!isset($vehicleTypeCount[$vehicle['type']])) {
        $vehicleTypeCount[$vehicle['type']] = 0;
    }

    $vehicleTypeCount[$vehicle['type']]++;

    if (!isset($vehicleTypeImage[$vehicle['type']]) && $imageName) {
        $vehicleTypeImage[$vehicle['type']] = $imageName;
    }
}

$featuredCompanies = [];

foreach ($allCompanies as $company) {
    $company['vehicle_count'] = (int) db_value('SELECT COUNT(*) FROM vehicles WHERE company_id = ?', [$company['id']]);
    $featuredCompanies[] = $company;
}

usort($featuredCompanies, function ($a, $b) {
    if ($a['vehicle_count'] === $b['vehicle_count']) {
        return strcmp($a['name'], $b['name']);
    }

    if ($a['vehicle_count'] < $b['vehicle_count']) {
        return 1;
    }

    return -1;
});

if (count($featuredCompanies) > 4) {
    $featuredCompanies = array_slice($featuredCompanies, 0, 4);
}

$vehicleTypes = [];

foreach ($vehicleTypeCount as $typeName => $total) {
    $vehicleTypes[] = [
        'type' => $typeName,
        'total' => $total,
        'image_name' => isset($vehicleTypeImage[$typeName]) ? $vehicleTypeImage[$typeName] : '',
    ];
}

usort($vehicleTypes, function ($a, $b) {
    if ($a['total'] === $b['total']) {
        return strcmp($a['type'], $b['type']);
    }

    if ($a['total'] < $b['total']) {
        return 1;
    }

    return -1;
});

if (count($vehicleTypes) > 6) {
    $vehicleTypes = array_slice($vehicleTypes, 0, 6);
}

if (!$vehicleTypes) {
    $vehicleTypes = [
        ['type' => 'car', 'total' => 0],
        ['type' => 'bike', 'total' => 0],
        ['type' => 'bus', 'total' => 0],
        ['type' => 'van', 'total' => 0],
    ];
}

$typeDescriptions = [
    'car' => 'Clean daily rentals for city travel and family use.',
    'bike' => 'Simple two-wheeler options for quick local movement.',
    'bus' => 'Larger vehicles for tours, teams, and event travel.',
    'van' => 'Comfortable rides for family pickup and group trips.',
];

$typeSymbols = [
    'car' => 'C',
    'bike' => 'B',
    'bus' => 'BS',
    'van' => 'V',
];

$benefits = [
    [
        'title' => 'Special booking review',
        'text' => 'Every request stays pending first so companies and agents can check dates, documents, and availability clearly.',
    ],
    [
        'title' => 'Transparent pricing',
        'text' => 'Daily vehicle price and optional driver charge are shown before the booking reaches confirmation.',
    ],
    [
        'title' => 'Role-based control',
        'text' => 'Super admin, company, agent, and renter each get their own simple dashboard and actions.',
    ],
    [
        'title' => 'Made for local rentals',
        'text' => 'The flow supports cash payment, manual approval, and beginner-friendly day-based booking.',
    ],
];

$heroVehicle = null;

foreach ($featuredVehicles as $vehicle) {
    if (!empty($vehicle['image_name'])) {
        $heroVehicle = $vehicle;
        break;
    }
}

if ($heroVehicle === null && isset($featuredVehicles[0])) {
    $heroVehicle = $featuredVehicles[0];
}

require __DIR__ . '/../includes/header.php';
?>

<section class="hero home-hero">
    <div class="hero-banner">
        <?php if ($heroVehicle && !empty($heroVehicle['image_name'])): ?>
            <img
                src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, $heroVehicle['image_name'])) ?>"
                alt="<?= e($heroVehicle['name']) ?>"
                class="hero-vehicle-image"
            >
        <?php else: ?>
            <div class="hero-image-placeholder">
                <strong>Featured vehicle image</strong>
                <p>Add a vehicle image from the company dashboard to show it here.</p>
            </div>
        <?php endif; ?>

        <div class="hero-overlay"></div>

        <div class="container hero-shell">
            <div class="hero-copy">
                <span class="eyebrow">Premium Vehicle Booking Platform</span>
                <p class="hero-note">Reserve verified vehicles across trusted companies</p>
                <h1>Find your perfect vehicle online</h1>
                <p class="lead hero-lead">
                    Browse approved local fleets, compare daily prices, request optional drivers,
                    and send one simple booking request from one clean rental system.
                </p>
                <div class="hero-actions hero-actions-centered">
                    <a class="button button-primary" href="<?= e(url('vehicles.php')) ?>">Browse Vehicles</a>
                    <a class="button button-secondary hero-ghost-button" href="<?= e(url('register.php')) ?>">Create Account</a>
                </div>
            </div>

            <?php if ($heroVehicle): ?>
                <div class="hero-image-caption">
                    <span>Featured vehicle</span>
                    <strong><?= e($heroVehicle['name']) ?></strong>
                    <small><?= e($heroVehicle['company_name']) ?> | <?= e($heroVehicle['location']) ?></small>
                </div>
            <?php endif; ?>

            <div class="hero-search-strip">
                <a class="quick-link-pill" href="<?= e(url('vehicles.php?type=car')) ?>">Used Cars</a>
                <a class="quick-link-pill" href="<?= e(url('vehicles.php?type=bike')) ?>">Any Bike</a>
                <a class="quick-link-pill" href="<?= e(url('vehicles.php?type=bus')) ?>">Any Bus</a>
                <a class="quick-link-pill" href="<?= e(url('vehicles.php?type=van')) ?>">Any Van</a>
                <a class="button button-accent quick-search-button" href="<?= e(url('vehicles.php')) ?>">Search Vehicles</a>
            </div>
        </div>
    </div>
</section>

<section class="container home-stats-band">
    <div class="stat-row home-stat-row">
        <article class="stat-card">
            <strong><?= e((string) $stats['vehicles']) ?></strong>
            <span>Vehicles listed</span>
        </article>
        <article class="stat-card">
            <strong><?= e((string) $stats['companies']) ?></strong>
            <span>Approved companies</span>
        </article>
        <article class="stat-card">
            <strong><?= e((string) $stats['bookings']) ?></strong>
            <span>Bookings tracked</span>
        </article>
    </div>
</section>

<section class="container section home-type-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Select A Vehicle Type</span>
            <h2>Browse by body style</h2>
        </div>
        <p class="section-copy">Choose a category and move to the full listing page with cleaner filters and pricing.</p>
    </div>

    <div class="type-grid">
        <?php foreach ($vehicleTypes as $typeRow): ?>
            <?php $typeName = (string) $typeRow['type']; ?>
            <a class="type-card" href="<?= e(url('vehicles.php?type=' . rawurlencode($typeName))) ?>">
                <div class="type-card-media">
                    <?php if (!empty($typeRow['image_name'])): ?>
                        <img
                            src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, (string) $typeRow['image_name'])) ?>"
                            alt="<?= e(ucfirst($typeName)) ?>"
                            class="type-card-image"
                        >
                    <?php else: ?>
                        <span class="type-symbol"><?= e($typeSymbols[$typeName] ?? strtoupper(substr($typeName, 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="type-card-body">
                    <span class="type-count"><?= e((string) $typeRow['total']) ?> available</span>
                    <strong class="type-name"><?= e(ucfirst($typeName)) ?></strong>
                    <small><?= e($typeDescriptions[$typeName] ?? 'Simple vehicle booking with day-based pricing.') ?></small>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section home-trust-section">
    <div class="trust-layout">
        <div class="trust-copy">
            <span class="eyebrow">What Matters Most</span>
            <h2>Clean flow for renters and companies</h2>
            <p class="lead">
                This project keeps document checks, company approval, pricing, and role-based actions
                simple enough to understand while still feeling cleaner and more polished.
            </p>
            <div class="trust-mini-panel">
                <strong>One system for every role</strong>
                <p>Super admin, company, agent, and user all work inside one shared booking flow with clear status updates.</p>
            </div>
        </div>

        <div class="trust-grid">
            <?php foreach ($benefits as $index => $benefit): ?>
                <article class="feature-card trust-card">
                    <span class="icon-pill"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    <h3><?= e($benefit['title']) ?></h3>
                    <p><?= e($benefit['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container section home-listing-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Most Searched Vehicles</span>
            <h2>Fresh listings</h2>
        </div>
        <a class="text-link" href="<?= e(url('vehicles.php')) ?>">View all vehicles</a>
    </div>

    <div class="card-grid">
        <?php foreach ($featuredVehicles as $vehicle): ?>
            <article class="listing-card">
                <?php if (!empty($vehicle['image_name'])): ?>
                    <img src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, $vehicle['image_name'])) ?>" alt="<?= e($vehicle['name']) ?>" class="listing-image">
                <?php else: ?>
                    <div class="listing-image placeholder">No image uploaded</div>
                <?php endif; ?>
                <div class="listing-content">
                    <div class="card-topline">
                        <span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e(ucfirst($vehicle['status'])) ?></span>
                        <span class="muted"><?= e(ucfirst($vehicle['type'])) ?></span>
                    </div>
                    <h3><?= e($vehicle['name']) ?></h3>
                    <p class="listing-meta"><?= e($vehicle['company_name']) ?> | <?= e($vehicle['location']) ?></p>
                    <strong><?= e(format_money((float) $vehicle['price_per_day'])) ?>/day</strong>
                    <div class="card-actions">
                        <a class="button button-secondary" href="<?= e(url('vehicle.php?id=' . $vehicle['id'])) ?>">View details</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$featuredVehicles): ?>
            <div class="empty-state">
                <h3>No vehicles yet</h3>
                <p>Import the seed data or add vehicles from a company dashboard to populate the public listing.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="container section home-company-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Approved Companies</span>
            <h2>Trusted fleet providers</h2>
        </div>
    </div>

    <div class="company-grid">
        <?php foreach ($featuredCompanies as $company): ?>
            <article class="feature-card">
                <span class="<?= e(badge_class($company['status'])) ?>"><?= e(ucfirst($company['status'])) ?></span>
                <h3><?= e($company['name']) ?></h3>
                <p><?= e($company['address'] ?: 'Address coming soon') ?></p>
                <p><?= e((string) $company['vehicle_count']) ?> vehicle(s) listed</p>
                <a class="text-link" href="<?= e(url('company.php?id=' . $company['id'])) ?>">Open company profile</a>
            </article>
        <?php endforeach; ?>

        <?php if (!$featuredCompanies): ?>
            <div class="empty-state">
                <h3>No approved companies yet</h3>
                <p>Company profiles will appear here after registration and admin approval.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
