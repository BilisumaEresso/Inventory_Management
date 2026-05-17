<?php
// No auth check required! Public JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/db.php';
require_once '../../config/stock_helper.php';

try {
    $stats = getStockStatistics($pdo);
    
    // Metrics
    $total_value = $stats['total_value'] ?? 0;
    $total_units = $stats['total_units'] ?? 0;
    
    // Stock Health
    $health = [
        'green' => 0, // >= 10
        'yellow' => 0, // < 10 and > 0
        'red' => 0, // == 0
    ];
    
    $chart_categories = [];
    
    foreach ($stats['products'] as $p) {
        $stock = $p['stock'];
        if ($stock >= 10) {
            $health['green']++;
        } elseif ($stock > 0) {
            $health['yellow']++;
        } else {
            $health['red']++;
        }
        
        // Categories (Count by stock units)
        if (!isset($chart_categories[$p['category']])) {
            $chart_categories[$p['category']] = 0;
        }
        $chart_categories[$p['category']] += $stock;
    }
    
    // Top Moving Products (Highest OUT quantity)
    $stmt_top = $pdo->query("
        SELECT p.name, SUM(sm.quantity) as total_out
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        WHERE sm.movement_type = 'OUT'
        GROUP BY p.id
        ORDER BY total_out DESC
        LIMIT 5
    ");
    $top_moving = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent Activity Feed
    $stmt_recent = $pdo->query("
        SELECT sm.movement_type, sm.quantity, sm.created_at, p.name as product_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        ORDER BY sm.created_at DESC
        LIMIT 10
    ");
    $recent_activity = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly Trend Chart Data
    $stmt_trend = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) as total_in,
            SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) as total_out
        FROM stock_movements
        GROUP BY month
        ORDER BY month ASC
        LIMIT 6
    ");
    $trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);
    
    // Stock Trend Line Chart Data (Daily total movement volume over last 7 days)
    $stmt_line = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            SUM(quantity) as total_movement
        FROM stock_movements
        GROUP BY date
        ORDER BY date DESC
        LIMIT 7
    ");
    $line_data_raw = $stmt_line->fetchAll(PDO::FETCH_ASSOC);
    
    // Pre-populate last 7 days with 0 values for proper line chart rendering
    $line_labels_map = [];
    $line_values_map = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $line_labels_map[$d] = date('M d', strtotime($d));
        $line_values_map[$d] = 0;
    }
    
    // Merge actual database records
    foreach ($line_data_raw as $row) {
        $row_date = $row['date'];
        if (isset($line_values_map[$row_date])) {
            $line_values_map[$row_date] = (int)$row['total_movement'];
        }
    }
    
    $line_labels = array_values($line_labels_map);
    $line_values = array_values($line_values_map);
    
    // Return JSON
    echo json_encode([
        'metrics' => [
            'total_value' => $total_value,
            'total_units' => $total_units,
        ],
        'health' => $health,
        'top_moving' => $top_moving,
        'recent_activity' => $recent_activity,
        'charts' => [
            'category' => [
                'labels' => array_keys($chart_categories),
                'data' => array_values($chart_categories)
            ],
            'trend' => $trend_data,
            'line' => [
                'labels' => $line_labels,
                'data' => $line_values
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
