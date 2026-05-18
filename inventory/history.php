<?php
/**
 * Stock Movements History - Smart Inventory Management System (SIMS)
 * Restyled to match SIMS design system. No DB changes.
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';

$filter_product = trim($_GET['product_id']     ?? '');
$filter_type    = trim($_GET['movement_type']  ?? '');
$page           = max(1, (int)($_GET['page']   ?? 1));
$per_page       = 15;

$all_products = [];
$movements    = [];
$total_rows   = 0;
$total_pages  = 1;

try {
    $stmt_prod = $pdo->prepare('SELECT id, name FROM products ORDER BY name');
    $stmt_prod->execute();
    $all_products = $stmt_prod->fetchAll();

    // Base WHERE clause
    $where  = 'WHERE 1=1';
    $params = [];
    if ($filter_product) { $where .= ' AND sm.product_id = ?';    $params[] = $filter_product; }
    if ($filter_type)    { $where .= ' AND sm.movement_type = ?'; $params[] = $filter_type;    }

    // Count
    $cs = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm JOIN products p ON sm.product_id=p.id $where");
    $cs->execute($params);
    $total_rows  = (int)$cs->fetchColumn();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
    $offset      = ($page - 1) * $per_page;

    // Download CSV before page render
    if (isset($_GET['download'])) {
        $stmt_dl = $pdo->prepare("
            SELECT sm.id, sm.created_at, p.name AS product_name, p.category,
                   sm.movement_type, sm.quantity, sm.reason, sm.reference_no, u.username AS created_by
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN users u ON sm.created_by = u.id
            $where ORDER BY sm.created_at DESC
        ");
        $stmt_dl->execute($params);
        $rows = $stmt_dl->fetchAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="movements_' . date('Y-m-d') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', 'Date', 'Product', 'Category', 'Type', 'Quantity', 'Reason', 'Reference', 'Logged By']);
        foreach ($rows as $r) fputcsv($fp, [$r['id'], $r['created_at'], $r['product_name'], $r['category'], $r['movement_type'], $r['quantity'], $r['reason'], $r['reference_no'], $r['created_by'] ?? 'System']);
        fclose($fp);
        exit;
    }

    // Paginated data
    $stmt = $pdo->prepare("
        SELECT sm.id, sm.created_at, p.name AS product_name, p.category,
               sm.movement_type, sm.quantity, sm.reason, sm.reference_no, u.username AS created_by
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN users u ON sm.created_by = u.id
        $where
        ORDER BY sm.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $movements = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Movements History Error: ' . $e->getMessage());
}

$page_title  = 'Stock Movements';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<style>
    .mov-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        padding: 12px 16px;
        background: var(--card-bg);
        border-color: var(--navbar-border);
    }
    .mov-table td {
        font-size: 13.5px;
        padding: 13px 16px;
        border-color: var(--navbar-border);
        vertical-align: middle;
        color: var(--text-dark);
    }
    .mov-table tbody tr:hover { background: rgba(19,102,217,.025); }
    .badge-in  { background: rgba(85,179,138,.12); color: #55b38a; font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; }
    .badge-out { background: rgba(243,85,136,.10); color: #f35588; font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; }
    .page-btn {
        border: 1px solid var(--navbar-border);
        background: var(--card-bg);
        color: var(--text-dark);
        font-size: 13px;
        font-weight: 600;
        padding: 7px 20px;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s;
    }
    .page-btn:hover { background: rgba(19,102,217,.06); color: #1366d9; }
    .page-btn.disabled { opacity: .4; pointer-events: none; }
</style>

<!-- MAIN CARD -->
<div class="card border-0 shadow-sm rounded-4 p-0 overflow-hidden">

    <!-- Header row -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center px-4 pt-4 pb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark" style="font-family:'Inter',sans-serif;font-size:18px;">Stock Movements</h5>
            <p class="text-muted mb-0" style="font-size:12px;">Full chronological log of all inventory transactions.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="movement.php" class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-1" style="font-weight:600;font-size:13px;">
                <i class="bi bi-plus-lg"></i> New Movement
            </a>
            <button class="btn btn-outline-secondary px-3 py-2 rounded-3 d-flex align-items-center gap-1 bg-white border shadow-sm"
                    style="font-weight:600;font-size:13px;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-funnel"></i> Filters
            </button>
            <a href="?<?php echo http_build_query(array_filter(['product_id'=>$filter_product,'movement_type'=>$filter_type,'download'=>'1'])); ?>"
               class="btn btn-outline-secondary px-3 py-2 rounded-3 bg-white border shadow-sm" style="font-weight:600;font-size:13px;">
                Download all
            </a>
        </div>
    </div>

    <!-- Collapsible Filters -->
    <div class="collapse px-4 pb-3 <?php echo ($filter_product || $filter_type) ? 'show' : ''; ?>" id="filterCollapse">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold text-secondary" style="font-size:12px;">Filter by Product</label>
                <select name="product_id" class="form-select rounded-3" style="font-size:13px;">
                    <option value="">All Products</option>
                    <?php foreach ($all_products as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>" <?php echo ($filter_product == $prod['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prod['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary" style="font-size:12px;">Filter by Type</label>
                <select name="movement_type" class="form-select rounded-3" style="font-size:13px;">
                    <option value="">All Types</option>
                    <option value="IN"  <?php echo ($filter_type === 'IN')  ? 'selected' : ''; ?>>IN — Stock Intake</option>
                    <option value="OUT" <?php echo ($filter_type === 'OUT') ? 'selected' : ''; ?>>OUT — Stock Dispatch</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 rounded-3" style="font-size:13px;">Apply</button>
                <?php if ($filter_product || $filter_type): ?>
                    <a href="history.php" class="btn btn-outline-secondary rounded-3 d-flex align-items-center justify-content-center" style="width:42px;"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($movements)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-4 text-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 text-primary"
                 style="width:54px;height:54px;background:rgba(19,102,217,.08);">
                <i class="bi bi-clock-history fs-4"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">No transactions found</h6>
            <p class="text-muted mb-3" style="font-size:13px;">
                <?php echo ($filter_product || $filter_type) ? 'Try removing filters to see all records.' : 'No stock movements have been recorded yet.'; ?>
            </p>
            <a href="movement.php" class="btn btn-primary px-4 rounded-3" style="font-size:13px;">
                <i class="bi bi-plus-lg"></i> Add First Movement
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table mov-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Quantity</th>
                        <th>Reason</th>
                        <th>Reference No.</th>
                        <th>Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                    <tr>
                        <td class="text-muted" style="font-size:12.5px; white-space:nowrap;">
                            <?php echo date('d M Y', strtotime($m['created_at'])); ?><br>
                            <span style="font-size:11px;"><?php echo date('H:i', strtotime($m['created_at'])); ?></span>
                        </td>
                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($m['product_name']); ?></td>
                        <td>
                            <span class="badge rounded-pill px-3 py-1"
                                  style="background:rgba(19,102,217,.08);color:#1366d9;font-size:11px;font-weight:600;">
                                <?php echo htmlspecialchars($m['category'] ?: 'Other'); ?>
                            </span>
                        </td>
                        <td class="text-center" style="white-space: nowrap;">
                            <?php if ($m['movement_type'] === 'IN'): ?>
                                <span class="badge-in">↑ IN</span>
                            <?php else: ?>
                                <span class="badge-out">↓ OUT</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold text-dark"><?php echo (int)$m['quantity']; ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($m['reason'] ?: '—'); ?></td>
                        <td>
                            <?php if ($m['reference_no']): ?>
                                <span class="fw-semibold text-dark" style="font-size:12.5px;font-family:monospace;">
                                    <?php echo htmlspecialchars($m['reference_no']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($m['created_by'] ?? 'System'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center px-4 py-3"
             style="border-top: 1px solid var(--navbar-border);">
            <?php
            $q = array_filter(['product_id' => $filter_product, 'movement_type' => $filter_type]);
            $qs = $q ? '&' . http_build_query($q) : '';
            ?>
            <?php if ($page > 1): ?>
                <a class="page-btn" href="?page=<?php echo $page - 1 . $qs; ?>">Previous</a>
            <?php else: ?>
                <span class="page-btn disabled">Previous</span>
            <?php endif; ?>

            <span class="text-muted fw-semibold" style="font-size:13px;">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                &nbsp;·&nbsp; <?php echo number_format($total_rows); ?> records
            </span>

            <?php if ($page < $total_pages): ?>
                <a class="page-btn" href="?page=<?php echo $page + 1 . $qs; ?>">Next</a>
            <?php else: ?>
                <span class="page-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/layout-end.php'; ?>
