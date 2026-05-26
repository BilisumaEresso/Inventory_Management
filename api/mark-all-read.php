<?php
/**
 * API: Mark all notifications as read
 */
session_start();
require_once '../config/db.php';
require_once '../config/notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false]);
    exit;
}

markAllNotificationsRead($pdo);
echo json_encode(['success' => true]);