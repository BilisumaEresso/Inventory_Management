<?php
/**
 * Dashboard Stats API - returns JSON
 * Used by both WebSocket-triggered and fallback polling updates
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stock_helper.php';

try {
    $stats = getStockStatistics($pdo);
    
    // Total suppliers
    $stmt_supp = $pdo->query('SELECT COUNT(*) FROM suppliers');
    $total_suppliers = $stmt_supp->fetchColumn();
    
    echo json_encode([
        'total_products' => $stats['total_products'],
        'total_units' => $stats['total_units'],
        'low_stock' => $stats['low_stock'],
        'out_of_stock' => $stats['out_of_stock'],
        'total_value' => $stats['total_value'],
        'total_suppliers' => (int)$total_suppliers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
