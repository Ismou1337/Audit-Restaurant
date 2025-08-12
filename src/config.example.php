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
define('SMTP_HOST', 'your.smtp.server.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@domain.com');
define('SMTP_PASS', 'your_email_password');
define('FROM_EMAIL', 'noreply@domain.com');
define('FROM_NAME', 'Audit Restaurant System');

// Application settings
define('APP_URL', 'https://check.restaurail.ma');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PHOTOS_PATH', __DIR__ . '/../photos/');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

/**
 * Database connection function
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }
    
    return $pdo;
}

// Other configuration settings can be added here
?>
