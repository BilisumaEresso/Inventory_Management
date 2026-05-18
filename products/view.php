<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

$product_id = $_GET['id'] ?? null;
$product = null;
$error_state = false;

if (!$product_id || !is_numeric($product_id)) {
    $error_state = true;
} else {
    try {
        // Fetch product with categories and suppliers joined
        $stmt = $pdo->prepare('
            SELECT 
                p.id, 
                p.name, 
                p.sku, 
                p.barcode, 
                p.price, 
                p.description, 
                p.created_at, 
                COALESCE(c.name, p.category) as category_name, 
                s.name as supplier_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN suppliers s ON p.supplier_id = s.id 
            WHERE p.id = ?
        ');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $error_state = true;
        } else {
            // Get Current Stock
            $current_stock = getCurrentStock($pdo, $product_id);
            $stock_status = getStockStatus($current_stock);

            // Stock Intelligence metrics
            $stmt_in = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE product_id = ? AND movement_type = 'IN'");
            $stmt_in->execute([$product_id]);
            $total_added = (int)$stmt_in->fetchColumn();

            $stmt_out = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE product_id = ? AND movement_type = 'OUT'");
            $stmt_out->execute([$product_id]);
            $total_removed = (int)$stmt_out->fetchColumn();

            // Fetch History sorted newest first
            $stmt_movements = $pdo->prepare('
                SELECT * 
                FROM stock_movements 
                WHERE product_id = ? 
                ORDER BY created_at DESC
            ');
            $stmt_movements->execute([$product_id]);
            $movements = $stmt_movements->fetchAll();

            // Prepare Chart.js Cumulative Stock Trend Data
            $stmt_chart = $pdo->prepare('
                SELECT movement_type, quantity, created_at 
                FROM stock_movements 
                WHERE product_id = ? 
                ORDER BY created_at ASC
            ');
            $stmt_chart->execute([$product_id]);
            $chart_movements = $stmt_chart->fetchAll();

            $chart_labels = [];
            $chart_data = [];
            $cumulative = 0;

            if (!empty($chart_movements)) {
                $chart_labels[] = 'Start';
                $chart_data[] = 0;

                foreach ($chart_movements as $m) {
                    $qty = (int)$m['quantity'];
                    if ($m['movement_type'] === 'IN') {
                        $cumulative += $qty;
                    } else {
                        $cumulative -= $qty;
                    }
                    $chart_labels[] = date('M d, H:i', strtotime($m['created_at']));
                    $chart_data[] = $cumulative;
                }
            } else {
                // Fallback quantity if no movements exist
                try {
                    $stmt_fallback = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
                    $stmt_fallback->execute([$product_id]);
                    $fallback_qty = (int)($stmt_fallback->fetchColumn() ?: 0);
                } catch (PDOException $e) {
                    $fallback_qty = 0;
                }
                $chart_labels[] = 'Initial';
                $chart_data[] = $fallback_qty;
            }

            $chart_labels_json = json_encode($chart_labels);
            $chart_data_json = json_encode($chart_data);
        }
    } catch (PDOException $e) {
        error_log('View Product Error: ' . $e->getMessage());
        $error_state = true;
    }
}

$page_title = !$error_state ? htmlspecialchars($product['name']) . ' Profile' : 'Product Not Found';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<?php if ($error_state): ?>
    <div class="card card-custom border-0 shadow-sm p-5 text-center my-5 mx-auto" style="max-width: 500px;">
        <div class="avatar shadow-sm bg-danger text-white mb-4 mx-auto" style="width: 60px; height: 60px;"><i class="bi bi-x-lg fs-3"></i></div>
        <h4 class="fw-bold text-danger mb-2">Product Not Found</h4>
        <p class="text-muted mb-4">The inventory catalog item you are looking for does not exist or has been removed.</p>
        <a href="list.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Back to Products</a>
    </div>
<?php else: ?>
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Top Welcome & Controls -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">📦 Product Profile Portal</h4>
            <p class="text-muted mb-0" style="font-size: 13px;">Detailed metadata profile and chronological transaction audits.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="../inventory/movement.php?product_id=<?php echo $product['id']; ?>" class="btn btn-success btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-arrow-down-up"></i> Manage Stock
            </a>
        </div>
    </div>

    <?php
    // Calculate initials for the Hero Avatar
    $name_parts = explode(' ', $product['name']);
    $initials = '';
    if (count($name_parts) >= 2) {
        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
    } else {
        $initials = strtoupper(substr($product['name'], 0, 2));
    }
    ?>

    <div class="row g-4">
        <!-- Left Column: Product Hero Card -->
        <div class="col-lg-4 col-12">
            <div class="card card-custom border-0 shadow-sm p-4 text-center mb-4">
                <!-- Circle Gradient Hero Initials Avatar -->
                <div class="d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" 
                     style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd 0%, #4f46e5 100%); color: white; font-weight: 700; font-size: 28px; letter-spacing: 1px;">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                
                <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($product['name']); ?></h4>
                <p class="text-muted mb-3" style="font-size: 13px;">Product Profile #<?php echo htmlspecialchars($product['id']); ?></p>
                
                <div class="mb-4">
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill" style="font-size: 12px; font-weight: 600;">
                        <?php echo htmlspecialchars($product['category_name'] ?: 'Other'); ?>
                    </span>
                </div>
                
                <!-- Hero Price -->
                <div class="bg-light rounded-4 p-3 mb-4">
                    <div class="text-muted uppercase fw-semibold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">UNIT PRICE</div>
                    <h3 class="fw-bold text-primary mb-0">ETB <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></h3>
                </div>
                
                <!-- Profile specs -->
                <div class="text-start d-flex flex-column gap-3 border-top pt-4">
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary fw-medium" style="font-size: 13px;">SKU Code</span>
                        <span class="text-dark fw-semibold" style="font-size: 13px;"><?php echo htmlspecialchars($product['sku'] ?: '—'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary fw-medium" style="font-size: 13px;">Barcode</span>
                        <span class="text-dark fw-semibold" style="font-size: 13px;"><?php echo htmlspecialchars($product['barcode'] ?: '—'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary fw-medium" style="font-size: 13px;">Active Supplier</span>
                        <span class="text-dark fw-semibold" style="font-size: 13px;"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($product['supplier_name'] ?? '—'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary fw-medium" style="font-size: 13px;">Status</span>
                        <span class="badge <?php echo ($current_stock == 0) ? 'bg-danger-subtle text-danger' : (($current_stock < 5) ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'); ?>" style="font-size: 10px;">
                            <?php echo $stock_status['status']; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary fw-medium" style="font-size: 13px;">Registered</span>
                        <span class="text-dark fw-semibold" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                    </div>
                </div>
                
                <?php if ($product['description']): ?>
                    <div class="text-start border-top mt-4 pt-4">
                        <span class="text-secondary fw-semibold d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">CATALOG DESCRIPTION</span>
                        <p class="text-dark mb-0" style="font-size: 13px; line-height: 1.5; text-align: justify;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Charts & Audits -->
        <div class="col-lg-8 col-12">
            <!-- Stock Intelligence Tiles -->
            <div class="row g-3 mb-4">
                <!-- Stock Added -->
                <div class="col-md-4 col-sm-6 col-12">
                    <div class="card card-custom border-0 shadow-sm p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-plus-circle-fill text-success fs-5"></i>
                            <span class="text-muted fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">STOCK ADDED (IN)</span>
                        </div>
                        <h2 class="fw-bold text-success mb-1">+<?php echo $total_added; ?></h2>
                        <span class="text-muted" style="font-size: 11px;">Cumulative restocks</span>
                    </div>
                </div>
                <!-- Stock Removed -->
                <div class="col-md-4 col-sm-6 col-12">
                    <div class="card card-custom border-0 shadow-sm p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-dash-circle-fill text-danger fs-5"></i>
                            <span class="text-muted fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">STOCK REMOVED (OUT)</span>
                        </div>
                        <h2 class="fw-bold text-danger mb-1">-<?php echo $total_removed; ?></h2>
                        <span class="text-muted" style="font-size: 11px;">Cumulative sales/losses</span>
                    </div>
                </div>
                <!-- Net Current Stock -->
                <div class="col-md-4 col-sm-6 col-12">
                    <div class="card card-custom border-0 shadow-sm p-4 h-100 bg-primary-subtle border-0">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-boxes text-primary fs-5"></i>
                            <span class="text-primary fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">NET CURRENT STOCK</span>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?php echo $current_stock; ?></h2>
                        <span class="text-muted" style="font-size: 11px;">Calculated on-hand stock</span>
                    </div>
                </div>
            </div>

            <!-- Movement Analytics Chart Card -->
            <div class="card card-custom border-0 shadow-sm p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-graph-up-arrow text-primary fs-5"></i>
                    <h5 class="fw-bold text-dark mb-0">Cumulative Stock Trend Line</h5>
                </div>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="productTrendChart"></canvas>
                </div>
            </div>

            <!-- Stock Movement History Card -->
            <div class="card card-custom border-0 shadow-sm p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-clock-history text-secondary fs-5"></i>
                    <h5 class="fw-bold text-dark mb-0">Stock Transaction Log</h5>
                </div>
                <?php if (empty($movements)): ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px; border-radius: 50%;"><i class="bi bi-journal-x fs-3"></i></div>
                        <h6 class="fw-bold text-secondary mb-1">No transaction history</h6>
                        <p class="text-muted mb-3" style="font-size: 12px;">This product has not recorded any stock movements yet.</p>
                        <a href="../inventory/movement.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">+ Adjust Stock</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th class="text-center">Type</th>
                                    <th>Quantity</th>
                                    <th>Reason / Cause</th>
                                    <th class="text-end">Ref Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, Y - H:i', strtotime($m['created_at'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($m['movement_type'] === 'IN'): ?>
                                            <span class="badge bg-success-subtle text-success">IN</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">OUT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="fs-6 text-dark"><?php echo (int)$m['quantity']; ?></strong></td>
                                    <td class="text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($m['reason'] ?: '—'); ?></td>
                                    <td class="text-end"><code><?php echo htmlspecialchars($m['reference_no'] ?: '—'); ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart rendering -->
    <script>
        const trendCtx = document.getElementById('productTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo $chart_labels_json; ?>,
                datasets: [{
                    label: 'Cumulative Stock',
                    data: <?php echo $chart_data_json; ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.06)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.15,
                    pointBackgroundColor: '#0d6efd',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { font: { family: 'Inter', size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { family: 'Inter', size: 10 } }
                    }
                }
            }
        });
    </script>
<?php endif; ?>

<?php
require_once '../includes/layout-end.php';
?>
