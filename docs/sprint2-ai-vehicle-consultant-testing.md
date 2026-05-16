# Sprint 2 AI Vehicle Consultant Testing

Task: Add AI vehicle consulting agent

## Files checked

- `api.php`
- `includes/functions.php`
- `includes/config.php`
- `includes/header.php`
- `includes/footer.php`
- `vehicle.php`
- `assets/js/app.js`
- `assets/css/style.css`
- `.env.example`

## Local secret setup

The Groq API key is not committed to Git. Add it locally in `.env`:

```env
GROQ_API_KEY=your-groq-key-here
GROQ_MODEL=llama-3.1-8b-instant
```

## Syntax checks

```powershell
php -l api.php
php -l includes\functions.php
php -l includes\config.php
php -l includes\footer.php
```

Expected result: no syntax errors detected.

JavaScript check:

```powershell
node --check assets\js\app.js
```

Expected result: command finishes with no error output.

## API test

```powershell
$body = @{
  message = "I need a self drive car in Kathmandu for 4 seats under 12000"
  preferences = @{
    budget = 12000
    vehicle_type = "Car"
    location = "Kathmandu"
    start_date = "2027-04-10"
    end_date = "2027-04-12"
    seats = 4
    driver_preference = "self_drive"
  }
} | ConvertTo-Json -Depth 5

$response = Invoke-RestMethod `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=vehicle_consultant" `
  -Method Post `
  -ContentType "application/json" `
  -Body $body

$response | ConvertTo-Json -Depth 8
```

Expected result:

- `success` is true
- response includes `answer`
- response includes `consultant.questions`
- response includes `consultant.filters`
- response includes `consultant.recommendations`
- each recommendation has an `explanation`
- `ai_provider` is `Groq` when a local Groq key is present in `.env`
- `ai_provider` is `Local fallback` when the key is not present

## Browser test

1. Open `http://localhost/vehicle-rental-system-clean/`.
2. Click `AI Help`.
3. Enter:
   - Budget per day: `12000`
   - Vehicle type: `Car`
   - Location: `Kathmandu`
   - Start date: `2027-04-10`
   - End date: `2027-04-12`
   - Seats: `4`
   - Driver: `Self-drive`
   - Question: `I need a self drive car for a family trip`
4. Click `Recommend vehicles`.

Expected result:

- AI consultant returns an answer
- recommendations show vehicle cards
- each card includes an explanation
- form collapses after search so messages and recommendations are easy to read
- Search again button shows if the user wants to edit the request
- Book Now button opens the vehicle detail booking section
- Details button opens the vehicle detail page
- X close button closes the AI panel

## Login page check

1. Open `http://localhost/vehicle-rental-system-clean/login.php`.
2. Confirm the login form loads normally.
3. Login with `user@test.com` / `password123`.

Expected result:

- AI Help is hidden on login/register pages so it does not block the form
- login redirects to the user dashboard

## Expected Jira screenshots

- `.env` setup with key hidden or blurred
- PHP syntax checks
- `node --check assets\js\app.js`
- PowerShell API success response
- AI Help form filled in browser
- AI answer and recommendation cards in browser
- Book Now opening the vehicle detail booking section
- Login page working without AI Help blocking the form
- Git status before commit
- GitHub PR page after push
