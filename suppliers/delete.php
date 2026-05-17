<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$supplier_id = $_GET['id'] ?? null;

if ($supplier_id && is_numeric($supplier_id)) {
    try {
        $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$supplier_id]);
    } catch (PDOException $e) {
        error_log('Delete Supplier Error: ' . $e->getMessage());
    }
}

header('Location: list.php');
exit;
?>
