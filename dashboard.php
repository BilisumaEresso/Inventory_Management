<?php
// Step 11: Dashboard - Only accessible after login
require_once 'middleware/auth.php';
require_once 'config/db.php';
require_once 'config/stock_helper.php';

$filter_category = trim($_GET['category'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

// Get statistics with calculated stock
$stats = getStockStatistics($pdo);
$total_products = $stats['total_products'];
$total_units = $stats['total_units'];
$low_stock = $stats['low_stock'];
$out_of_stock = $stats['out_of_stock'];
$total_value = $stats['total_value'];
$all_products = $stats['products'];

// Extract unique categories and filter products
$filtered_products = [];
$categories = [];
foreach ($all_products as $p) {
    if (!in_array($p['category'], $categories)) {
        $categories[] = $p['category'];
    }
    
    if ($filter_category && $p['category'] !== $filter_category) continue;
    if ($filter_status === 'low_stock' && $p['stock'] >= 5) continue;
    if ($filter_status === 'out_of_stock' && $p['stock'] > 0) continue;
    if ($filter_status === 'in_stock' && $p['stock'] < 5) continue;
    
    $filtered_products[] = $p;
}
sort($categories);

$recent_movements = [];
$total_suppliers = 0;
$trend_data = [];

try {
    // Get total suppliers
    $stmt_supp = $pdo->query('SELECT COUNT(*) FROM suppliers');
    $total_suppliers = $stmt_supp->fetchColumn();

    // Get recent 5 movements
    $stmt_mov = $pdo->query('
        SELECT sm.movement_type, sm.quantity, sm.created_at, p.name as product_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        ORDER BY sm.created_at DESC LIMIT 5
    ');
    $recent_movements = $stmt_mov->fetchAll();
    
    // Get chart data: Movement trend (Last 6 months)
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
    $trend_data = $stmt_trend->fetchAll();

} catch (PDOException $e) {
    error_log('Dashboard Error: ' . $e->getMessage());
}

// Prepare Category Chart Data
$chart_categories = [];
foreach ($filtered_products as $p) {
    if (!isset($chart_categories[$p['category']])) {
        $chart_categories[$p['category']] = 0;
    }
    $chart_categories[$p['category']] += $p['stock'];
}
$chart_labels_json = json_encode(array_keys($chart_categories));
$chart_data_json = json_encode(array_values($chart_categories));

// Prepare Trend Chart Data
$trend_labels = [];
$trend_in = [];
$trend_out = [];
if (!empty($trend_data)) {
    foreach ($trend_data as $row) {
        $trend_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $trend_in[] = (int)$row['total_in'];
        $trend_out[] = (int)$row['total_out'];
    }
}
$trend_labels_json = json_encode($trend_labels);
$trend_in_json = json_encode($trend_in);
$trend_out_json = json_encode($trend_out);

// Include premium layout start
$page_title = 'Dashboard';
$path_prefix = '';
require_once 'includes/layout-start.php';
?>

<!-- Top Welcome & Controls -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Here's an overview of your dynamic warehouse stock today.</p>
    </div>
    <div class="d-flex gap-2">
        <button id="btnRefreshDash" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-clockwise"></i> Refresh Stats
        </button>
        <a href="public/live-inventory.php" target="_blank" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-tv"></i> Full Signage Display
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="row g-4 mb-4">
    <!-- Total Inventory Value -->
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-semibold uppercase" style="font-size: 11px; letter-spacing: 0.5px;">TOTAL INVENTORY VALUE</span>
                <span class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-currency-dollar"></i></span>
            </div>
            <h2 class="fw-bold mb-1 text-dark" id="dashValValue">$<?php echo number_format($total_value, 2); ?></h2>
            <span class="text-muted" style="font-size: 11px;">Estimated warehouse value</span>
        </div>
    </div>
    
    <!-- Total Products -->
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-semibold uppercase" style="font-size: 11px; letter-spacing: 0.5px;">TOTAL PRODUCTS</span>
                <span class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-box-seam"></i></span>
            </div>
            <h2 class="fw-bold mb-1 text-dark" id="dashValProducts"><?php echo $total_products; ?></h2>
            <span class="text-muted" style="font-size: 11px;">Unique registered items</span>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="col-lg-3 col-sm-6 col-12">
        <a href="products/list.php?filter=low_stock" class="text-decoration-none h-100">
            <div class="card card-custom border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-warning fw-semibold uppercase" style="font-size: 11px; letter-spacing: 0.5px;">LOW STOCK PRODUCTS</span>
                    <span class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-exclamation-triangle-fill"></i></span>
                </div>
                <h2 class="fw-bold text-warning mb-1" id="dashValLowStock"><?php echo $low_stock; ?></h2>
                <span class="text-muted" style="font-size: 11px;">Quantity less than 5 units</span>
            </div>
        </a>
    </div>

    <!-- Out of Stock Products -->
    <div class="col-lg-3 col-sm-6 col-12">
        <a href="products/list.php?filter=out_of_stock" class="text-decoration-none h-100">
            <div class="card card-custom border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-danger fw-semibold uppercase" style="font-size: 11px; letter-spacing: 0.5px;">OUT OF STOCK PRODUCTS</span>
                    <span class="d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 38px; height: 38px; font-size: 18px;"><i class="bi bi-slash-circle-fill"></i></span>
                </div>
                <h2 class="fw-bold text-danger mb-1" id="dashValOutOfStock"><?php echo $out_of_stock; ?></h2>
                <span class="text-muted" style="font-size: 11px;">Quantity equals zero units</span>
            </div>
        </a>
    </div>
</div>

<!-- Search Insights Filter Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-funnel text-primary fs-5"></i>
        <h5 class="fw-bold text-dark mb-0">Search Insights Filters</h5>
    </div>
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label text-secondary">Filter by Category</label>
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label text-secondary">Filter by Stock Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="in_stock" <?php echo $filter_status === 'in_stock' ? 'selected' : ''; ?>>In Stock (5+)</option>
                <option value="low_stock" <?php echo $filter_status === 'low_stock' ? 'selected' : ''; ?>>Low Stock (&lt; 5)</option>
                <option value="out_of_stock" <?php echo $filter_status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock (0)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Apply</button>
            <?php if ($filter_category || $filter_status): ?>
                <a href="dashboard.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 42px; border-radius: 30px;"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4" id="analytics-section">
    <!-- Left: Inventory Trend chart -->
    <div class="col-lg-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-bar-chart-fill text-primary"></i>
                <h5 class="fw-bold text-dark mb-0">Monthly Stock Movement Trend</h5>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Right: Category Distribution chart -->
    <div class="col-lg-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-pie-chart-fill text-indigo" style="color: #4f46e5;"></i>
                <h5 class="fw-bold text-dark mb-0">Inventory by Category</h5>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Insights row -->
<div class="row g-4 mb-4">
    <!-- Left: Recent Stock Activity -->
    <div class="col-lg-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-activity text-primary fs-5"></i>
                <h5 class="fw-bold text-dark mb-0">Recent Stock Activity</h5>
            </div>
            <?php if (!empty($recent_movements)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Product Name</th>
                            <th class="text-center">Action</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody id="dashRecentMovements">
                        <?php foreach ($recent_movements as $mov): ?>
                        <tr>
                            <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, H:i', strtotime($mov['created_at'])); ?></td>
                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($mov['product_name']); ?></td>
                            <td class="text-center">
                                <?php if ($mov['movement_type'] === 'IN'): ?>
                                    <span class="badge bg-success-subtle text-success">IN</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">OUT</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><strong><?php echo (int)$mov['quantity']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-end">
                <a href="inventory/history.php" class="text-primary fw-semibold text-decoration-none" style="font-size: 13px;">View full transaction history →</a>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column align-items-center justify-content-center py-5">
                <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px;"><i class="bi bi-clock-history fs-3"></i></div>
                <h6 class="fw-bold text-secondary mb-1">No transaction history</h6>
                <p class="text-muted mb-0" style="font-size: 12px;">Movements will show up here.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Low Stock Products -->
    <div class="col-lg-6 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-graph-down text-danger fs-5"></i>
                <h5 class="fw-bold text-dark mb-0">Low Stock Products</h5>
            </div>
            <?php
            $low_stock_products = [];
            foreach ($filtered_products as $p) {
                if ($p['stock'] < 5) {
                    $low_stock_products[] = $p;
                    if (count($low_stock_products) >= 5) break;
                }
            }
            ?>
            <?php if (!empty($low_stock_products)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th class="text-center">Stock</th>
                            <th>Supplier</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product):
                            $status = getStockStatus($product['stock']);
                        ?>
                        <tr>
                            <td>
                                <a href="products/view.php?id=<?php echo $product['id']; ?>" class="fw-semibold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                                <div class="mt-1">
                                    <span class="badge <?php echo ($product['stock'] == 0) ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning'; ?>">
                                        <?php echo $status['status']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center"><strong class="fs-6 text-dark"><?php echo htmlspecialchars($product['stock']); ?></strong></td>
                            <td class="text-muted" style="font-size: 13px;"><?php echo htmlspecialchars($product['supplier_name'] ?? '—'); ?></td>
                            <td class="text-end">
                                <a href="inventory/movement.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">Restock</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-end">
                <a href="products/list.php?filter=low_stock" class="text-primary fw-semibold text-decoration-none" style="font-size: 13px;">View all low stock →</a>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column align-items-center justify-content-center py-5">
                <div class="avatar shadow-sm bg-success text-white mb-3" style="width: 50px; height: 50px; border-radius: 50%;"><i class="bi bi-check-lg fs-3"></i></div>
                <h6 class="fw-bold text-success mb-1">Stock levels healthy!</h6>
                <p class="text-muted mb-0" style="font-size: 12px;">All products are well stocked.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Action Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-rocket-takeoff-fill"></i> Fast Track Workflows</h5>
    <div class="d-flex flex-wrap gap-2">
        <a href="products/add.php" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-plus-lg"></i> Add Product</a>
        <a href="products/list.php" class="btn btn-outline-dark rounded-pill px-4"><i class="bi bi-box-seam"></i> View Inventory</a>
        <a href="inventory/history.php" class="btn btn-outline-dark rounded-pill px-4"><i class="bi bi-clock-history"></i> Inventory Logs</a>
        <a href="suppliers/list.php" class="btn btn-outline-dark rounded-pill px-4"><i class="bi bi-building"></i> Suppliers List</a>
        <a href="categories/list.php" class="btn btn-outline-dark rounded-pill px-4"><i class="bi bi-tags"></i> Category Manager</a>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart JS Rendering -->
<script>
    // Category Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                data: <?php echo $chart_data_json; ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6610f2', '#fd7e14', '#e83e8c', '#20c997']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'right',
                    labels: { boxWidth: 12, font: { family: 'Inter', size: 11 } }
                } 
            }
        }
    });

    // Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $trend_labels_json; ?>,
            datasets: [
                {
                    label: 'IN (Restock)',
                    data: <?php echo $trend_in_json; ?>,
                    backgroundColor: '#198754',
                    borderRadius: 4
                },
                {
                    label: 'OUT (Sales/Loss)',
                    data: <?php echo $trend_out_json; ?>,
                    backgroundColor: '#dc3545',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: { family: 'Inter', size: 11 } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
</script>

<!-- AJAX Live Updates & Polling -->
<script>
    const formatDashCurrency = (val) => '$' + Number(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let lastStatsHash = '';
    let lastMovementsHash = '';

    async function refreshDashboardStats() {
        try {
            const response = await fetch('api/dashboard-stats.php');
            const textData = await response.text();
            
            if (textData === lastStatsHash) return;
            lastStatsHash = textData;
            
            const data = JSON.parse(textData);
            if (data.error) return;

            const updates = {
                'dashValValue': formatDashCurrency(data.total_value),
                'dashValProducts': data.total_products,
                'dashValUnits': data.total_units,
                'dashValLowStock': data.low_stock,
                'dashValOutOfStock': data.out_of_stock,
                'dashValSuppliers': data.total_suppliers
            };

            for (const [id, value] of Object.entries(updates)) {
                const el = document.getElementById(id);
                if (el && el.textContent != value) {
                    el.textContent = value;
                    el.style.transition = 'background-color 0.3s';
                    el.style.backgroundColor = 'rgba(25, 135, 84, 0.15)';
                    setTimeout(() => { el.style.backgroundColor = 'transparent'; }, 1500);
                }
            }
        } catch (e) {
            console.error('[Dashboard] Stats refresh error:', e);
        }
    }

    async function refreshRecentMovements() {
        try {
            const response = await fetch('api/recent-movements.php');
            const textData = await response.text();
            
            if (textData === lastMovementsHash) return;
            lastMovementsHash = textData;

            const movements = JSON.parse(textData);
            const tbody = document.getElementById('dashRecentMovements');

            if (!tbody || movements.error) return;

            if (movements.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state">No stock movements recorded yet.</td></tr>';
                return;
            }

            tbody.innerHTML = '';
            movements.forEach(mov => {
                const dateStr = new Date(mov.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' +
                                new Date(mov.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                const isIn = mov.movement_type === 'IN';
                const badge = isIn
                    ? '<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill" style="font-size: 11px;">IN</span>'
                    : '<span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill" style="font-size: 11px;">OUT</span>';

                tbody.innerHTML += `
                    <tr>
                        <td class="text-muted" style="font-size: 13px;">${dateStr}</td>
                        <td class="fw-bold text-dark">${mov.product_name}</td>
                        <td class="text-center">${badge}</td>
                        <td class="text-end"><strong>${parseInt(mov.quantity)}</strong></td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error('[Dashboard] Movements refresh error:', e);
        }
    }

    function refreshDashboard() {
        refreshDashboardStats();
        refreshRecentMovements();
    }

    // Manual refresh action
    document.getElementById('btnRefreshDash').addEventListener('click', (e) => {
        const btn = e.target;
        const origText = btn.innerHTML;
        btn.innerHTML = '⏳ Loading...';
        refreshDashboard();
        setTimeout(() => { btn.innerHTML = origText; }, 500);
    });

    // Smart 5s polling
    setInterval(refreshDashboard, 5000);
</script>

<?php
// Include layout end
require_once 'includes/layout-end.php';
?>