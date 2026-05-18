<?php
/**
 * Database Migration Script
 * Safe, idempotent database schema updates
 */
require_once __DIR__ . '/../config/db.php';

try {
    echo "Starting Database Migration...<br>";

    // Helper function to check if a column exists securely using INFORMATION_SCHEMA
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        return !empty($result);
    }

    $altered = false;

    // 1. Add email column
    if (!columnExists($pdo, 'users', 'email')) {
        echo "Adding 'email' column to 'users' table...<br>";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) NULL UNIQUE DEFAULT NULL");
        $altered = true;
    } else {
        echo "'email' column already exists.<br>";
    }

    // 2. Add fullname column
    if (!columnExists($pdo, 'users', 'fullname')) {
        echo "Adding 'fullname' column to 'users' table...<br>";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `fullname` VARCHAR(100) NULL DEFAULT NULL");
        $altered = true;
    } else {
        echo "'fullname' column already exists.<br>";
    }

    // 3. Add google_id column
    if (!columnExists($pdo, 'users', 'google_id')) {
        echo "Adding 'google_id' column to 'users' table...<br>";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `google_id` VARCHAR(255) NULL UNIQUE DEFAULT NULL");
        $altered = true;
    } else {
        echo "'google_id' column already exists.<br>";
    }

    // 4. Add status column
    if (!columnExists($pdo, 'users', 'status')) {
        echo "Adding 'status' column to 'users' table...<br>";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'PENDING'");
        // Automatically approve any existing users (especially the admin)
        $pdo->exec("UPDATE `users` SET `status` = 'APPROVED'");
        $altered = true;
    } else {
        echo "'status' column already exists.<br>";
    }

    if ($altered) {
        echo "<strong>Migration completed successfully!</strong>";
    } else {
        echo "<strong>Database is already up to date. No changes needed.</strong>";
    }

} catch (PDOException $e) {
    echo "<strong style='color:red;'>Migration failed:</strong> " . htmlspecialchars($e->getMessage());
}
?>
