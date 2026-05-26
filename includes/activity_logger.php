<?php
/**
 * Log user actions for audit trail
 *
 * @param PDO $pdo Database connection
 * @param int $user_id ID of the user performing action
 * @param string $action Action description (e.g., "Product created")
 * @param mixed $details Optional details (string or array, will be JSON encoded)
 * @return bool Success status
 */
function logActivity($pdo, $user_id, $action, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Convert array details to JSON for storage
    if (is_array($details)) {
        $details = json_encode($details);
    }

    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action, $details, $ip_address]);
}
?>