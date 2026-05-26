# Sprint 2 AI Vehicle Consultant Testing

Task: Add AI vehicle consulting agent

Branch:

```powershell
feature/vehicle-consultant-agent
```

## Local setup

The Groq API key is not committed to Git. Add it locally in `.env`:

```env
APP_PUBLIC_URL=http://localhost/vehicle-rental-system-clean
AI_CONSULTANT_NAME=VehicleRentalConsultant
GROQ_API_KEY=your-groq-key-here
GROQ_MODEL=llama-3.1-8b-instant
GROQ_API_URL=https://api.groq.com/openai/v1/chat/completions
```

## Syntax checks

```powershell
php -l includes/config.php
php -l includes/functions.php
php -l backend/models/VehicleConsultantModel.php
php -l api/consultant/index.php
php -l api.php
```

Expected result:

```text
No syntax errors detected
```

## Vehicle filter API check

```powershell
$vehicles = Invoke-RestMethod `
  -Method Get `
  -Uri "http://localhost/vehicle-rental-system-clean/api.php?action=vehicles&location=Kathmandu&vehicle_type=car&seats=2&max_price=12000&driver_preference=self_drive"

$vehicles.success
$vehicles.data.filters | Format-List
$vehicles.data.vehicles | Select-Object id,name,company_name,location,type_name,seating_capacity,self_drive_price | Format-Table
```

Expected:

- `success` is `True`
- filters include budget, location, vehicle type, seats, and driver preference
- returned vehicles match the selected filters

## Consultant missing-fields check

```powershell
$missingBody = @{
  message = "Can you help me choose a vehicle?"
} | ConvertTo-Json

$missing = Invoke-RestMethod `
  -Method Post `
  -Uri "http://localhost/vehicle-rental-system-clean/api/consultant/index.php" `
  -ContentType "application/json" `
  -Body $missingBody

$missing.success
$missing.data.agent
$missing.data.missing_fields
$missing.data.answer
```

Expected:

- `success` is `True`
- agent is `VehicleRentalConsultant`
- missing fields include budget, vehicle type, location, dates, seats, and driver preference
- answer asks for missing details

## Consultant recommendation check

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
$consultant.data.agent
$consultant.data.ai_provider
$consultant.data.answer
$consultant.data.filters | Format-List
$consultant.data.recommendations | Select-Object vehicle_id,name,location,type_name,seating_capacity,daily_price,explanation | Format-List
```

Expected:

- `success` is `True`
- agent is `VehicleRentalConsultant`
- `ai_provider` is `GroqCloud` when the local Groq key is configured
- recommendations include top vehicle matches with explanations

## Browser check

Open:

```text
http://localhost/vehicle-rental-system-clean/
```

Test:

1. Click `AI Help`.
2. Enter budget, vehicle type, location, dates, seats, and driver preference.
3. Submit the form.
4. Confirm the AI answer and recommendation cards appear.

## Screenshots for Jira

- PowerShell syntax check success
- vehicle filter API response with filters
- consultant missing-fields response
- consultant recommendation response showing `agent`, `ai_provider`, and `answer`
- consultant recommendation list with explanations
- browser chat box showing AI answer and recommendation cards
- GitHub branch/commit/PR page after pushing
