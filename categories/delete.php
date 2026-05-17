<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$category_id = $_GET['id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    header('Location: list.php');
    exit;
}

try {
    // Fetch category name first to check exist and protect system category
    $stmt_fetch = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
    $stmt_fetch->execute([$category_id]);
    $cat_name = $stmt_fetch->fetchColumn();

    if (!$cat_name || strtolower($cat_name) === 'other') {
        header('Location: list.php');
        exit;
    }

    // Get 'Other' category ID to reassign products safely
    $stmt_other = $pdo->query("SELECT id FROM categories WHERE name = 'Other'");
    $other_id = $stmt_other->fetchColumn();

    if (!$other_id) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('Other')");
        $other_id = $pdo->lastInsertId();
    }

    $pdo->beginTransaction();

    // Reassign products to 'Other' category and update legacy text column
    $stmt_reassign = $pdo->prepare('UPDATE products SET category_id = ?, category = ? WHERE category_id = ?');
    $stmt_reassign->execute([$other_id, 'Other', $category_id]);

    // Delete the category
    $stmt_delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt_delete->execute([$category_id]);

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Delete Category Error: ' . $e->getMessage());
}

header('Location: list.php');
exit;
