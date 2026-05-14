# Sprint 2 Maintenance Records Table Testing

## Task

Create the `maintenance_records` migration so the table stores:

- `vehicle_id`
- `company_id`
- `title`
- `description`
- `cost`
- `start_date`
- `end_date`
- `status`

This task keeps the database schema aligned with the existing maintenance API, which already reads and writes `start_date` and `end_date`.

## Files Updated

- `database/schema.sql`
- `database/seed.sql`
- `database/sprint2_maintenance_records_update.sql`

## Migration Test

1. Apply the maintenance records migration.

```powershell
Get-Content database\sprint2_maintenance_records_update.sql |
  & "C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental
```

2. Confirm the table now has `start_date` and `end_date`.

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental -e "SHOW COLUMNS FROM maintenance_records;"
```

Expected result:

- `start_date` exists as `DATE`
- `end_date` exists as `DATE`

3. Confirm old maintenance data was backfilled.

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental -e "SELECT id, vehicle_id, start_date, end_date, start_datetime, end_datetime FROM maintenance_records;"
```

Expected result:

- existing rows keep their original datetime values
- `start_date` is filled from `start_datetime`
- `end_date` is filled from `end_datetime`

## API Proof

1. Login as admin and create a maintenance record using `start_date` and `end_date`.

```powershell
$login = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"admin@test.com","password":"password123"}'

$jwt = $login.data.jwt

$body = @{
  vehicle_id = 2
  title = "Tire rotation"
  description = "Schema migration verification record"
  cost = 1500
  start_date = "2027-03-05"
  end_date = "2027-03-06"
  status = "scheduled"
} | ConvertTo-Json

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api/maintenance/index.php" `
  -Method Post `
  -Headers @{ Authorization = "Bearer $jwt" } `
  -ContentType "application/json" `
  -Body $body
```

Expected result:

- API returns success
- response includes `maintenance_id`
- response includes both `start_date` and `end_date`

2. Confirm the row was stored in the database.

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental -e "SELECT id, vehicle_id, company_id, title, cost, start_date, end_date, start_datetime, end_datetime, status FROM maintenance_records ORDER BY id DESC LIMIT 1;"
```

Expected result:

- record exists with `start_date = 2027-03-05`
- record exists with `end_date = 2027-03-06`

3. Confirm company and agent roles can load maintenance data, while user role is blocked.

Expected result:

- company JWT can open maintenance list
- agent JWT can open maintenance list
- user JWT gets `403`

## PHP Syntax Checks

```powershell
& "C:\xampp\php\php.exe" -l api\maintenance\index.php
& "C:\xampp\php\php.exe" -l backend\models\MaintenanceModel.php
& "C:\xampp\php\php.exe" -l backend\routes\api_common.php
```

Expected result:

- no syntax errors detected

## Screenshots To Attach

- migration proof showing `start_date` and `end_date` in `SHOW COLUMNS FROM maintenance_records`
- maintenance API success response after creating the test record
- database row for the created maintenance record with both date and datetime columns
- company/agent access proof and user `403` proof
- GitHub push and pull request page
