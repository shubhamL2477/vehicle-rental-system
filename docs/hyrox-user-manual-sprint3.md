# HYROX VEHICLE RENTAL PLATFORM

```
HYROX VEHICLE RENTAL PLATFORM
User Manual - Version 1.0
Project: HYR | Sprint 3
Prepared by: [Project Manager Name]
Date: May 2026
```

---

## SECTION 1 - SYSTEM OVERVIEW

The Hyrox Vehicle Rental Platform is a web-based system for browsing vehicles, checking availability, creating rental bookings, paying online, and managing vehicle operations. It supports customer-facing rental workflows and internal workflows for admins, companies, and agents.

Who uses it:

| Role | Description |
|---|---|
| Customer | Browses vehicles, makes bookings, pays online, receives notifications, and submits reviews. |
| Admin | Manages the complete platform, including vehicles, bookings, users, payments, analytics, and maintenance. |
| Company | Manages its own fleet, service history, maintenance records, and related bookings. |
| Agent | Supports booking and customer-service tasks on behalf of a company. |

How to access it:

1. Open a browser.
   -> Expected result: The browser is ready for the platform URL.
   [SCREENSHOT PROMPT: Capture the empty browser address bar before entering the local URL]

2. Enter **http://localhost/vehicle-rental-system-clean/**.
   -> Expected result: The Hyrox home page loads.
   [SCREENSHOT PROMPT: Capture the Hyrox public home page with navigation visible]

3. Use **Register** or **Login** from the navigation menu.
   -> Expected result: The correct account page opens.
   [SCREENSHOT PROMPT: Capture the navigation area showing Register and Login links]

Tech stack summary:

| Area | Technology |
|---|---|
| Backend | PHP |
| Database | MySQL |
| Payments | Stripe Checkout and Stripe Webhooks |
| Authentication | JWT tokens, OTP verification, role-based access |
| AI Support | AI chatbot and vehicle consulting agent |

---

## SECTION 2 - GETTING STARTED

━━━━━━━━━━━━━━━━━━━━━━━━━
ROLE: Customer
━━━━━━━━━━━━━━━━━━━━━━━━━

Step 1: Customer clicks **Register**.
         -> What to expect: The registration form appears.
         [SCREENSHOT PROMPT: Capture the customer registration form with all fields visible]

Step 2: Customer enters name, email, phone number, and password.
         -> What to expect: The form accepts the account details.
         [SCREENSHOT PROMPT: Capture the completed registration form before submission]

Step 3: Customer clicks **Register**.
         -> What to expect: The system creates the account and asks for OTP verification.
         [SCREENSHOT PROMPT: Capture the OTP verification page]

Step 4: Customer enters the OTP code.
         -> What to expect: The account is verified successfully.
         [SCREENSHOT PROMPT: Capture the successful OTP verification message]

Step 5: Customer logs in with email and password.
         -> What to expect: The customer dashboard opens.
         [SCREENSHOT PROMPT: Capture the customer dashboard after login]

Step 6: Customer reviews the dashboard.
         -> What to expect: The customer can see bookings, payment status, notifications, and review options.
         [SCREENSHOT PROMPT: Capture the main customer dashboard overview]

━━━━━━━━━━━━━━━━━━━━━━━━━
ROLE: Admin
━━━━━━━━━━━━━━━━━━━━━━━━━

Step 1: Admin opens **Login**.
         -> What to expect: The login form appears.
         [SCREENSHOT PROMPT: Capture the login form before admin login]

Step 2: Admin enters admin credentials.
         -> What to expect: The form accepts the credentials.
         [SCREENSHOT PROMPT: Capture the filled admin login form without exposing the password]

Step 3: Admin clicks **Login**.
         -> What to expect: The admin dashboard opens.
         [SCREENSHOT PROMPT: Capture the admin dashboard landing view]

Step 4: Admin reviews the dashboard sections.
         -> What to expect: Admin can see vehicle management, bookings, users, payments, maintenance, and analytics.
         [SCREENSHOT PROMPT: Capture the admin dashboard sections in one view]

━━━━━━━━━━━━━━━━━━━━━━━━━
ROLE: Company
━━━━━━━━━━━━━━━━━━━━━━━━━

Step 1: Company user opens **Register** or **Login**.
         -> What to expect: The correct account form appears.
         [SCREENSHOT PROMPT: Capture the account access page for company users]

Step 2: Company user logs in with approved company credentials.
         -> What to expect: The company dashboard opens.
         [SCREENSHOT PROMPT: Capture the company dashboard after login]

Step 3: Company user reviews fleet tools.
         -> What to expect: Company can manage its vehicles, maintenance, service history, and related bookings.
         [SCREENSHOT PROMPT: Capture the fleet management area on the company dashboard]

━━━━━━━━━━━━━━━━━━━━━━━━━
ROLE: Agent
━━━━━━━━━━━━━━━━━━━━━━━━━

Step 1: Agent opens **Login**.
         -> What to expect: The login form appears.
         [SCREENSHOT PROMPT: Capture the login page for agent access]

Step 2: Agent enters agent credentials.
         -> What to expect: The form accepts the credentials.
         [SCREENSHOT PROMPT: Capture the filled agent login form without exposing the password]

Step 3: Agent clicks **Login**.
         -> What to expect: The agent dashboard opens.
         [SCREENSHOT PROMPT: Capture the agent dashboard after login]

Step 4: Agent reviews assigned booking and service tools.
         -> What to expect: Agent can support bookings and view company-related service history.
         [SCREENSHOT PROMPT: Capture the agent dashboard tools]

---

## SECTION 3 - FEATURE-BY-FEATURE GUIDE

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: User Authentication & Registration | JIRA: HYR-20
Who uses this: Customer, Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature lets users create accounts, verify accounts using OTP, log in securely, reset passwords, and access the correct dashboard based on their role.

HOW TO USE IT - STEP BY STEP:

Step 1: User opens **Register**.
         -> Expected result: The registration form appears.
         [SCREENSHOT PROMPT: Capture the full registration page]

Step 2: User enters account details.
         -> Expected result: The form displays the entered details.
         [SCREENSHOT PROMPT: Capture the completed registration form]

Step 3: User submits the form.
         -> Expected result: The OTP verification screen appears.
         [SCREENSHOT PROMPT: Capture the OTP input screen]

Step 4: User enters the OTP code.
         -> Expected result: The account is verified.
         [SCREENSHOT PROMPT: Capture the success message after OTP verification]

Step 5: User opens **Login**.
         -> Expected result: The login page appears.
         [SCREENSHOT PROMPT: Capture the login form]

Step 6: User enters email and password.
         -> Expected result: The system signs the user in and opens the correct dashboard.
         [SCREENSHOT PROMPT: Capture the role-specific dashboard after login]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Registration form accepts valid details.
☐ OTP verification succeeds with the correct code.
☐ Login redirects users to the correct role dashboard.
☐ Invalid login details show a clear error.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Secure Role-Based Login"*
→ Best screenshot moment: Successful login showing the dashboard.
→ Talking point: The platform gives each user a secure path into the system based on their role.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Vehicle Listing & Search | JIRA: HYR-21
Who uses this: Customer, Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature lets users browse available vehicles, search by keyword, and filter results by details such as location, type, seats, price, and driver preference.

HOW TO USE IT - STEP BY STEP:

Step 1: User opens **Vehicles**.
         -> Expected result: The vehicle listing page appears.
         [SCREENSHOT PROMPT: Capture the full vehicle listing page with cards visible]

Step 2: User types a keyword into the **Search** field.
         -> Expected result: Matching vehicles appear in the listing.
         [SCREENSHOT PROMPT: Capture the search field with filtered results]

Step 3: User selects filter options.
         -> Expected result: Vehicle results update based on the selected filters.
         [SCREENSHOT PROMPT: Capture the filter panel and updated vehicle cards]

Step 4: User clicks a vehicle card.
         -> Expected result: The vehicle detail page opens.
         [SCREENSHOT PROMPT: Capture an individual vehicle detail page]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Vehicle cards display name, image, price, location, and rating.
☐ Search results update correctly.
☐ Filters narrow the list correctly.
☐ Vehicle detail page opens from the listing.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Fast Vehicle Discovery"*
→ Best screenshot moment: Vehicle listing with search and filters applied.
→ Talking point: Customers can quickly narrow a large fleet to vehicles that fit their trip.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Vehicle Management - Admin CRUD | JIRA: HYR-22
Who uses this: Admin
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature allows the admin to create, view, update, and delete vehicle records so the platform inventory stays accurate.

HOW TO USE IT - STEP BY STEP:

Step 1: Admin opens the **Admin Dashboard**.
         -> Expected result: The admin management panels appear.
         [SCREENSHOT PROMPT: Capture the admin dashboard with vehicle management visible]

Step 2: Admin clicks **Add Vehicle**.
         -> Expected result: A vehicle form appears.
         [SCREENSHOT PROMPT: Capture the add vehicle form]

Step 3: Admin enters vehicle details.
         -> Expected result: The form shows vehicle name, category, type, price, location, and image fields.
         [SCREENSHOT PROMPT: Capture the completed vehicle form]

Step 4: Admin clicks **Save Vehicle**.
         -> Expected result: The vehicle is added to the inventory.
         [SCREENSHOT PROMPT: Capture the success message after adding a vehicle]

Step 5: Admin clicks **Edit** on an existing vehicle.
         -> Expected result: The edit form opens with existing data filled in.
         [SCREENSHOT PROMPT: Capture the edit vehicle form]

Step 6: Admin clicks **Delete** on a vehicle.
         -> Expected result: The system asks for confirmation before deleting.
         [SCREENSHOT PROMPT: Capture the delete confirmation dialog]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ New vehicles appear in the public listing.
☐ Edits update the vehicle detail page.
☐ Delete confirmation appears before removal.
☐ Vehicles with booking history are protected from unsafe deletion.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Admin Vehicle Inventory Control"*
→ Best screenshot moment: Admin vehicle table with add/edit/delete actions visible.
→ Talking point: Admins can maintain the entire rental inventory from one dashboard.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Database Setup & Backend Foundation | JIRA: HYR-23
Who uses this: Admin, System Maintainer
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This foundation stores users, roles, vehicles, bookings, payments, maintenance records, reviews, notifications, OTP codes, and refresh tokens in a structured MySQL database.

HOW TO USE IT - STEP BY STEP:

Step 1: Admin or maintainer imports the database schema.
         -> Expected result: Required database tables are created.
         [SCREENSHOT PROMPT: Capture the database tool showing Hyrox tables]

Step 2: Admin or maintainer loads seed data.
         -> Expected result: Example vehicles, users, and records are available for demonstration.
         [SCREENSHOT PROMPT: Capture seeded vehicle rows in the database]

Step 3: Admin opens the platform home page.
         -> Expected result: Data loads from the database into the website.
         [SCREENSHOT PROMPT: Capture the home page showing seeded vehicles]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Database connection works.
☐ Required tables exist.
☐ Seed data appears in the website.
☐ Errors are logged clearly if a database issue occurs.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Reliable Data Foundation"*
→ Best screenshot moment: Vehicle data appearing on the website from the database.
→ Talking point: The backend foundation supports every visible workflow in the platform.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Booking & Reservation Management | JIRA: HYR-65
Who uses this: Customer, Admin, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature lets customers reserve vehicles for selected dates, checks conflicts before confirmation, supports booking extension, vehicle changes, cancellation, and admin booking management.

HOW TO USE IT - STEP BY STEP:

Step 1: Customer opens a vehicle detail page.
         -> Expected result: Vehicle information and booking form appear.
         [SCREENSHOT PROMPT: Capture vehicle detail page with booking form]

Step 2: Customer selects rental start date.
         -> Expected result: The selected start date appears in the date field.
         [SCREENSHOT PROMPT: Capture the selected start date in the booking form]

Step 3: Customer selects rental end date.
         -> Expected result: The selected end date appears and the booking cost updates.
         [SCREENSHOT PROMPT: Capture the selected date range and total price]

Step 4: Customer clicks **Book Now**.
         -> Expected result: The system checks availability and creates a pending booking.
         [SCREENSHOT PROMPT: Capture the booking confirmation or pending booking screen]

Step 5: Customer clicks **Extend +2 Days** on an eligible booking.
         -> Expected result: The booking end date extends if the vehicle is available.
         [SCREENSHOT PROMPT: Capture the updated booking duration]

Step 6: Customer clicks **Change Vehicle**.
         -> Expected result: The system shows available replacement vehicles.
         [SCREENSHOT PROMPT: Capture the vehicle change selection screen]

Step 7: Customer clicks **Cancel Booking**.
         -> Expected result: A confirmation dialog appears.
         [SCREENSHOT PROMPT: Capture the cancel booking confirmation dialog]

Step 8: Admin opens **Bookings** from the dashboard.
         -> Expected result: Admin sees all customer bookings.
         [SCREENSHOT PROMPT: Capture the admin booking management table]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Date conflicts are blocked.
☐ Booking total is calculated correctly.
☐ Extension only works when extra dates are available.
☐ Cancellation asks for confirmation.
☐ Admin can view all bookings.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"End-to-End Booking Management"*
→ Best screenshot moment: Booking form showing selected dates and calculated price.
→ Talking point: The booking system protects availability while keeping the customer flow simple.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Payment System (Stripe Integration) | JIRA: HYR-66
Who uses this: Customer, Admin
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature sends customers to Stripe Checkout for secure payment, updates payment status automatically through webhooks, stores payment details, and lets admins view revenue.

HOW TO USE IT - STEP BY STEP:

Step 1: Customer creates a booking.
         -> Expected result: The payment option appears.
         [SCREENSHOT PROMPT: Capture the booking payment prompt]

Step 2: Customer clicks **Pay Now**.
         -> Expected result: The customer is redirected to Stripe Checkout.
         [SCREENSHOT PROMPT: Capture the redirect moment or Stripe Checkout page]

Step 3: Customer enters Stripe test card **4242 4242 4242 4242**.
         -> Expected result: Stripe accepts the test payment details.
         [SCREENSHOT PROMPT: Capture the Stripe card entry form with non-sensitive test data]

Step 4: Customer submits the Stripe payment.
         -> Expected result: Stripe completes the payment and redirects back to Hyrox.
         [SCREENSHOT PROMPT: Capture the successful Stripe payment screen]

Step 5: Customer views the payment status page.
         -> Expected result: Payment status shows **paid**, **pending**, or **failed**.
         [SCREENSHOT PROMPT: Capture the Hyrox payment status page]

Step 6: Admin opens the revenue dashboard.
         -> Expected result: Monthly revenue appears from payment data.
         [SCREENSHOT PROMPT: Capture the admin monthly revenue section]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Stripe Checkout opens correctly.
☐ Test card payment succeeds.
☐ Payment status updates after checkout.
☐ Webhook updates booking payment records.
☐ Admin revenue totals reflect paid bookings.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Secure Online Payments"*
→ Best screenshot moment: Payment status page showing a successful paid booking.
→ Talking point: Stripe handles the payment securely while Hyrox keeps booking records updated.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: JWT Authentication & Role-Based Access | JIRA: HYR-67
Who uses this: Customer, Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature uses secure JWT tokens and role checks so users only see pages and data they are allowed to access.

HOW TO USE IT - STEP BY STEP:

Step 1: User logs in.
         -> Expected result: The system creates secure access for the user.
         [SCREENSHOT PROMPT: Capture successful login for one role]

Step 2: User opens their dashboard.
         -> Expected result: The dashboard only shows sections allowed for that role.
         [SCREENSHOT PROMPT: Capture a role-specific dashboard view]

Step 3: User tries to open a restricted page.
         -> Expected result: The system blocks access or redirects the user.
         [SCREENSHOT PROMPT: Capture the access denied or redirected page]

Step 4: Admin opens the admin dashboard.
         -> Expected result: Admin sees platform-wide sections.
         [SCREENSHOT PROMPT: Capture admin-only sections]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Customer only sees their own bookings.
☐ Company only sees its own fleet data.
☐ Agent sees company-related support data.
☐ Admin sees platform-wide data.
☐ Restricted pages are protected.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Role-Based System Security"*
→ Best screenshot moment: Side-by-side dashboard screenshots for Admin and Customer.
→ Talking point: Each role gets the right level of access without exposing unrelated data.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Vehicle Availability & Calendar | JIRA: HYR-68
Who uses this: Customer, Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature shows booked and blocked vehicle dates on a calendar and prevents customers from booking unavailable dates.

HOW TO USE IT - STEP BY STEP:

Step 1: User opens a vehicle detail page.
         -> Expected result: The availability calendar appears.
         [SCREENSHOT PROMPT: Capture the vehicle detail page with calendar visible]

Step 2: User reviews blocked dates.
         -> Expected result: Booked or maintenance dates are visually marked.
         [SCREENSHOT PROMPT: Capture the calendar showing blocked dates]

Step 3: Customer selects an available date range.
         -> Expected result: The booking form accepts the selected dates.
         [SCREENSHOT PROMPT: Capture the accepted date range]

Step 4: Customer selects a blocked date range.
         -> Expected result: The system shows a conflict message.
         [SCREENSHOT PROMPT: Capture the date conflict warning]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Calendar loads on vehicle detail pages.
☐ Booked dates are blocked.
☐ Maintenance dates are blocked.
☐ API returns the correct dates for each vehicle.
☐ Conflict messages are clear.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Live Vehicle Availability"*
→ Best screenshot moment: Calendar with blocked and available dates visible.
→ Talking point: Customers can see availability before they commit to a booking.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Reviews & Ratings | JIRA: HYR-69
Who uses this: Customer, Admin
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature lets customers review completed rentals, rate vehicles with stars, and rate the overall website experience.

HOW TO USE IT - STEP BY STEP:

Step 1: Customer opens completed bookings.
         -> Expected result: Completed rentals are visible.
         [SCREENSHOT PROMPT: Capture customer completed bookings]

Step 2: Customer clicks **Leave Review**.
         -> Expected result: The review form opens.
         [SCREENSHOT PROMPT: Capture the vehicle review form]

Step 3: Customer selects a star rating.
         -> Expected result: The selected rating is highlighted.
         [SCREENSHOT PROMPT: Capture the selected star rating]

Step 4: Customer enters a comment.
         -> Expected result: The written review appears in the comment field.
         [SCREENSHOT PROMPT: Capture the completed review form]

Step 5: Customer submits the review.
         -> Expected result: The vehicle rating updates.
         [SCREENSHOT PROMPT: Capture the updated rating on the vehicle card]

Step 6: Customer submits a site review.
         -> Expected result: The overall service rating is saved.
         [SCREENSHOT PROMPT: Capture the site review form or confirmation message]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Only completed rentals can be reviewed.
☐ Star rating is required.
☐ Review comments save correctly.
☐ Average rating updates on vehicle cards.
☐ Site review saves separately from vehicle review.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Customer Feedback and Ratings"*
→ Best screenshot moment: Vehicle card showing average rating stars.
→ Talking point: Reviews help future customers choose the right rental vehicle.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Vehicle Maintenance & Service Records | JIRA: HYR-70
Who uses this: Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature lets internal users log maintenance and service records for vehicles. Vehicles under maintenance are blocked from bookings, and service history remains visible for tracking.

HOW TO USE IT - STEP BY STEP:

Step 1: Company or Admin opens the dashboard.
         -> Expected result: Maintenance and service sections are visible.
         [SCREENSHOT PROMPT: Capture maintenance/service area on dashboard]

Step 2: User selects a vehicle.
         -> Expected result: The selected vehicle appears in the maintenance form.
         [SCREENSHOT PROMPT: Capture the vehicle selector in the maintenance form]

Step 3: User enters maintenance dates.
         -> Expected result: The date range appears in the form.
         [SCREENSHOT PROMPT: Capture maintenance date fields]

Step 4: User saves the maintenance record.
         -> Expected result: The vehicle is blocked for those dates.
         [SCREENSHOT PROMPT: Capture the saved maintenance confirmation]

Step 5: User opens **Service History**.
         -> Expected result: Existing service records appear.
         [SCREENSHOT PROMPT: Capture the service history table]

Step 6: User adds a service record.
         -> Expected result: The record is saved and displayed in the history list.
         [SCREENSHOT PROMPT: Capture the newly added service record]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Maintenance record saves correctly.
☐ Vehicle becomes unavailable during maintenance.
☐ Service history appears per vehicle.
☐ Company and agent users see only permitted records.
☐ Admin can review all records.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Fleet Maintenance Tracking"*
→ Best screenshot moment: Service history table with records visible.
→ Talking point: Maintenance management protects customers from booking vehicles that are not ready.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Analytics & Reporting Dashboard | JIRA: HYR-71
Who uses this: Admin
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature gives admins a summary of platform performance, including most rented vehicles, monthly revenue, and key operating metrics.

HOW TO USE IT - STEP BY STEP:

Step 1: Admin opens the **Admin Dashboard**.
         -> Expected result: Analytics cards and reporting sections appear.
         [SCREENSHOT PROMPT: Capture the admin analytics overview]

Step 2: Admin reviews most rented vehicles.
         -> Expected result: A ranked vehicle list appears.
         [SCREENSHOT PROMPT: Capture the most rented vehicles section]

Step 3: Admin reviews monthly revenue.
         -> Expected result: Revenue data appears from Stripe payment records.
         [SCREENSHOT PROMPT: Capture the monthly revenue graph or summary]

Step 4: Admin reviews key metrics.
         -> Expected result: Booking, payment, and fleet summary numbers appear.
         [SCREENSHOT PROMPT: Capture the dashboard metric cards]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Most rented vehicles are ranked correctly.
☐ Revenue uses paid Stripe records.
☐ Metrics are visible on the admin dashboard.
☐ Dashboard numbers update after bookings and payments.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Admin Reporting and Insights"*
→ Best screenshot moment: Admin dashboard with metrics and revenue visible.
→ Talking point: Admins can monitor business performance without leaving the dashboard.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: Notifications & Reminders | JIRA: HYR-72
Who uses this: Customer, Admin, Company, Agent
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature sends in-app notifications for booking confirmations, cancellations, rental starts, returns, and other important updates.

HOW TO USE IT - STEP BY STEP:

Step 1: User logs in to the dashboard.
         -> Expected result: The notification bell is visible.
         [SCREENSHOT PROMPT: Capture the dashboard notification bell icon]

Step 2: Customer creates a booking.
         -> Expected result: A booking confirmation notification is created.
         [SCREENSHOT PROMPT: Capture the booking confirmation notification]

Step 3: Customer cancels a booking.
         -> Expected result: A cancellation notification is created.
         [SCREENSHOT PROMPT: Capture the cancellation notification]

Step 4: User clicks the notification bell.
         -> Expected result: The notification list opens.
         [SCREENSHOT PROMPT: Capture the notification dropdown or list]

Step 5: User marks notifications as read.
         -> Expected result: The unread count decreases or disappears.
         [SCREENSHOT PROMPT: Capture notification read status after marking read]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Notification bell appears on dashboards.
☐ Booking events create notifications.
☐ Unread count updates correctly.
☐ Notification history remains available.
☐ Role-specific notifications go to the correct users.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"Real-Time Rental Notifications"*
→ Best screenshot moment: Notification bell with unread notification list open.
→ Talking point: Notifications keep every role informed about rental activity.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FEATURE: AI Chatbot & Consulting Agent | JIRA: HYR-73
Who uses this: Customer, Guest User
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WHAT IT DOES:
This feature gives users an AI chatbot that answers rental questions and recommends vehicles based on budget, location, dates, seats, and driver preference.

HOW TO USE IT - STEP BY STEP:

Step 1: User opens the public home page.
         -> Expected result: The chatbot widget is visible.
         [SCREENSHOT PROMPT: Capture the home page with AI Help widget visible]

Step 2: User clicks **AI Help**.
         -> Expected result: The chatbot panel opens.
         [SCREENSHOT PROMPT: Capture the open chatbot panel]

Step 3: User enters rental preferences.
         -> Expected result: The chatbot accepts location, dates, budget, vehicle type, seats, and driver preference.
         [SCREENSHOT PROMPT: Capture the chatbot preference fields]

Step 4: User clicks **Recommend Vehicles**.
         -> Expected result: The system checks live availability.
         [SCREENSHOT PROMPT: Capture the live availability response in chat]

Step 5: User reviews AI recommendations.
         -> Expected result: Recommended vehicles and explanation appear.
         [SCREENSHOT PROMPT: Capture recommended vehicle cards inside the chatbot]

Step 6: User selects a recommended vehicle.
         -> Expected result: The user can continue toward the vehicle detail or booking flow.
         [SCREENSHOT PROMPT: Capture handoff from chatbot recommendation to vehicle detail]

WHAT TO LOOK FOR (QA CHECKLIST):
☐ Chatbot opens from the public site.
☐ User preferences are accepted.
☐ Live availability appears before AI explanation.
☐ Recommended vehicles match the request.
☐ User can continue toward booking.

NOTES FOR SLIDE DECK:
→ Key slide title suggestion: *"AI Vehicle Consulting Agent"*
→ Best screenshot moment: Chatbot showing live available vehicle cards.
→ Talking point: The chatbot helps users choose a vehicle faster using live platform data.

---

## SECTION 4 - ROLE PERMISSIONS SUMMARY TABLE

| Feature | Customer | Admin | Company | Agent |
|---|---:|---:|---:|---:|
| User Authentication & Registration | YES | YES | YES | YES |
| Vehicle Listing & Search | YES | YES | YES | YES |
| Vehicle Management - Admin CRUD | NO | YES | LIMITED | NO |
| Database Setup & Backend Foundation | NO | YES | NO | NO |
| Booking & Reservation Management | YES | YES | NO | YES |
| Payment System | YES | YES | NO | NO |
| JWT Authentication & Role-Based Access | YES | YES | YES | YES |
| Vehicle Availability & Calendar | YES | YES | YES | YES |
| Reviews & Ratings | YES | YES | NO | NO |
| Vehicle Maintenance & Service Records | NO | YES | YES | YES |
| Analytics & Reporting Dashboard | NO | YES | NO | NO |
| Notifications & Reminders | YES | YES | YES | YES |
| AI Chatbot & Consulting Agent | YES | YES | YES | YES |

---

## SECTION 5 - SCREENSHOT CHECKLIST FOR SLIDE DECK

FEATURE: User Authentication & Registration (HYR-20)
  □ Screenshot 1: Registration form with all fields visible
  □ Screenshot 2: OTP verification screen
  □ Screenshot 3: Successful OTP verification message
  □ Screenshot 4: Login form
  □ Screenshot 5: Successful login - customer dashboard
  □ Screenshot 6: Successful login - admin dashboard

FEATURE: Vehicle Listing & Search (HYR-21)
  □ Screenshot 7: Vehicle listing page with search bar
  □ Screenshot 8: Filter panel with options visible
  □ Screenshot 9: Filtered vehicle results
  □ Screenshot 10: Individual vehicle detail page

FEATURE: Vehicle Management - Admin CRUD (HYR-22)
  □ Screenshot 11: Admin vehicle management table
  □ Screenshot 12: Add vehicle form
  □ Screenshot 13: Edit vehicle form
  □ Screenshot 14: Delete confirmation dialog

FEATURE: Database Setup & Backend Foundation (HYR-23)
  □ Screenshot 15: Database tables visible
  □ Screenshot 16: Seeded vehicle rows
  □ Screenshot 17: Website loading database vehicle data

FEATURE: Booking & Reservation Management (HYR-65)
  □ Screenshot 18: Vehicle booking form
  □ Screenshot 19: Date range selected
  □ Screenshot 20: Booking confirmation screen
  □ Screenshot 21: Extend +2 Days action
  □ Screenshot 22: Change vehicle screen
  □ Screenshot 23: Cancel booking confirmation
  □ Screenshot 24: Admin booking management table

FEATURE: Payment System (HYR-66)
  □ Screenshot 25: Booking payment prompt
  □ Screenshot 26: Stripe Checkout page
  □ Screenshot 27: Test card entry using 4242 4242 4242 4242
  □ Screenshot 28: Payment success redirect
  □ Screenshot 29: Hyrox payment status page
  □ Screenshot 30: Admin monthly revenue section

FEATURE: JWT Authentication & Role-Based Access (HYR-67)
  □ Screenshot 31: Customer dashboard permissions
  □ Screenshot 32: Company dashboard permissions
  □ Screenshot 33: Agent dashboard permissions
  □ Screenshot 34: Admin dashboard permissions
  □ Screenshot 35: Restricted access message or redirect

FEATURE: Vehicle Availability & Calendar (HYR-68)
  □ Screenshot 36: Vehicle availability calendar
  □ Screenshot 37: Blocked dates on calendar
  □ Screenshot 38: Available date range accepted
  □ Screenshot 39: Conflict warning for unavailable dates

FEATURE: Reviews & Ratings (HYR-69)
  □ Screenshot 40: Completed booking ready for review
  □ Screenshot 41: Vehicle review form
  □ Screenshot 42: Star rating selected
  □ Screenshot 43: Updated vehicle rating on listing card
  □ Screenshot 44: Site review confirmation

FEATURE: Vehicle Maintenance & Service Records (HYR-70)
  □ Screenshot 45: Maintenance form
  □ Screenshot 46: Maintenance date range
  □ Screenshot 47: Maintenance saved confirmation
  □ Screenshot 48: Service history table
  □ Screenshot 49: New service record displayed

FEATURE: Analytics & Reporting Dashboard (HYR-71)
  □ Screenshot 50: Admin analytics overview
  □ Screenshot 51: Most rented vehicles list
  □ Screenshot 52: Monthly revenue graph or summary
  □ Screenshot 53: Dashboard metric cards

FEATURE: Notifications & Reminders (HYR-72)
  □ Screenshot 54: Notification bell icon
  □ Screenshot 55: Booking confirmation notification
  □ Screenshot 56: Cancellation notification
  □ Screenshot 57: Notification list open
  □ Screenshot 58: Notifications marked as read

FEATURE: AI Chatbot & Consulting Agent (HYR-73)
  □ Screenshot 59: AI Help widget on home page
  □ Screenshot 60: Open chatbot panel
  □ Screenshot 61: Chatbot preference fields
  □ Screenshot 62: Live availability result
  □ Screenshot 63: AI recommended vehicle cards
  □ Screenshot 64: Handoff from chatbot to vehicle detail

---

## SECTION 6 - COMMON ISSUES & TROUBLESHOOTING

| Feature | Most Likely Issue | How to Fix It | Contact If Unresolved |
|---|---|---|---|
| Authentication | User cannot log in. | Check email, password, OTP status, and role approval. | Admin or technical support |
| Vehicle Search | Vehicles do not appear. | Clear filters and confirm vehicles exist in the database. | Admin |
| Vehicle CRUD | Vehicle image does not upload. | Check file type, file size, and upload folder permissions. | Technical support |
| Database | Page shows database error. | Confirm MySQL is running and database settings are correct. | Technical support |
| Booking | Date range is rejected. | Check calendar for booked or maintenance-blocked dates. | Admin or agent |
| Payment | Stripe payment remains pending. | Refresh payment status and confirm webhook configuration. | Technical support |
| Role Access | User sees the wrong dashboard. | Confirm the assigned role in the user record. | Admin |
| Availability Calendar | Calendar does not show blocked dates. | Confirm booking and maintenance records exist for that vehicle. | Technical support |
| Reviews | Review button is missing. | Confirm the booking is completed and belongs to the customer. | Admin |
| Maintenance | Vehicle is still bookable during maintenance. | Confirm maintenance dates were saved and availability block was created. | Admin or technical support |
| Analytics | Revenue looks incorrect. | Confirm Stripe payments are marked paid. | Admin or technical support |
| Notifications | Notification bell count is wrong. | Mark all as read and refresh dashboard. | Technical support |
| AI Chatbot | No recommendations appear. | Enter complete preferences and confirm matching vehicles are available. | Technical support |

---

## SECTION 7 - ADMIN-ONLY OPERATIONS

How to add a vehicle:

Step 1: Admin opens **Admin Dashboard**.
         -> Expected result: Admin management tools appear.
         [SCREENSHOT PROMPT: Capture the admin dashboard]

Step 2: Admin clicks **Add Vehicle**.
         -> Expected result: The add vehicle form opens.
         [SCREENSHOT PROMPT: Capture the add vehicle form]

Step 3: Admin enters vehicle details.
         -> Expected result: The form contains name, category, type, price, location, image, and availability details.
         [SCREENSHOT PROMPT: Capture the completed add vehicle form]

Step 4: Admin clicks **Save Vehicle**.
         -> Expected result: The vehicle is saved and appears in the inventory.
         [SCREENSHOT PROMPT: Capture the success message and new vehicle row]

How to edit a vehicle:

Step 1: Admin finds the vehicle in the vehicle table.
         -> Expected result: The vehicle row is visible.
         [SCREENSHOT PROMPT: Capture the selected vehicle row]

Step 2: Admin clicks **Edit**.
         -> Expected result: The edit form opens.
         [SCREENSHOT PROMPT: Capture the edit vehicle form]

Step 3: Admin updates the required field.
         -> Expected result: The new value appears in the form.
         [SCREENSHOT PROMPT: Capture the updated field before saving]

Step 4: Admin clicks **Save**.
         -> Expected result: The vehicle record updates.
         [SCREENSHOT PROMPT: Capture the updated vehicle row]

How to delete a vehicle:

Step 1: Admin finds the vehicle in the vehicle table.
         -> Expected result: The vehicle row is visible.
         [SCREENSHOT PROMPT: Capture the vehicle row before deletion]

Step 2: Admin clicks **Delete**.
         -> Expected result: A confirmation dialog appears.
         [SCREENSHOT PROMPT: Capture the delete confirmation dialog]

Step 3: Admin confirms deletion.
         -> Expected result: The vehicle is removed if allowed.
         [SCREENSHOT PROMPT: Capture the vehicle table after deletion]

How to manage bookings:

Step 1: Admin opens **Bookings**.
         -> Expected result: All bookings appear in a table.
         [SCREENSHOT PROMPT: Capture the admin bookings table]

Step 2: Admin selects a booking.
         -> Expected result: Booking details are visible.
         [SCREENSHOT PROMPT: Capture selected booking details]

Step 3: Admin updates booking status.
         -> Expected result: The booking status changes.
         [SCREENSHOT PROMPT: Capture the updated booking status]

How to view revenue reports:

Step 1: Admin opens **Analytics** or the dashboard revenue section.
         -> Expected result: Revenue summary appears.
         [SCREENSHOT PROMPT: Capture the revenue section]

Step 2: Admin reviews monthly revenue.
         -> Expected result: Paid Stripe booking totals are visible.
         [SCREENSHOT PROMPT: Capture monthly revenue totals]

How to log maintenance records:

Step 1: Admin opens the maintenance section.
         -> Expected result: Maintenance form appears.
         [SCREENSHOT PROMPT: Capture the maintenance form]

Step 2: Admin selects a vehicle.
         -> Expected result: The vehicle is selected.
         [SCREENSHOT PROMPT: Capture vehicle selected in the maintenance form]

Step 3: Admin enters maintenance date range.
         -> Expected result: The blocked maintenance period appears.
         [SCREENSHOT PROMPT: Capture maintenance date range]

Step 4: Admin saves the maintenance record.
         -> Expected result: The vehicle is blocked from booking for those dates.
         [SCREENSHOT PROMPT: Capture maintenance saved confirmation]

How to view analytics dashboard:

Step 1: Admin opens the dashboard.
         -> Expected result: Metric cards appear.
         [SCREENSHOT PROMPT: Capture dashboard metric cards]

Step 2: Admin reviews most rented vehicles.
         -> Expected result: Ranked vehicle list appears.
         [SCREENSHOT PROMPT: Capture most rented vehicles list]

Step 3: Admin reviews revenue.
         -> Expected result: Revenue data appears.
         [SCREENSHOT PROMPT: Capture revenue chart or summary]

How to manage user accounts and roles:

Step 1: Admin opens the user management section.
         -> Expected result: User accounts appear in a list.
         [SCREENSHOT PROMPT: Capture user management table]

Step 2: Admin selects a user.
         -> Expected result: User details are visible.
         [SCREENSHOT PROMPT: Capture selected user details]

Step 3: Admin updates the user role or status.
         -> Expected result: The user permissions change.
         [SCREENSHOT PROMPT: Capture updated role or status]

---

## SECTION 8 - AI CHATBOT GUIDE

Step 1: User opens the public Hyrox website.
         -> Expected result: The home page loads.
         [SCREENSHOT PROMPT: Capture the public home page with the chatbot widget visible]

Step 2: User clicks **AI Help**.
         -> Expected result: The chatbot window opens.
         [SCREENSHOT PROMPT: Capture the open chatbot window]

Step 3: User asks a rental question.
         -> Expected result: The chatbot responds with rental guidance.
         [SCREENSHOT PROMPT: Capture a question and chatbot answer]

Step 4: User enters preferred location.
         -> Expected result: The chatbot stores the location preference.
         [SCREENSHOT PROMPT: Capture location entered in chatbot]

Step 5: User enters travel dates.
         -> Expected result: The chatbot stores the date range.
         [SCREENSHOT PROMPT: Capture date fields in chatbot]

Step 6: User enters budget, seats, vehicle type, and driver preference.
         -> Expected result: The chatbot has enough information to recommend vehicles.
         [SCREENSHOT PROMPT: Capture all preference fields completed]

Step 7: User clicks **Recommend Vehicles**.
         -> Expected result: Live availability is checked.
         [SCREENSHOT PROMPT: Capture live availability message]

Step 8: User reviews recommended vehicles.
         -> Expected result: The chatbot shows matching vehicles with explanations.
         [SCREENSHOT PROMPT: Capture recommended vehicle cards and AI explanation]

Step 9: User opens a recommended vehicle.
         -> Expected result: The user moves from chatbot guidance to the booking flow.
         [SCREENSHOT PROMPT: Capture vehicle detail page opened from chatbot recommendation]

Questions the chatbot can answer:

1. Which vehicles are available in a location?
2. Which vehicle is best for a certain budget?
3. Which vehicle fits a group size?
4. Which vehicles support self-drive or driver options?
5. How to continue from recommendation to booking?

---

## SECTION 9 - PAYMENT FLOW WALKTHROUGH

Step 1: Customer selects a vehicle.
         -> Expected result: The vehicle detail page opens.
         [SCREENSHOT PROMPT: Capture the selected vehicle detail page]

Step 2: Customer fills the booking form.
         -> Expected result: Start date, end date, and total price appear.
         [SCREENSHOT PROMPT: Capture the completed booking form]

Step 3: Customer clicks **Book Now**.
         -> Expected result: A booking is created and payment option appears.
         [SCREENSHOT PROMPT: Capture the booking created/payment prompt]

Step 4: Customer clicks **Pay Now**.
         -> Expected result: Stripe Checkout opens.
         [SCREENSHOT PROMPT: Capture the Stripe Checkout page]

Step 5: Customer enters test card **4242 4242 4242 4242**.
         -> Expected result: Stripe accepts the card details for testing.
         [SCREENSHOT PROMPT: Capture Stripe test card entry with safe test data]

Step 6: Customer completes payment.
         -> Expected result: Stripe confirms payment and redirects back to Hyrox.
         [SCREENSHOT PROMPT: Capture Stripe payment completion or redirect]

Step 7: Customer views payment status.
         -> Expected result: Payment status shows **paid**.
         [SCREENSHOT PROMPT: Capture the Hyrox payment status page showing paid]

Step 8: Customer opens the dashboard.
         -> Expected result: Booking appears as confirmed or paid.
         [SCREENSHOT PROMPT: Capture the customer dashboard with paid booking]

Step 9: Customer opens notifications.
         -> Expected result: Booking/payment notification appears.
         [SCREENSHOT PROMPT: Capture the booking confirmation notification]

---

## SECTION 10 - GLOSSARY

| Term | Meaning |
|---|---|
| API | A connection point that lets the website request or send data behind the scenes. |
| Availability Calendar | A calendar that shows which dates a vehicle can or cannot be booked. |
| Booking | A reservation made by a customer for a vehicle and date range. |
| CRUD | Create, Read, Update, Delete. These are the four basic actions for managing records. |
| Dashboard | A role-specific page where users manage their tasks and view important information. |
| JWT Token | A secure login token that proves who the user is after signing in. |
| Maintenance Record | A record showing when a vehicle is being repaired or inspected. |
| OTP | One-Time Password. A temporary code used to verify a user account or action. |
| Payment Status | The current state of a payment, such as pending, paid, or failed. |
| Role-Based Access | A system that shows each user only the pages and data allowed for their role. |
| Service History | A list of past service work completed on a vehicle. |
| Stripe Checkout | A secure Stripe payment page used to collect online payments. |
| Stripe Webhook | A Stripe message sent back to Hyrox to confirm payment events automatically. |
| Vehicle Availability | Whether a vehicle can be booked for a selected date range. |
| Vehicle Listing | The page where customers browse and compare vehicles. |
| Webhook Handler | The backend code that receives automatic updates from Stripe. |

---

✅ USER MANUAL COMPLETE - 129 total steps documented | 132 screenshots required
