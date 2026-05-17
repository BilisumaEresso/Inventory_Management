<?php
// Step 19: Delete Product
require_once '../middleware/auth.php';
require_once '../config/db.php';

$product_id = $_GET['id'] ?? null;

// Validate product ID
if (!$product_id || !is_numeric($product_id)) {
    header('Location: list.php');
    exit;
}

try {
    // Delete product using prepared statement
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$product_id]);

    // Redirect back to list
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

