<?php
/**
 * API: Get unread notification count
 */
session_start();
require_once '../config/db.php';
require_once '../config/notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['count' => 0]);
    exit;
}

$count = getUnreadCount($pdo);
echo json_encode(['count' => $count]);