<?php
/**
 * Recent Movements API - returns JSON
 * Used by both WebSocket-triggered and fallback polling updates
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT sm.movement_type, sm.quantity, sm.reason, sm.reference_no, sm.created_at, 
               p.name as product_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        ORDER BY sm.created_at DESC
        LIMIT 5
    ");
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($movements);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
