<?php

function vehicle_unavailable_reason($vehicleId, $startDate, $endDate, $excludeBookingId = 0)
{
    $booking = db_one(
        'SELECT id FROM bookings
         WHERE vehicle_id = ?
           AND id <> ?
           AND status IN ("pending", "approved", "confirmed")
           AND ? <= end_date
           AND ? >= start_date
         LIMIT 1',
        [(int) $vehicleId, (int) $excludeBookingId, $startDate, $endDate]
    );

    if ($booking) {
        return 'booking';
    }

    $maintenance = db_one(
        'SELECT id FROM maintenance
         WHERE vehicle_id = ?
           AND ? <= end_date
           AND ? >= start_date
         LIMIT 1',
        [(int) $vehicleId, $startDate, $endDate]
    );

    if ($maintenance) {
        return 'maintenance';
    }

    $block = db_one(
        'SELECT id FROM availability_blocks
         WHERE vehicle_id = ?
           AND ? <= DATE(end_datetime)
           AND ? >= DATE(start_datetime)
         LIMIT 1',
        [(int) $vehicleId, $startDate, $endDate]
    );

    if ($block) {
        return 'availability_block';
    }

    return '';
}

function available_vehicle_options($excludeVehicleId = 0)
{
    $companySql = vehicle_company_sql_parts();

    return db_all(
        'SELECT v.id, v.name, v.location, v.self_drive_price, v.with_driver_price,
                ' . $companySql['name_sql'] . ' AS company_name, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         ' . $companySql['join_sql'] . '
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.status = "available" AND v.id <> ?
         ORDER BY c.name, v.name',
        [(int) $excludeVehicleId]
    );
}

function vehicle_company_sql_parts()
{
    if (company_table_enabled()) {
        return [
            'join_sql' => 'JOIN companies company ON company.id = v.company_id
                           LEFT JOIN users company_owner ON company_owner.id = company.owner_user_id',
            'name_sql' => 'COALESCE(NULLIF(company.name, ""), company_owner.name, CONCAT("Company #", company.id))',
        ];
    }

    return [
        'join_sql' => 'JOIN users company_user ON company_user.id = v.company_id',
        'name_sql' => 'COALESCE(NULLIF(company_user.company_name, ""), company_user.name)',
    ];
}

function vehicle_filter_options($input)
{
    $search = trim((string) ($input['search'] ?? $input['location'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $startDate = trim((string) ($input['start_date'] ?? ''));
    $endDate = trim((string) ($input['end_date'] ?? ''));
    $categoryId = (int) ($input['category_id'] ?? 0);
    $typeId = (int) ($input['type_id'] ?? 0);
    $typeName = trim((string) ($input['vehicle_type'] ?? $input['type'] ?? ''));
    $driverPreference = trim((string) ($input['driver_preference'] ?? ''));
    $sort = trim((string) ($input['sort'] ?? ''));
    $minPrice = (float) ($input['min_price'] ?? 0);
    $maxPrice = (float) ($input['max_price'] ?? $input['budget'] ?? 0);
    $seats = (int) ($input['seats'] ?? 0);

    if ($typeId < 1 && $typeName !== '') {
        $type = db_one('SELECT id, category_id FROM vehicle_types WHERE LOWER(name) LIKE LOWER(?) LIMIT 1', ['%' . $typeName . '%']);
        if ($type) {
            $typeId = (int) $type['id'];
            $categoryId = $categoryId > 0 ? $categoryId : (int) $type['category_id'];
        }
    }

    return [
        'search' => $search,
        'location' => $location,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'category_id' => $categoryId,
        'type_id' => $typeId,
        'min_price' => max(0, $minPrice),
        'max_price' => max(0, $maxPrice),
        'seats' => max(0, $seats),
        'driver_preference' => in_array($driverPreference, ['self_drive', 'with_driver'], true) ? $driverPreference : '',
        'sort' => in_array($sort, ['most_rented', 'latest'], true) ? $sort : 'latest',
    ];
}

function vehicle_filter_sql($filters)
{
    $params = [];
    $where = 'WHERE v.status = "available"';
    $startDate = $filters['start_date'];
    $endDate = $filters['end_date'];
    $companySql = vehicle_company_sql_parts();

    if ($startDate !== '' && $endDate !== '' && strtotime($endDate) >= strtotime($startDate)) {
        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM maintenance m
            WHERE m.vehicle_id = v.id AND ? <= m.end_date AND ? >= m.start_date
        )';
        $params[] = $startDate;
        $params[] = $endDate;

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM bookings b
            WHERE b.vehicle_id = v.id AND b.status IN ("pending", "approved", "confirmed")
            AND ? <= b.end_date AND ? >= b.start_date
        )';
        $params[] = $startDate;
        $params[] = $endDate;

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM availability_blocks ab
            WHERE ab.vehicle_id = v.id AND ? <= DATE(ab.end_datetime) AND ? >= DATE(ab.start_datetime)
        )';
        $params[] = $startDate;
        $params[] = $endDate;
    } else {
        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM maintenance m
            WHERE m.vehicle_id = v.id AND CURDATE() BETWEEN m.start_date AND m.end_date
        )';

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM bookings b
            WHERE b.vehicle_id = v.id AND b.status IN ("pending", "approved", "confirmed")
            AND CURDATE() BETWEEN b.start_date AND b.end_date
        )';

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM availability_blocks ab
            WHERE ab.vehicle_id = v.id AND CURDATE() BETWEEN DATE(ab.start_datetime) AND DATE(ab.end_datetime)
        )';
    }

    if ($filters['search'] !== '') {
        $where .= ' AND (v.name LIKE ? OR ' . $companySql['name_sql'] . ' LIKE ? OR v.location LIKE ?)';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    if ($filters['location'] !== '') {
        $where .= ' AND v.location LIKE ?';
        $params[] = '%' . $filters['location'] . '%';
    }

    if ($filters['category_id'] > 0) {
        $where .= ' AND v.category_id = ?';
        $params[] = $filters['category_id'];
    }

    if ($filters['type_id'] > 0) {
        $where .= ' AND v.type_id = ?';
        $params[] = $filters['type_id'];
    }

    if ($filters['driver_preference'] === 'with_driver') {
        $where .= ' AND v.with_driver_price > 0';
    }

    if ($filters['min_price'] > 0) {
        $where .= ' AND v.self_drive_price >= ?';
        $params[] = $filters['min_price'];
    }

    if ($filters['max_price'] > 0) {
        $priceColumn = $filters['driver_preference'] === 'with_driver' ? 'v.with_driver_price' : 'v.self_drive_price';
        $where .= ' AND ' . $priceColumn . ' <= ?';
        $params[] = $filters['max_price'];
    }

    if (($filters['seats'] ?? 0) > 0 && db_column_exists('vehicles', 'seating_capacity')) {
        $where .= ' AND v.seating_capacity >= ?';
        $params[] = (int) $filters['seats'];
    }

    return [$where, $params];
}

function filtered_vehicles($input, $limit = 50)
{
    $filters = vehicle_filter_options($input);
    [$where, $params] = vehicle_filter_sql($filters);
    $limit = max(1, min(100, (int) $limit));
    $companySql = vehicle_company_sql_parts();
    $seatSql = db_column_exists('vehicles', 'seating_capacity') ? 'v.seating_capacity' : 'NULL';
    $availabilitySql = db_column_exists('vehicles', 'availability') ? 'v.availability' : 'v.status';
    $latSql = db_column_exists('vehicles', 'lat') ? 'v.lat' : 'v.latitude';
    $longSql = db_column_exists('vehicles', 'long') ? 'v.`long`' : 'v.longitude';

    $vehicles = db_all(
        'SELECT v.id, v.company_id, v.category_id, v.type_id, v.name, v.location,
                v.self_drive_price, v.with_driver_price, v.description, v.status,
                v.latitude, v.longitude, v.image, v.created_at,
                ' . $availabilitySql . ' AS availability,
                ' . $latSql . ' AS lat,
                ' . $longSql . ' AS `long`,
                ' . $seatSql . ' AS seating_capacity,
                ' . $companySql['name_sql'] . ' AS company_name,
                c.name AS category_name, t.name AS type_name,
                (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS average_rating,
                (SELECT COUNT(*) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS review_count,
                (SELECT COUNT(*) FROM bookings b2 WHERE b2.vehicle_id = v.id AND b2.status IN ("confirmed", "completed", "approved")) AS rental_count
         FROM vehicles v
         ' . $companySql['join_sql'] . '
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         ' . $where . '
         ORDER BY ' . ($filters['sort'] === 'most_rented' ? 'rental_count DESC, v.id DESC' : 'v.id DESC') . '
         LIMIT ' . $limit,
        $params
    );

    return [$vehicles, $filters];
}

function most_rented_vehicles($companyId = 0, $limit = 5)
{
    $where = '';
    $params = [];

    if ((int) $companyId > 0) {
        $where = 'WHERE v.company_id = ?';
        $params[] = (int) $companyId;
    }

    return db_all(
        'SELECT v.id, v.name, v.location, v.status,
                COUNT(b.id) AS rental_count,
                COALESCE(SUM(CASE WHEN b.payment_status = "paid" THEN b.total_price ELSE 0 END), 0) AS paid_revenue
         FROM vehicles v
         LEFT JOIN bookings b ON b.vehicle_id = v.id
            AND b.status IN ("confirmed", "completed", "approved")
         ' . $where . '
         GROUP BY v.id, v.name, v.location, v.status
         ORDER BY rental_count DESC, paid_revenue DESC, v.id DESC
         LIMIT ' . max(1, min(10, (int) $limit)),
        $params
    );
}
