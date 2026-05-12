# Sprint 2 Booking API Testing

## Task

Create `POST /api/bookings` to validate vehicle availability, calculate booking cost, save the reservation, and return booking confirmation data.

## Updated Files

- `api/bookings/index.php`
- `backend/models/BookingModel.php`
- `database/sprint2_fleet_seed.sql`

## Test Steps

1. Login as a user and copy the JWT token.

```powershell
$login = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"user@test.com","password":"password123"}'

$jwt = $login.data.jwt
```

2. Create a booking.

```powershell
$bookingBody = @{
  vehicle_id = 2
  start_date = "2026-12-20"
  end_date = "2026-12-22"
  with_driver = $false
  payment_method = "cash"
  pickup_location = "Kathmandu"
  destination = "Pokhara"
  terms_accepted = $true
} | ConvertTo-Json

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api/bookings" `
  -Method Post `
  -Headers @{ Authorization = "Bearer $jwt" } `
  -ContentType "application/json" `
  -Body $bookingBody
```

Expected result: the API returns `success: true`, a `booking_id`, `daily_rate`, `total_price`, and booking status `pending`.

3. Send the same request again.

Expected result: the API returns a conflict response because the vehicle is already booked for those dates.

## Screenshot Checklist

- User login API response.
- Booking success response with `booking_id` and `total_price`.
- `bookings` table row in phpMyAdmin.
- Duplicate booking conflict response.
- GitHub commit page after push.
