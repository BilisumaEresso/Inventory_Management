<?php
/**
 * Reports Page - Smart Inventory Management System (SIMS)
 * Matches Figma design: Overview, Best Selling Category, Profit & Revenue Chart, Best Selling Products
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

// ─────────────────────────────────────────────
// 1. OVERVIEW METRICS
// ─────────────────────────────────────────────
$total_profit       = 0;
$total_revenue      = 0;
$total_sales_val    = 0;
$net_purchase_val   = 0;
$net_sales_val      = 0;
$mom_profit         = 0;
$yoy_profit         = 0;

// CHART DATA
$chart_months       = [];
$chart_revenue      = [];
$chart_profit       = [];

// BEST SELLING CATEGORY
$best_categories    = [];

// BEST SELLING PRODUCTS
$best_products      = [];

try {
    // ── Totals ──────────────────────────────────────────────────────────────
    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN sm.movement_type = 'IN'  THEN sm.quantity * p.price ELSE 0 END), 0) AS in_val,
            COALESCE(SUM(CASE WHEN sm.movement_type = 'OUT' THEN sm.quantity * p.price ELSE 0 END), 0) AS out_val
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
    ");
    $totals = $stmt->fetch();
    $net_purchase_val = (float)$totals['in_val'];
    $net_sales_val    = (float)$totals['out_val'];
    $total_sales_val  = $net_sales_val;
    $total_revenue    = $net_sales_val * 1.25;          // 25 % markup
    $total_profit     = $total_revenue - $net_purchase_val;

    // ── Month-on-Month Profit ────────────────────────────────────────────────
    $stmt_mom = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity*p.price ELSE 0 END),0)*1.25
            - COALESCE(SUM(CASE WHEN sm.movement_type='IN'  THEN sm.quantity*p.price ELSE 0 END),0) AS profit
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        WHERE MONTH(sm.created_at)=MONTH(CURDATE()) AND YEAR(sm.created_at)=YEAR(CURDATE())
    ");
    $mom_profit = (float)($stmt_mom->fetchColumn() ?? 0);

    // ── Year-on-Year Profit ──────────────────────────────────────────────────
    $stmt_yoy = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity*p.price ELSE 0 END),0)*1.25
            - COALESCE(SUM(CASE WHEN sm.movement_type='IN'  THEN sm.quantity*p.price ELSE 0 END),0) AS profit
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        WHERE YEAR(sm.created_at)=YEAR(CURDATE())
    ");
    $yoy_profit = (float)($stmt_yoy->fetchColumn() ?? 0);

    // ── Chart: Last 7 months Revenue & Profit ───────────────────────────────
    $stmt_chart = $pdo->query("
        SELECT
            DATE_FORMAT(sm.created_at,'%Y-%m') AS mo,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity*p.price ELSE 0 END),0) AS sales,
            COALESCE(SUM(CASE WHEN sm.movement_type='IN'  THEN sm.quantity*p.price ELSE 0 END),0) AS cost
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        GROUP BY mo ORDER BY mo ASC
    ");
    $raw_chart = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);
    $raw_map   = [];
    foreach ($raw_chart as $r) { $raw_map[$r['mo']] = $r; }

    for ($i = 6; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-$i months"));
        $lbl = date('M', strtotime("-$i months"));
        $chart_months[] = $lbl;
        $s = isset($raw_map[$key]) ? (float)$raw_map[$key]['sales'] : 0;
        $c = isset($raw_map[$key]) ? (float)$raw_map[$key]['cost']  : 0;
        $chart_revenue[] = round($s * 1.25, 2);
        $chart_profit[]  = round(($s * 1.25) - $c, 2);
    }

    // ── Best Selling Categories ──────────────────────────────────────────────
    // Turn Over  = SUM(OUT qty * price) per category
    // Increase % = (this_month - last_month) / last_month * 100
    $stmt_cat = $pdo->query("
        SELECT
            COALESCE(c.name, p.category, 'Other') AS cat_name,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity * p.price ELSE 0 END),0) AS turnover,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' AND MONTH(sm.created_at)=MONTH(CURDATE()) AND YEAR(sm.created_at)=YEAR(CURDATE()) THEN sm.quantity*p.price ELSE 0 END),0) AS this_month,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' AND MONTH(sm.created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(sm.created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) THEN sm.quantity*p.price ELSE 0 END),0) AS last_month
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        GROUP BY cat_name
        ORDER BY turnover DESC
        LIMIT 5
    ");
    $best_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($best_categories as &$cat) {
        $lm = (float)$cat['last_month'];
        $tm = (float)$cat['this_month'];
        $cat['increase_pct'] = $lm > 0 ? round((($tm - $lm) / $lm) * 100, 1) : ($tm > 0 ? 100 : 0);
    }
    unset($cat);

    // ── Best Selling Products ────────────────────────────────────────────────
    $stmt_prod = $pdo->query("
        SELECT
            p.id,
            p.name,
            COALESCE(c.name, p.category, 'Other') AS category,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity ELSE 0 END),0) AS sold_qty,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' THEN sm.quantity*p.price ELSE 0 END),0) AS turnover,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' AND MONTH(sm.created_at)=MONTH(CURDATE()) AND YEAR(sm.created_at)=YEAR(CURDATE()) THEN sm.quantity*p.price ELSE 0 END),0) AS this_month,
            COALESCE(SUM(CASE WHEN sm.movement_type='OUT' AND MONTH(sm.created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(sm.created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) THEN sm.quantity*p.price ELSE 0 END),0) AS last_month
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE sm.movement_type = 'OUT'
        GROUP BY p.id
        ORDER BY sold_qty DESC
        LIMIT 8
    ");
    $best_products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);
    foreach ($best_products as &$prod) {
        $prod['stock']        = getCurrentStock($pdo, $prod['id']);
        $lm = (float)$prod['last_month'];
        $tm = (float)$prod['this_month'];
        $prod['increase_pct'] = $lm > 0 ? round((($tm - $lm) / $lm) * 100, 1) : ($tm > 0 ? 100 : 0);
    }
    unset($prod);

} catch (PDOException $e) {
    error_log('Reports Error: ' . $e->getMessage());
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function etb($val) { return 'ETB ' . number_format((float)$val); }
function pct($v) {
    $color = $v >= 0 ? '#55b38a' : '#f35588';
    $sign  = $v >= 0 ? '+' : '';
    return "<span style='color:{$color}; font-weight:700;'>{$sign}{$v}%</span>";
}

$page_title  = 'Reports';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<style>
    .report-card {
        background: var(--card-bg);
        border: 1px solid var(--navbar-border);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 1px 6px rgba(0,0,0,.05);
    }
    .ov-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        margin-top: 4px;
    }
    .ov-value {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-dark);
        font-family: 'Outfit', sans-serif;
        line-height: 1.1;
    }
    .ov-value.orange { color: #e28743; }
    .ov-value.purple { color: #845ec2; }
    .ov-divider {
        border-right: 1px solid var(--navbar-border);
    }
    .cat-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        padding: 10px 0;
        border: none;
        background: transparent;
    }
    .cat-table td {
        font-size: 13.5px;
        padding: 11px 0;
        border-color: var(--navbar-border);
        vertical-align: middle;
        color: var(--text-dark);
    }
    .prod-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        padding: 12px 16px;
        background: var(--card-bg);
        border-color: var(--navbar-border);
    }
    .prod-table td {
        font-size: 13.5px;
        padding: 13px 16px;
        border-color: var(--navbar-border);
        vertical-align: middle;
        color: var(--text-dark);
    }
    .prod-table tbody tr:hover { background: rgba(19,102,217,.025); }
    .section-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--text-dark);
        font-family: 'Inter', sans-serif;
    }
    .chart-container { position: relative; height: 300px; width: 100%; }
</style>

<!-- ═══════════════════════════════════════════════════════════
     ROW 1 : Overview (left) + Best Selling Category (right)
═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- OVERVIEW CARD -->
    <div class="col-lg-7 col-12">
        <div class="report-card h-100">
            <h5 class="section-title mb-4">Overview</h5>

            <!-- Row 1: Total Profit | Revenue | Sales -->
            <div class="row g-0 mb-4 pb-3" style="border-bottom: 1px solid var(--navbar-border);">
                <div class="col-4 ov-divider pe-3">
                    <div class="ov-value"><?php echo etb($total_profit); ?></div>
                    <div class="ov-label">Total Profit</div>
                </div>
                <div class="col-4 ov-divider px-3">
                    <div class="ov-value orange"><?php echo etb($total_revenue); ?></div>
                    <div class="ov-label">Revenue</div>
                </div>
                <div class="col-4 ps-3">
                    <div class="ov-value purple"><?php echo etb($total_sales_val); ?></div>
                    <div class="ov-label">Sales</div>
                </div>
            </div>

            <!-- Row 2: Net purchase value | Net sales value | MoM Profit | YoY Profit -->
            <div class="row g-0">
                <div class="col-3 ov-divider pe-2">
                    <div class="ov-value" style="font-size:16px;"><?php echo etb($net_purchase_val); ?></div>
                    <div class="ov-label">Net purchase value</div>
                </div>
                <div class="col-3 ov-divider px-2">
                    <div class="ov-value" style="font-size:16px;"><?php echo etb($net_sales_val); ?></div>
                    <div class="ov-label">Net sales value</div>
                </div>
                <div class="col-3 ov-divider px-2">
                    <div class="ov-value" style="font-size:16px;"><?php echo etb($mom_profit); ?></div>
                    <div class="ov-label">MoM Profit</div>
                </div>
                <div class="col-3 ps-2">
                    <div class="ov-value" style="font-size:16px;"><?php echo etb($yoy_profit); ?></div>
                    <div class="ov-label">YoY Profit</div>
                </div>
            </div>

        </div>
    </div>

    <!-- BEST SELLING CATEGORY CARD -->
    <div class="col-lg-5 col-12">
        <div class="report-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">Best selling category</h5>
                <a href="../categories/list.php" class="text-primary fw-bold text-decoration-none" style="font-size:13px;">See All</a>
            </div>

            <?php if (empty($best_categories)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-2 text-primary" style="width:44px;height:44px;background:rgba(19,102,217,.08);">
                        <i class="bi bi-tags fs-5"></i>
                    </div>
                    <small class="text-muted">No sales data yet. Add stock movements to see categories.</small>
                </div>
            <?php else: ?>
                <table class="table cat-table mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Turn Over</th>
                            <th class="text-end">Increase By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($best_categories as $cat): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo htmlspecialchars($cat['cat_name']); ?></td>
                            <td class="text-end fw-bold"><?php echo etb($cat['turnover']); ?></td>
                            <td class="text-end"><?php echo pct($cat['increase_pct']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     ROW 2 : Profit & Revenue Chart (full width)
═══════════════════════════════════════════════════════════════ -->
<div class="report-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="section-title mb-0">Profit &amp; Revenue</h5>
        <button class="btn btn-sm btn-outline-secondary px-3 rounded-3 d-flex align-items-center gap-2" style="font-size:12.5px;font-weight:500;">
            <i class="bi bi-calendar3"></i> Weekly
        </button>
    </div>
    <div class="chart-container">
        <canvas id="profitRevenueChart"></canvas>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     ROW 3 : Best Selling Products Table (full width)
═══════════════════════════════════════════════════════════════ -->
<div class="report-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="section-title mb-0">Best selling product</h5>
        <a href="../products/list.php" class="text-primary fw-bold text-decoration-none" style="font-size:13px;">See All</a>
    </div>

    <?php if (empty($best_products)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 text-primary" style="width:54px;height:54px;background:rgba(19,102,217,.08);">
                <i class="bi bi-graph-up fs-4"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">No Sales Data Yet</h6>
            <p class="text-muted mb-0" style="font-size:13px;">Best-selling products appear here once stock movements (OUT) are recorded.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table prod-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Product ID</th>
                        <th>Category</th>
                        <th>Remaining Quantity</th>
                        <th class="text-end">Turn Over</th>
                        <th class="text-end">Increase By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($best_products as $prod): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td class="text-muted">#<?php echo str_pad($prod['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <span class="badge rounded-pill px-3 py-1" style="background:rgba(19,102,217,.08);color:#1366d9;font-size:11px;font-weight:600;">
                                <?php echo htmlspecialchars($prod['category']); ?>
                            </span>
                        </td>
                        <td class="text-dark fw-semibold"><?php echo (int)$prod['stock']; ?> Units</td>
                        <td class="text-end fw-bold"><?php echo etb($prod['turnover']); ?></td>
                        <td class="text-end"><?php echo pct($prod['increase_pct']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const ctx = document.getElementById('profitRevenueChart').getContext('2d');

    // Soft gradient fills
    const gradRevenue = ctx.createLinearGradient(0, 0, 0, 280);
    gradRevenue.addColorStop(0, 'rgba(59, 130, 246, 0.15)');
    gradRevenue.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    const gradProfit = ctx.createLinearGradient(0, 0, 0, 280);
    gradProfit.addColorStop(0, 'rgba(230, 195, 140, 0.25)');
    gradProfit.addColorStop(1, 'rgba(230, 195, 140, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_months); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradRevenue,
                    borderWidth: 2.5,
                    tension: 0.45,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6',
                    pointHoverRadius: 7
                },
                {
                    label: 'Profit',
                    data: <?php echo json_encode($chart_profit); ?>,
                    borderColor: '#d4a96a',
                    backgroundColor: gradProfit,
                    borderWidth: 2,
                    tension: 0.45,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#d4a96a',
                    pointHoverRadius: 6,
                    borderDash: []
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { family: 'Inter', size: 12, weight: '500' },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    titleColor: '#1e293b',
                    bodyColor: '#64748b',
                    padding: 14,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ETB ' + Number(ctx.parsed.y).toLocaleString('en-US');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 12 }, color: '#94a3b8' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        callback: function(v) {
                            if (v >= 1000000) return 'ETB ' + (v/1000000).toFixed(1) + 'M';
                            if (v >= 1000) return 'ETB ' + (v/1000).toFixed(0) + 'K';
                            return 'ETB ' + v;
                        }
                    }
                }
            }
        }
    });
})();
</script>

<?php require_once '../includes/layout-end.php'; ?>
