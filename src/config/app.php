<?php

date_default_timezone_set('Asia/Kathmandu');

const APP_NAME = 'Vehicle Rental System';
const APP_TAGLINE = 'Reserve verified vehicles across trusted companies.';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'vehicle_rental_system';
const DB_USER = 'root';
const DB_PASS = '';

// Mail settings are for the sender email only.
// Set these once with one real Gmail account and its app password.
// You do not change these values for every user.
// The receiver email is taken automatically from the registration form.
const MAIL_HOST = 'smtp.gmail.com';
const MAIL_PORT = 587;
const MAIL_USERNAME = 'nobodyknows5175@gmail.com';
const MAIL_PASSWORD = 'yuyfvbywjlsidbcv';
const MAIL_FROM_EMAIL = 'nobodyknows5175@gmail.com';
const MAIL_FROM_NAME = 'Vehicle_Rental_System';
const MAIL_ENCRYPTION = 'tls';


const MAX_UPLOAD_SIZE = 5_242_880;
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const ALLOWED_DOCUMENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
const USER_ROLES = ['super_admin', 'company', 'agent', 'user'];
const BOOKING_STATUSES = ['pending', 'confirmed', 'cancelled'];
const COMPANY_STATUSES = ['pending', 'approved', 'rejected'];
const ACCOUNT_STATUSES = ['active', 'inactive', 'pending'];
const VEHICLE_STATUSES = ['available', 'unavailable', 'maintenance'];
const VEHICLE_TYPES = ['car', 'bike', 'bus', 'van', 'jeep', 'suv', 'scooter'];

const VEHICLE_UPLOAD_DIR = 'public/assets/uploads/vehicles';
const DOCUMENT_UPLOAD_DIR = 'public/assets/uploads/documents';

define('APP_ROOT', dirname(__DIR__));
