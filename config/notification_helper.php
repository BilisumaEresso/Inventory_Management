<?php
/**
 * Notification Helper Functions
 * Lightweight, database-driven notification system
 */

/**
 * Create a new notification
 */
function createNotification($pdo, $title, $message, $type = 'info', $link = null) {
    try {
        $stmt = $pdo->prepare('INSERT INTO notifications (title, message, type, link) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $message, $type, $link]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Create Notification Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count
 */
function getUnreadCount($pdo) {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get recent notifications (for dropdown)
 */
function getRecentNotifications($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark a notification as read
 */
function markNotificationRead($pdo, $id) {
    try {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log('Mark Read Error: ' . $e->getMessage());
    }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsRead($pdo) {
    try {
        $pdo->exec('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
    } catch (PDOException $e) {
        error_log('Mark All Read Error: ' . $e->getMessage());
    }
}

/**
 * Get recent activity for dashboard (combines stock movements + notifications)
 */
function getDashboardActivity($pdo, $limit = 8) {
    $activity = [];

    try {
        // Recent stock movements
        $stmt = $pdo->query("
            (SELECT 'movement' AS type,
                    CONCAT(p.name, ' — ',
                           CASE sm.movement_type WHEN 'IN' THEN 'Stock in (+' WHEN 'OUT' THEN 'Stock out (-' END,
                           sm.quantity, ')') AS description,
                    sm.created_at
             FROM stock_movements sm
             JOIN products p ON p.id = sm.product_id
             ORDER BY sm.created_at DESC
             LIMIT 5)
            UNION ALL
            (SELECT 'notification' AS type, title AS description, created_at
             FROM notifications
             ORDER BY created_at DESC
             LIMIT 5)
            ORDER BY created_at DESC
            LIMIT $limit
        ");
        $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Activity Feed Error: ' . $e->getMessage());
    }

    return $activity;
}
?>