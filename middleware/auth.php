<?php
/**
 * Route Protection & Approval Middleware
 * Protects pages from unauthorized access, handles PENDING approvals, and blocks BANNED/DECLINED accounts.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // Dynamic relative redirect to login
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $prefix = ($current_dir !== 'Inventory_Management' && $current_dir !== '') ? '../' : './';
    header('Location: ' . $prefix . 'auth/login.php');
    exit;
}

// 2. Query user status dynamically to ensure real-time security
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User account deleted
        session_destroy();
        $_SESSION = [];
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        $prefix = ($current_dir !== 'Inventory_Management' && $current_dir !== '') ? '../' : './';
        header('Location: ' . $prefix . 'auth/login.php');
        exit;
    }

    $status = $user['status'] ?? 'PENDING';
    $current_file = basename($_SERVER['PHP_SELF']);

    if ($status === 'PENDING') {
        // Redirect to pending.php if they try to access any other protected page
        if ($current_file !== 'pending.php') {
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));
            $prefix = ($current_dir !== 'Inventory_Management' && $current_dir !== '') ? '../' : './';
            header('Location: ' . $prefix . 'auth/pending.php');
            exit;
        }
    } elseif ($status === 'BANNED' || $status === 'DECLINED') {
        // Account has been banned or declined. Terminate session and show message.
        session_destroy();
        $_SESSION = [];
        
        session_start();
        $_SESSION['flash_msg'] = "Your account has been banned or declined by the administrator.";
        $_SESSION['flash_type'] = "danger";
        
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        $prefix = ($current_dir !== 'Inventory_Management' && $current_dir !== '') ? '../' : './';
        header('Location: ' . $prefix . 'auth/login.php');
        exit;
    } else {
        // Approved user! If they are on pending.php, redirect them to dashboard!
        if ($current_file === 'pending.php') {
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));
            $prefix = ($current_dir !== 'Inventory_Management' && $current_dir !== '') ? '../' : './';
            header('Location: ' . $prefix . 'dashboard.php');
            exit;
        }
    }

} catch (PDOException $e) {
    // Fail-safe: if DB query errors, allow active session (prevents system lockouts if DB is busy)
}
?>
