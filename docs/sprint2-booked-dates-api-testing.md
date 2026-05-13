\# Sprint 2 Booked Dates API Testing



\## Task



Ensure the booked\_dates API returns both confirmed bookings and availability blocks, including maintenance, with ISO date ranges.



\## Endpoint



GET http://localhost/vehicle-rental-system-clean/api/vehicles/booked\_dates.php?vehicle\_id=2



\## Files Checked



\- api/vehicles/booked\_dates.php

\- backend/routes/api\_routes.php



\## Test Steps



1\. Confirm the route exists.



php

'GET /api/vehicles/booked\_dates' => 'api/vehicles/booked\_dates.php',



