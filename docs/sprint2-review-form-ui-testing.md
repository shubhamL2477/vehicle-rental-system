# Sprint 2 Review Form UI Testing

Task: Build post-rental review form UI

## Files checked

- `booking-detail.php`
- `dashboard.php`
- `actions/review.php`
- `includes/functions.php`
- `includes/config.php`
- `assets/css/style.css`

## Syntax checks

```powershell
php -l booking-detail.php
php -l dashboard.php
php -l actions\review.php
php -l includes\functions.php
php -l includes\config.php
```

Result: no syntax errors detected.

## Browser test account

- URL: `http://localhost/vehicle-rental-system-clean/`
- User: `user@test.com`
- Password: `password123`

## Test data

Booking `#9`:

- User booking
- Status: `completed`
- Payment: `paid`
- Existing review already submitted

Booking `#11` was temporarily changed for form visibility testing:

- Status changed to `completed`
- Payment changed to `paid`
- End date changed to a past date
- No review existed for this booking
- After browser testing, booking `#11` was restored to its original pending/cash_due future booking state

## Browser checks performed

1. Opened `booking-detail.php?id=11`.
2. Confirmed the booking detail page loads.
3. Confirmed the post-rental review form appears only for a completed paid rental with no submitted review.
4. Confirmed the UI shows a 1-5 star selector.
5. Confirmed the comment textarea accepts input.
6. Confirmed the Submit review button is enabled after selecting a rating.
7. Opened `booking-detail.php?id=9`.
8. Confirmed an already-reviewed booking shows the submitted review.
9. Confirmed the submitted review button is disabled.
10. Opened `dashboard.php?section=bookings`.
11. Confirmed bookings have View details links.

## Expected Jira screenshots

- Syntax checks showing no errors
- Booking detail page with the 1-5 star selector and comment box
- Booking detail page showing already submitted review disabled
- Dashboard bookings page showing View details links
- Git status before commit
- GitHub PR page after push
