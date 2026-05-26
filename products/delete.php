<?php
/**
 * Delete Product — with notification trigger
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/notification_helper.php';

$product_id = $_GET['id'] ?? null;

// Validate product ID
if (!$product_id || !is_numeric($product_id)) {
    header('Location: list.php');
    exit;
}

try {
    // Fetch product name before deleting for notification
    $stmt_name = $pdo->prepare('SELECT name FROM products WHERE id = ?');
    $stmt_name->execute([$product_id]);
    $product_name = $stmt_name->fetchColumn();

    // Delete product
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$product_id]);

    // *** NEW: Create notification ***
    if ($product_name) {
        createNotification(
            $pdo,
            'Product Deleted',
            '"' . $product_name . '" has been removed from the inventory catalog.',
            'warning',
            'products/list.php'
        );
    }

    $_SESSION['flash_msg'] = 'Product deleted successfully.';
    $_SESSION['flash_type'] = 'success';
    header('Location: list.php');
    exit;
} catch (PDOException $e) {
    error_log('Delete Product Error: ' . $e->getMessage());
    $_SESSION['flash_msg'] = 'Error deleting product. It may be linked to existing inventory movements.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: list.php');
    exit;
}
?>