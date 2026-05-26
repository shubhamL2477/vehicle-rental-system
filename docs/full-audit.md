# Vehicle Rental System Audit

## Audit scope

This audit covered all application-owned runtime files in the current workspace:

- top-level PHP entry points
- `actions/`
- `api/`
- `backend/models/`
- `backend/routes/`
- `dashboard/`
- `includes/`
- `auth/`, `admin/`, `company/`, `user/`, `payment/`
- `assets/js/`
- `assets/css/style.css`
- SQL files
- `.env` / `.env.example`
- `composer.json`
- `tests/crud_smoke.php`

Third-party vendor code and binary/image assets were not reviewed line-by-line because they are external dependencies, not project logic.

## Verification run

### Static/runtime checks

- `php -l` over all project PHP files: passed
- `php tests/crud_smoke.php`: passed, but emitted CLI session warnings because `session_start()` tried to use an unwritable `C:\xampp\tmp`
- Browser validation:
  - home page loaded
  - login with `user@test.com / password123` worked
  - dashboard loaded
  - vehicles page loaded
  - vehicle detail page loaded
  - `api/login` returned JWT + refresh token
  - `api/vehicles/booked_dates.php` returned valid JSON
  - `api/consultant/index.php` returned valid JSON using local fallback

### Important environment note

The live local database is not currently aligned with the checked-in seed/schema. The codebase ships a 17-table schema in `vehicle-rental.sql`, but the local DB currently contains only the legacy categories/data set. This is an environment drift issue, not a syntax failure in the code.

## What is working

- Session-based login works end-to-end in the browser.
- API login works and returns JWT + refresh token.
- Dashboard role routing works.
- Vehicle listing page renders and fetch-based live filtering is wired.
- Vehicle detail page, availability widget, and booked-dates API are wired.
- Booking creation logic exists for both form posts and API posts.
- Stripe Checkout integration is wired from booking creation to webhook/status sync.
- Legacy mock payment flow works structurally.
- Notifications are persisted and rendered.
- Review and website-rating flows are wired.
- Maintenance and service history flows are wired.
- OTP generation and verification logic exists and passed smoke coverage.
- Composer dependencies are installed and loadable.

## Broken or incomplete items

### 1. API vehicle deletion can erase booking history by bypassing the UI safety rule

Files:

- `api.php:178-189`
- `actions/vehicle.php:76-90`
- `vehicle-rental.sql:147-168`

Problem:

The dashboard action blocks vehicle deletion when bookings exist, but the API route does not. Because the schema uses `ON DELETE CASCADE` from `vehicles -> bookings` and `bookings -> payments/reviews`, deleting a vehicle through the API can remove historical bookings and related payment/review data.

Impact:

- Data loss
- Inconsistent behavior between dashboard and API
- Dangerous admin/company API action

Fix:

Add the same booking-history guard to `api.php` before deletion:

```php
$hasBooking = (int) db_value('SELECT COUNT(*) FROM bookings WHERE vehicle_id = ?', [$id]);
if ($hasBooking > 0) {
    json_out(false, 'Vehicle has booking history. Mark it unavailable instead.', [], 409);
}
```

Insert it immediately before:

```php
db_run('DELETE FROM vehicles WHERE id = ? AND company_id = ?', [$id, $vehicle['company_id']]);
```

### 2. Self-drive bookings can be created through the API without required ID/license uploads

Files:

- `actions/booking.php:121-131`
- `api/bookings/index.php:13-38`
- `backend/models/BookingModel.php:12-45`

Problem:

The form-post flow requires `document_file` and `license_file` for self-drive bookings, but the API booking endpoint directly calls `BookingModel::createForUser()` and that model has no document validation at all. This means API clients can create self-drive bookings without the mandatory documents.

Impact:

- Business rule bypass
- Different behavior between frontend and API
- Weak verification for self-drive rentals

Fix:

Either:

1. Reject self-drive API bookings until file upload support exists, or
2. Extend the API and `BookingModel` to require document paths for self-drive bookings.

Smallest safe fix inside `api/bookings/index.php`:

```php
if (empty($data['with_driver'])) {
    api_response(false, 'Self-drive API bookings are not supported without document uploads.', [], 422);
}
```

Better structural fix inside `BookingModel::validateBookingInput()`:

```php
$documentFile = trim((string) ($data['document_file'] ?? ''));
$licenseFile = trim((string) ($data['license_file'] ?? ''));

if (!$withDriver && ($documentFile === '' || $licenseFile === '')) {
    throw new InvalidArgumentException('Self-drive bookings require document_file and license_file.');
}
```

and then persist those fields in `createForUser()`.

### 3. The user dashboard offers “Cancel” for confirmed bookings, but the action handler rejects them

Files:

- `dashboard/user.php:171-189`
- `actions/booking.php:317-327`

Problem:

The UI shows the cancel button for `pending`, `approved`, and `confirmed` bookings, but the backend only allows cancellation for `pending` and `approved`. A confirmed booking therefore presents a visible button that ends in an error message.

Impact:

- Broken end-to-end UX
- Confusing for users

Fix:

Choose one rule and make both sides match.

If confirmed bookings should be cancellable, change:

```php
if (!$booking || !in_array($booking['status'], ['pending', 'approved'], true)) {
```

to:

```php
if (!$booking || !in_array($booking['status'], ['pending', 'approved', 'confirmed'], true)) {
```

If confirmed bookings should not be cancellable, remove `confirmed` from the dashboard condition at `dashboard/user.php:171`.

### 4. Real SMTP credentials are stored in `.env`

File:

- `.env:2-5`

Problem:

The project contains a real Gmail address and app password in plaintext.

Impact:

- Credential exposure
- Anyone with the repo can send mail through that account
- High security risk for the owner

Fix:

Immediately:

1. Rotate the Gmail app password.
2. Replace `.env` contents with safe local placeholders.
3. Keep only `.env.example` in shared/project history.

Suggested safe local replacement:

```env
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=
JWT_SECRET=replace-with-a-long-random-secret
```

### 5. Web login silently creates refresh tokens, but logout never revokes them

Files:

- `assets/js/auth.js:41-90`
- `api.php:46-56`
- `auth/logout.php:17-24`

Problem:

Every normal browser login first calls `api/login`, which issues a refresh token and stores it in `localStorage`. The eventual logout page only clears local storage and destroys the PHP session; it never calls API logout to revoke the refresh token in `refresh_tokens`.

Impact:

- Refresh tokens accumulate unnecessarily
- Logout is incomplete for API auth
- Stale tokens remain valid until expiry

Fix:

Best small fix: stop issuing API tokens during ordinary form login unless the page truly needs them.

Simplest change:

- remove the `fetch('api/login', ...)` preflight from `assets/js/auth.js`
- keep the normal form submit only

If you want hybrid session+JWT login, then update `auth/logout.php` to revoke the saved refresh token via API or direct DB update before clearing storage.

### 6. Payment reporting is split: Stripe payments never enter `payments` history

Files:

- `payments.php:18-28`
- `backend/models/StripePaymentModel.php:63-75`
- `backend/models/StripePaymentModel.php:195-198`
- `vehicle-rental.sql:153-169`

Problem:

`payments.php` only reads the `payments` table. Stripe flow updates only the `bookings` table (`stripe_session_id`, `stripe_payment_intent_id`, `payment_status`) and never inserts a `payments` row. Result: Stripe checkout/status works, but Stripe transactions do not appear in the main payment history table.

Impact:

- Incomplete payment history
- Dashboard/reporting inconsistency
- Two different sources of truth for payments

Fix options:

1. Extend `payments.php` to merge Stripe-backed booking rows into the display.
2. Or, create `payments` rows for Stripe sessions when checkout starts and update them via webhook.

Smallest code change:

- keep the current `payments` table for mock gateway
- add a second query in `payments.php` for Stripe bookings
- render them in the same table with provider `Stripe`

### 7. Mock payment completion overwrites the booking payment method to `cash`

File:

- `actions/payment.php:128-130`

Problem:

When the mock gateway finishes, the booking is updated with:

```php
UPDATE bookings SET payment_method = "cash", payment_status = ?
```

This loses the original channel information and makes paid-through-gateway bookings look like cash bookings.

Impact:

- Incorrect data
- Misleading reporting
- Harder debugging for payments

Fix:

Do not overwrite `payment_method`. Only update `payment_status`.

Change:

```php
db_run(
    'UPDATE bookings SET payment_method = "cash", payment_status = ? WHERE id = ?',
    [$decision === 'paid' ? 'paid' : 'failed', $payment['booking_id']]
);
```

to:

```php
db_run(
    'UPDATE bookings SET payment_status = ? WHERE id = ?',
    [$decision === 'paid' ? 'paid' : 'failed', $payment['booking_id']]
);
```

## Works, but has issues or risks

### 1. CLI/session environment is fragile

Files:

- `includes/functions.php:6-8`
- `tests/crud_smoke.php`

Problem:

`session_start()` runs unconditionally even during CLI test execution. In the current environment that produced warnings because the default XAMPP session directory was not writable.

Suggested fix:

- skip session start in CLI, or
- set a project-local writable session path for CLI runs

Example:

```php
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_start();
}
```

or set `session_save_path(APP_ROOT . '/tmp/sessions');` before `session_start()`.

### 2. API OTP verification does not mirror company-approval side effects from the form flow

Files:

- `api/otp/verify.php:24-35`
- `actions/auth.php:198-215`

Problem:

The page flow creates a `company_requests` row after company OTP verification; the API flow only updates `users.status`.

Suggested fix:

Reuse the same company-verification branch from `actions/auth.php`, or extract shared logic into a helper.

### 3. `vehicle-search.js` has no fetch error handling

File:

- `assets/js/vehicle-search.js:101-132`

Problem:

If the request fails or returns non-JSON, the live search UI has no `.catch()` path and can silently stop updating.

Suggested fix:

Add:

```js
.catch(function () {
    results.innerHTML = '<div class=\"box\">Vehicle search is unavailable right now.</div>';
    countBox.textContent = '0 vehicle(s) found';
});
```

### 4. Live data in the local DB does not match the checked-in SQL files

Evidence:

- live DB categories currently include `Truck` and `Other`
- current `seed.sql` defines `Van`, `Jeep`, `Scooter`, etc.

Impact:

- Demo behavior may differ from a fresh import
- Bugs may be hidden by local custom data

Suggested fix:

- test on a fresh `vehicle_rental` database imported from `vehicle-rental.sql` + `seed.sql`
- keep a short setup note in README

## Missing implementations

No explicit `TODO`, `FIXME`, or empty function stubs were found in the application-owned source files.

The main incompleteness is behavioral rather than placeholder-based:

- partial Stripe reporting
- API/self-drive document gap
- company OTP side-effect mismatch between API and form flows

## Recommended next fixes

1. Block destructive API vehicle deletion when bookings exist.
2. Align API booking rules with the form booking rules for self-drive documents.
3. Fix confirmed-booking cancellation mismatch between UI and backend.
4. Remove committed SMTP secrets and rotate the exposed password.
5. Decide whether browser login should use session-only auth or full session+JWT auth, then make logout match that choice.
6. Unify Stripe and mock payment reporting into one history source.
