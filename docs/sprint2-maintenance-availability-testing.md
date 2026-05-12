# Sprint 2 Maintenance Availability Testing

## Task

Connect maintenance records to availability blocks so vehicles cannot be booked during maintenance dates.

## Updated Files

- `backend/models/MaintenanceModel.php`
- `docs/sprint2-maintenance-availability-testing.md`

## Test Steps

1. Login as a company or agent user and copy the JWT token.

```powershell
$login = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"company@test.com","password":"password123"}'

$jwt = $login.data.jwt
```

2. Create a maintenance record.

```powershell
$maintenanceBody = @{
  vehicle_id = 2
  title = "Brake inspection"
  description = "Temporary maintenance block for booking conflict test."
  start_date = "2027-02-01"
  end_date = "2027-02-03"
  status = "scheduled"
} | ConvertTo-Json

$maintenance = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api/maintenance/" `
  -Method Post `
  -Headers @{ Authorization = "Bearer $jwt" } `
  -ContentType "application/json" `
  -Body $maintenanceBody

$maintenance.data.maintenance | Format-List
```

Expected result: the API returns a maintenance record with an `availability_block_id`.

3. Try to book the same vehicle for the maintenance dates.

Expected result: the booking API returns a conflict response because the vehicle is blocked for maintenance.

4. Update the maintenance record to `completed` or `cancelled`.

Expected result: the linked availability block is removed.

## Screenshot Checklist

- Maintenance API success response with `availability_block_id`.
- Database row in `maintenance_records`.
- Database row in `availability_blocks`.
- Booking API conflict response for the maintenance dates.
- GitHub commit page after push.
