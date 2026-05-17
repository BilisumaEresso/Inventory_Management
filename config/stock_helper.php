<?php
/**
 * Stock Movement Helper Functions
 * Calculate inventory based on stock movements
 */

/**
 * Get current stock for a product
 * Formula: SUM(IN) - SUM(OUT)
 */
function getCurrentStock($pdo, $product_id) {
    try {
        // First check if there are any movements
        $stmt = $pdo->prepare('
            SELECT
                COUNT(id) as movement_count,
                COALESCE(SUM(CASE WHEN movement_type = "IN" THEN quantity ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN movement_type = "OUT" THEN quantity ELSE 0 END), 0) as current_stock
            FROM stock_movements
            WHERE product_id = ?
        ');
        $stmt->execute([$product_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['movement_count'] > 0) {
            return (int)$result['current_stock'];
        }

        // Fallback: no movements exist, try to use old quantity field
        try {
            $stmt_fallback = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
            $stmt_fallback->execute([$product_id]);
            $fallback_result = $stmt_fallback->fetch();
            return $fallback_result ? (int)($fallback_result['quantity'] ?? 0) : 0;
        } catch (PDOException $e) {
            // Column might not exist if fully migrated
            return 0;
        }
    } catch (PDOException $e) {
        error_log('Stock Calculation Error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get stock status badge info
 */
function getStockStatus($quantity) {
    if ($quantity == 0) {
        return ['status' => 'Out of Stock', 'color' => '#d9534f', 'bg' => '#f2dede'];
    } elseif ($quantity < 5) {
        return ['status' => 'Low Stock', 'color' => '#ff9800', 'bg' => '#fff3cd'];
    } else {
        return ['status' => 'Available', 'color' => '#28a745', 'bg' => '#d4edda'];
    }
}

/**
 * Get all products with calculated stock
 */
function getProductsWithStock($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT p.id, p.name, COALESCE(c.name, p.category) as category, p.category_id, p.price, s.name as supplier_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.name');
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['stock'] = getCurrentStock($pdo, $product['id']);
        }

        return $products;
    } catch (PDOException $e) {
        error_log('Get Products Error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get product by ID with current stock
 */
function getProductWithStock($pdo, $product_id) {
    try {
        $stmt = $pdo->prepare('SELECT p.id, p.name, COALESCE(c.name, p.category) as category, p.category_id, p.price, p.sku, p.description FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['stock'] = getCurrentStock($pdo, $product_id);
        }

        return $product;
    } catch (PDOException $e) {
        error_log('Get Product Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get stock statistics for dashboard
 */
function getStockStatistics($pdo) {
    try {
        // Get all products with their calculated stock
        $products = getProductsWithStock($pdo);

        $total_products = count($products);
        $total_units = 0;
        $low_stock = 0;
        $out_of_stock = 0;
        $total_value = 0;

        foreach ($products as $product) {
            $total_units += $product['stock'];
            $total_value += ($product['stock'] * $product['price']);
            if ($product['stock'] == 0) {
                $out_of_stock++;
            } elseif ($product['stock'] < 5) {
                $low_stock++;
            }
        }

        return [
            'total_products' => $total_products,
            'total_units' => $total_units,
            'low_stock' => $low_stock,
            'out_of_stock' => $out_of_stock,
            'total_value' => $total_value,
            'products' => $products
        ];
    } catch (PDOException $e) {
        error_log('Stock Statistics Error: ' . $e->getMessage());
        return ['total_products' => 0, 'total_units' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'total_value' => 0, 'products' => []];
    }
}
?>
