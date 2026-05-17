<?php
/**
 * Database Configuration & Connection
 * Using PDO for reusable database connection
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '[7eu!5)!KiL@ShO@');
define('DB_NAME', 'smart_inventory');

try {
    // Create PDO connection
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {
    // Error handling
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please contact administrator.');
}
?>
