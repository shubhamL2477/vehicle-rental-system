# Sprint 2 Booking Conflict Validation Testing

## Task

Booking form validates date conflicts before submit.

## Updated Files

- `assets/js/app.js`

## What Changed

- End date picker is disabled until a start date is selected.
- Booking form fetches booked and blocked dates for the selected vehicle.
- Conflict dates show an unavailable message and disable submit.
- Submit click re-fetches availability before the form is allowed to continue.
- If availability cannot be checked, submit stays disabled.

## Syntax Check

```powershell
node --check assets\js\app.js
```

Expected:

```text
No output means JavaScript syntax passed.
```

## Browser Test: Existing Blocked Dates

URL:

```text
http://localhost/vehicle-rental-system-clean/vehicle.php?id=2
```

Login:

```text
user@test.com / password123
```

Steps:

1. Open the booking form.
2. Confirm end date is disabled before choosing start date.
3. Select start date:

```text
2027-02-02
```

4. Select end date:

```text
2027-02-03
```

Expected:

- Form shows unavailable message for maintenance block.
- Submit button is disabled.

## Browser Test: Available Dates

Use:

```text
Start date: 2028-10-01
End date: 2028-10-03
```

Expected:

- Form shows available message.
- Submit button is enabled.

## Browser Test: Submit-Time Recheck

Temporary database block used for proof:

```sql
INSERT INTO availability_blocks (vehicle_id, company_id, start_datetime, end_datetime, reason)
VALUES (2, 2, '2031-04-01 00:00:00', '2031-04-02 23:59:59', 'QA submit recheck block');
```

Steps:

1. Before adding the temporary block, select:

```text
Start date: 2031-04-01
End date: 2031-04-02
```

2. Confirm form says available.
3. Add the temporary availability block in the database.
4. Check "Book with driver" and accept terms so the submit event can run.
5. Click "Send booking request".

Expected:

- Form re-checks availability before submit.
- Form changes to unavailable.
- Submit stays disabled.
- Page does not navigate away or create a booking.

Cleanup:

```sql
DELETE FROM availability_blocks WHERE reason = 'QA submit recheck block';
```

## Test Result

Passed. The booking form blocks known date conflicts before submit and performs a fresh availability check again when the user clicks submit.
