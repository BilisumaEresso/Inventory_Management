<?php
/**
 * API: Mark a single notification as read
 */
session_start();
require_once '../config/db.php';
require_once '../config/notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false]);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    markNotificationRead($pdo, $id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}