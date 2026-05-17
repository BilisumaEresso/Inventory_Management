<?php
/**
 * Step 13: Logout
 *
 * Flow:
 * 1. Start session
 * 2. Destroy session
 * 3. Redirect to login
 */

session_start();
session_destroy();
header('Location: login.php');
exit;
?>

