<?php
// Configuration file for Meal Management System
// IMPORTANT: Rename this file to config.php and update with your actual credentials

// Database Configuration
define('DB_HOST', 'localhost');           // Database host (usually localhost)
define('DB_USER', 'root');                // Database username
define('DB_PASS', '');                    // Database password (leave empty for XAMPP default)
define('DB_NAME', 'meal_management');     // Database name

// Application Configuration
define('APP_NAME', 'Meal Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/Meal-2.0'); // Change to your domain in production

// Security Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 6);

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Timezone
date_default_timezone_set('Asia/Dhaka'); // Change to your timezone

// Error Reporting
// Set to 0 in production for security
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

