<?php
/**
 * Step 12: Create Route Protection
 *
 * Include this at the top of protected pages:
 * require_once '../middleware/auth.php';
 *
 * Pages protected: dashboard.php, products/*
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login if not authenticated
    header('Location: ' . dirname(dirname(__FILE__)) . '/auth/login.php');
    exit;
}
?>

