# Sprint 2 Booking Availability Check Testing

## Task

Add a vehicle availability check before booking so the API detects existing booking date conflicts and returns a clear unavailable-slot message.

## Files Checked

- `backend/models/BookingModel.php`
- `api/bookings/index.php`

## API Test Steps

1. Login as a normal user.

```powershell
$login = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"user@test.com","password":"password123"}'

$jwt = $login.data.jwt
```

Expected: `success` is `true`, and `data.jwt` is returned.

2. Create an initial booking.

```powershell
$body = @{
  vehicle_id = 4
  start_date = "2027-05-13"
  end_date = "2027-05-15"
  with_driver = $false
  payment_method = "cash"
  pickup_location = "Kathmandu"
  destination = "Pokhara"
  terms_accepted = $true
} | ConvertTo-Json

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api/bookings/index.php" `
  -Method Post `
  -Headers @{ Authorization = "Bearer $jwt" } `
  -ContentType "application/json" `
  -Body $body
```

Expected: booking is created with status `pending`.

3. Try to create an overlapping booking for the same vehicle.

```powershell
$body = @{
  vehicle_id = 4
  start_date = "2027-05-14"
  end_date = "2027-05-16"
  with_driver = $false
  payment_method = "cash"
  pickup_location = "Kathmandu"
  destination = "Chitwan"
  terms_accepted = $true
} | ConvertTo-Json

try {
  Invoke-RestMethod `
    -Uri "http://localhost/vehicle-rental-system-clean/api/bookings/index.php" `
    -Method Post `
    -Headers @{ Authorization = "Bearer $jwt" } `
    -ContentType "application/json" `
    -Body $body
} catch {
  $_.Exception.Response.StatusCode.value__
  $_.ErrorDetails.Message
}
```

Expected response:

```json
{
  "success": false,
  "message": "Unable to create booking: Vehicle is unavailable for selected dates. Existing pending booking covers 2027-05-13 to 2027-05-15.",
  "data": []
}
```

Expected HTTP status: `409`.

## Local Test Result

- Login API succeeded.
- Initial booking succeeded for vehicle `4`, booking ID `10`.
- Overlapping booking failed with HTTP `409`.
- Error message clearly showed the unavailable date range from the existing booking.
- `php -l` passed for the edited booking model and API endpoint.
