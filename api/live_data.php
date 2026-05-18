<?php
/**
 * Live Data API - Smart Inventory Management System
 * Provides real-time JSON metrics for the Live Operations Dashboard.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

try {
    $data = [
        'metrics' => ['total_value' => 0, 'total_units' => 0],
        'health' => ['green' => 0, 'yellow' => 0, 'red' => 0],
        'charts' => [
            'category' => ['labels' => [], 'data' => []],
            'line' => ['labels' => [], 'data' => []]
        ],
        'recent_activity' => [],
        'top_moving' => []
    ];

    // 1. Calculate Current Stock Levels per Product
    // We calculate stock = sum(IN) - sum(OUT)
    $stock_stmt = $pdo->query("
        SELECT 
            p.id, p.name, p.category, p.price,
            COALESCE(SUM(CASE WHEN sm.movement_type = 'IN' THEN sm.quantity ELSE -sm.quantity END), 0) as current_stock
        FROM products p
        LEFT JOIN stock_movements sm ON p.id = sm.product_id
        GROUP BY p.id
    ");
    $inventory = $stock_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aggregate Metrics & Health & Category Distribution
    $category_map = [];
    foreach ($inventory as $item) {
        $stock = (int)$item['current_stock'];
        $price = (float)$item['price'];
        $cat = $item['category'] ?: 'Other';

        if ($stock > 0) {
            $data['metrics']['total_units'] += $stock;
            $data['metrics']['total_value'] += ($stock * $price);
            
            if (!isset($category_map[$cat])) $category_map[$cat] = 0;
            $category_map[$cat] += $stock;
        }

        if ($stock >= 15) {
            $data['health']['green']++;
        } elseif ($stock > 0) {
            $data['health']['yellow']++;
        } else {
            $data['health']['red']++;
        }
    }

    // Format Category Chart Data
    foreach ($category_map as $label => $val) {
        $data['charts']['category']['labels'][] = $label;
        $data['charts']['category']['data'][] = $val;
    }

    // 2. 7-Day Trend Chart (Transaction Volume)
    // Get last 7 days including today
    $trend_stmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(quantity) as volume
        FROM stock_movements
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $trend_results = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);
    $trend_map = [];
    foreach ($trend_results as $row) {
        $trend_map[$row['date']] = (int)$row['volume'];
    }

    // Fill in missing days with 0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $data['charts']['line']['labels'][] = date('M d', strtotime($date));
        $data['charts']['line']['data'][] = $trend_map[$date] ?? 0;
    }

    // 3. Recent Activity (Last 5 movements)
    $recent_stmt = $pdo->query("
        SELECT sm.movement_type, sm.quantity, sm.created_at, p.name as product_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        ORDER BY sm.created_at DESC
        LIMIT 5
    ");
    $data['recent_activity'] = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top Moving Products (Most units OUT)
    $top_stmt = $pdo->query("
        SELECT p.name, COALESCE(SUM(sm.quantity), 0) as total_out
        FROM products p
        JOIN stock_movements sm ON p.id = sm.product_id
        WHERE sm.movement_type = 'OUT'
        GROUP BY p.id
        ORDER BY total_out DESC
        LIMIT 5
    ");
    $data['top_moving'] = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter out products with 0 movement from the leaderboard
    $data['top_moving'] = array_filter($data['top_moving'], function($item) {
        return (int)$item['total_out'] > 0;
    });

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'msg' => $e->getMessage()]);
}
