# Sprint 2 JWT API Permissions Testing

## Task

Extend `api_require_user()` usage so new module APIs require JWT authentication and return proper API errors.

## Protected API Checks

1. Calling a protected API without a JWT returns `401`.

```powershell
try {
  Invoke-RestMethod -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=admin_bookings"
} catch {
  $_.ErrorDetails.Message
}
```

Expected response:

```json
{
  "success": false,
  "message": "JWT token required.",
  "data": []
}
```

2. Calling a user-only API with a company JWT returns `403`.

```powershell
$companyLogin = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"company@test.com","password":"password123"}'

$companyJwt = $companyLogin.data.jwt

try {
  Invoke-RestMethod `
    -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=review_submit" `
    -Method Post `
    -Headers @{ Authorization = "Bearer $companyJwt" } `
    -ContentType "application/json" `
    -Body '{"booking_id":1,"rating":5,"comment":"test"}'
} catch {
  $_.ErrorDetails.Message
}
```

Expected response:

```json
{
  "success": false,
  "message": "Not allowed for this role.",
  "data": []
}
```

3. Calling the same user-only API with a user JWT passes authentication and reaches booking validation.

```powershell
$userLogin = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=login" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"login":"user@test.com","password":"password123"}'

$userJwt = $userLogin.data.jwt

try {
  Invoke-RestMethod `
    -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=review_submit" `
    -Method Post `
    -Headers @{ Authorization = "Bearer $userJwt" } `
    -ContentType "application/json" `
    -Body '{"booking_id":0,"rating":5,"comment":"test"}'
} catch {
  $_.ErrorDetails.Message
}
```

Expected response:

```json
{
  "success": false,
  "message": "Booking not found.",
  "data": []
}
```

This confirms the user JWT was accepted and the request reached normal business validation.

## Bug Fixed

During API login testing, the backend failed because the local database did not have the `refresh_tokens` table. The login route calls `make_refresh_token()`, and that function inserts into `refresh_tokens`.

The fix was to add `database/sprint2_jwt_refresh_tokens_update.sql`. The first migration attempt failed because `users.id` is `INT UNSIGNED`, so `refresh_tokens.user_id` also had to be `INT UNSIGNED` for the foreign key to work.
