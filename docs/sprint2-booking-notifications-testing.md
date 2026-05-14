\# Sprint 2 Booking Notifications Testing



\## Task



Create notifications table and in-app notification system. Trigger notifications when a booking is submitted, confirmed, cancelled, and completed.



\## Files Checked



\- database/schema.sql

\- database/sprint2\_notifications\_reviews\_update.sql

\- backend/models/NotificationService.php

\- actions/booking.php

\- actions/notification.php

\- dashboard.php

\- includes/header.php



\## What Was Verified



\- notifications table has required columns:

&#x20; - user\_id

&#x20; - title

&#x20; - message

&#x20; - type

&#x20; - read\_at

&#x20; - created\_at

\- NotificationService inserts notifications.

\- Booking submitted trigger creates user and company notifications.

\- Booking confirmed trigger creates booking notifications.

\- Booking cancelled trigger creates booking notifications.

\- Booking completed trigger creates rental completed notifications.

\- Dashboard shows in-app notifications.

\- Header shows notification count.

\- Notifications can be marked as read.



\## Syntax Checks



powershell

php -l backend\\models\\NotificationService.php

php -l actions\\notification.php

php -l dashboard.php

php -l includes\\header.php

php -l actions\\booking.php



