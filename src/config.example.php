<?php
// Configuration template for the application
// Copy this file to config.php and update with your actual values

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Set the default timezone
date_default_timezone_set('Europe/Paris');

// Email configuration
define('SMTP_HOST', 'your_smtp_host');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@domain.com');
define('SMTP_PASS', 'your_email_password');
define('FROM_EMAIL', 'your_email@domain.com');
define('FROM_NAME', 'Audit System');

// Application settings
define('APP_URL', 'https://your-domain.com');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PHOTOS_PATH', __DIR__ . '/../photos/');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// Other configuration settings can be added here
?>
