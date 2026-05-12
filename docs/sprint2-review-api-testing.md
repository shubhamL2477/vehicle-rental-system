# Sprint 2 Review API Testing

## Task

Create `POST /api/reviews` to store a vehicle review for a completed rental.

## Updated Files

- `api/reviews/index.php`
- `backend/routes/api_routes.php`

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

2. Submit a review for a completed and paid booking.

```powershell
$reviewBody = @{
  booking_id = 1
  rating = 5
  comment = "Vehicle was clean and the rental process was smooth."
} | ConvertTo-Json

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api/reviews/" `
  -Method Post `
  -Headers @{ Authorization = "Bearer $jwt" } `
  -ContentType "application/json" `
  -Body $reviewBody
```

Expected result: the API returns `success: true`, a `review_id`, `booking_id`, `vehicle_id`, `user_id`, `rating`, and `comment`.

3. Send the same review request again.

Expected result: the API returns a conflict response because only one review is allowed per booking.

## Screenshot Checklist

- User login API response.
- Review API success response.
- `reviews` table row in phpMyAdmin.
- Duplicate review conflict response.
- GitHub commit page after push.
