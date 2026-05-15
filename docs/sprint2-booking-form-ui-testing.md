# Sprint 2 Booking Form UI Testing

## Task

Build booking form UI with:
- Vehicle dropdown selector
- Date range inputs
- Real-time availability indicator
- Frontend validation before submission

## Updated Files

- `vehicle.php`
- `assets/js/app.js`
- `assets/css/style.css`
- `includes/footer.php`
- `includes/config.php`

## Syntax Checks

```powershell
php -l vehicle.php
node --check assets\js\app.js
```

Expected:
- No syntax errors detected in `vehicle.php`
- No JavaScript syntax errors

## Browser Test

URL:

```text
http://localhost/vehicle-rental-system-clean/vehicle.php?id=2
```

Test account:

```text
user@test.com / password123
```

Steps:

1. Login as the test user.
2. Open Toyota Hiace Tour Van detail page.
3. Confirm booking form shows a Vehicle dropdown.
4. Select vehicle: Toyota Hiace Tour Van.
5. Enter blocked dates:

```text
Start date: 2027-02-02
End date: 2027-02-03
```

Expected:
- Availability indicator shows unavailable.
- Submit button is disabled.

6. Enter available dates:

```text
Start date: 2028-09-01
End date: 2028-09-03
```

Expected:
- Availability indicator shows available.
- Submit button is enabled.

7. Enter an invalid range:

```text
Start date: 2028-09-05
End date: 2028-09-03
```

Expected:
- Availability indicator asks for a valid date range.
- Submit button is disabled.

## Test Result

Passed. The booking form now lets the user select a vehicle, choose a date range, see live availability, and blocks submission when dates are unavailable or invalid. The app base URL also points to the clean localhost path so the page loads the correct project assets during testing.
