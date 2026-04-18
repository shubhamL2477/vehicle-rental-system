# Simple API Notes

## Important

- `src/config/database.php` is the **database connection file**
- `src/api/*.php` are the **API endpoints**
- So, database connection is **not** an API

## APIs Added

- `src/api/register.php`
  - Registers a new user or company
  - Validates input
  - Hashes password
  - Creates OTP

- `src/api/verify_otp.php`
  - Checks OTP code
  - Checks expiry time
  - Activates normal user account
  - Keeps company pending for admin approval

- `src/api/login.php`
  - Checks email/phone and password
  - Starts PHP session
  - Returns session id in JSON

- `src/api/save_vehicle.php`
  - Create or update vehicle
  - Admin roles: `super_admin`, `company`, `agent`

- `src/api/fetch_vehicles.php`
  - Returns vehicle list in JSON
  - Supports filters like search, type, company, location, and status

- `src/api/search_vehicles.php`
  - Returns filtered vehicles for AJAX search
  - Limits result count for faster response

- `src/api/gps_mock.php`
  - Simulates GPS update with dummy latitude and longitude

## Example URLs

- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/register.php`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/login.php`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/verify_otp.php`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/save_vehicle.php`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/fetch_vehicles.php`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/search_vehicles.php?query=toyota`
- `http://localhost/Collaborative-Development/vehicle-rental-system/src/api/gps_mock.php?vehicle_id=1`
