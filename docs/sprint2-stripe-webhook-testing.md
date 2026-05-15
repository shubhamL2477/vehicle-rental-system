# Sprint 2 Stripe Webhook Testing

## Task

Handle Stripe payment confirmation through `POST /webhooks/stripe`.

Required behavior:

- verify the Stripe signature header
- when `payment_intent.succeeded` is received, update booking `payment_status` to `paid`
- when payment failure events are received, update booking `payment_status` to `failed`

## Files Updated

- `backend/models/StripePaymentModel.php`

## Implementation Note

The route map already points `POST /webhooks/stripe` to `webhooks/stripe.php`.

For the local XAMPP setup in this project, testing can be done against:

```text
http://localhost/vehicle-rental-system-clean/webhooks/stripe.php
```

## Test Setup

1. Restart Apache with a dummy webhook secret available to PHP.

2. Create pending Stripe test bookings in the database.

Example verification rows used during testing:

- booking `1` for success event
- booking `2` for failure event

## Success Webhook Test

```powershell
$payload = '{"type":"payment_intent.succeeded","data":{"object":{"id":"pi_test_success_1","metadata":{"booking_id":"1"}}}}'
$timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$signedPayload = "$timestamp.$payload"
$hmac = [System.Security.Cryptography.HMACSHA256]::new([System.Text.Encoding]::UTF8.GetBytes('whsec_local_test'))
$signature = ([System.BitConverter]::ToString($hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($signedPayload)))).Replace('-', '').ToLower()
$header = "t=$timestamp,v1=$signature"

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/webhooks/stripe.php" `
  -Method Post `
  -Headers @{ "Stripe-Signature" = $header } `
  -ContentType "application/json" `
  -Body $payload
```

Expected result:

- webhook returns success
- message says booking payment marked paid

Database check:

```powershell
"C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental -e "SELECT id, payment_status, stripe_payment_intent_id FROM bookings WHERE id = 1;"
```

Expected result:

- `payment_status = paid`
- `stripe_payment_intent_id = pi_test_success_1`

## Failure Webhook Test

```powershell
$payload = '{"type":"payment_intent.payment_failed","data":{"object":{"id":"pi_test_failed_2","metadata":{"booking_id":"2"}}}}'
$timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$signedPayload = "$timestamp.$payload"
$hmac = [System.Security.Cryptography.HMACSHA256]::new([System.Text.Encoding]::UTF8.GetBytes('whsec_local_test'))
$signature = ([System.BitConverter]::ToString($hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($signedPayload)))).Replace('-', '').ToLower()
$header = "t=$timestamp,v1=$signature"

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/webhooks/stripe.php" `
  -Method Post `
  -Headers @{ "Stripe-Signature" = $header } `
  -ContentType "application/json" `
  -Body $payload
```

Expected result:

- webhook returns success
- message says booking payment marked failed

Database check:

```powershell
"C:\xampp\mysql\bin\mysql.exe" -u root -D vehicle_rental -e "SELECT id, payment_status, stripe_payment_intent_id FROM bookings WHERE id = 2;"
```

Expected result:

- `payment_status = failed`
- `stripe_payment_intent_id = pi_test_failed_2`

## Signature Verification Test

```powershell
$payload = '{"type":"payment_intent.succeeded","data":{"object":{"id":"pi_bad_sig","metadata":{"booking_id":"1"}}}}'
$timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$badHeader = "t=$timestamp,v1=deadbeef"

Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/webhooks/stripe.php" `
  -Method Post `
  -Headers @{ "Stripe-Signature" = $badHeader } `
  -ContentType "application/json" `
  -Body $payload
```

Expected result:

- webhook request is rejected
- response message says Stripe signature verification failed

## PHP Syntax Check

```powershell
"C:\xampp\php\php.exe" -l backend\models\StripePaymentModel.php
"C:\xampp\php\php.exe" -l webhooks\stripe.php
```

Expected result:

- no syntax errors detected

## Screenshots To Attach

- success webhook response
- database row showing booking `1` changed to `paid`
- failure webhook response
- database row showing booking `2` changed to `failed`
- invalid signature response
- GitHub push and pull request page
