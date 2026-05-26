# Sprint 2 Chatbot Live Availability Testing

Task: Connect chatbot to live vehicle availability API

Branch:

```powershell
feature/chatbot-live-availability
```

## Syntax checks

```powershell
php -l api.php
php -l api/consultant/index.php
php -l backend/models/VehicleConsultantModel.php
php -l includes/functions.php
php -l includes/footer.php
```

Expected result:

```text
No syntax errors detected
```

## Live vehicle availability API check

```powershell
$availability = Invoke-RestMethod `
  -Method Get `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=vehicles&location=Kathmandu&vehicle_type=car&start_date=2027-04-10&end_date=2027-04-12&seats=2&max_price=12000&driver_preference=self_drive"

$availability.success
$availability.data.filters | Format-List
$availability.data.vehicles |
  Select-Object id,name,company_name,location,type_name,seating_capacity,self_drive_price |
  Format-Table
```

Expected:

- `success` is `True`
- filters include location, vehicle type, dates, seats, budget, and driver preference
- response shows available vehicles for the selected dates

## AI consultant still works

```powershell
$body = @{
  message = "I need a Kathmandu car for 2 people. I prefer self drive."
  budget = 12000
  vehicle_type = "car"
  location = "Kathmandu"
  start_date = "2027-04-10"
  end_date = "2027-04-12"
  seats = 2
  driver_preference = "self_drive"
} | ConvertTo-Json

$consultant = Invoke-RestMethod `
  -Method Post `
  -Uri "http://localhost/vehicle-rental-system-clean/api/consultant/index.php" `
  -ContentType "application/json" `
  -Body $body

$consultant.success
$consultant.data.ai_provider
$consultant.data.answer
$consultant.data.recommendations |
  Select-Object vehicle_id,name,daily_price,explanation |
  Format-List
```

Expected:

- `success` is `True`
- AI response still recommends matching vehicles

## Browser chatbot check

Open:

```text
http://localhost/vehicle-rental-system-clean/
```

Test:

1. Click `AI Help`.
2. Fill budget, vehicle type, location, dates, seats, and driver preference.
3. Click `Recommend vehicles`.
4. Confirm the chat first shows `Live availability`.
5. Confirm available vehicle cards show inline in the chat.
6. Confirm the AI consultant explanation appears after the live results.

## Screenshots for Jira

- PHP syntax checks
- live vehicle availability API response
- AI consultant response after availability check
- browser chat showing `Live availability`
- browser chat showing inline available vehicle cards
- browser chat showing AI explanation after live availability results
- GitHub PR page
