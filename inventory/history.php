<?php
// Stock Movement History - Audit Trail
require_once '../middleware/auth.php';
require_once '../config/db.php';

$filter_product = trim($_GET['product_id'] ?? '');
$filter_type = trim($_GET['movement_type'] ?? '');
$all_products = [];
$movements = [];

try {
    // Get all products for filter dropdown
    $stmt_prod = $pdo->prepare('SELECT id, name FROM products ORDER BY name');
    $stmt_prod->execute();
    $all_products = $stmt_prod->fetchAll();

    // Get stock movements with optional filters
    $query = '
        SELECT
            sm.id,
            sm.created_at,
            p.name as product_name,
            p.category,
            sm.movement_type,
            sm.quantity,
            sm.reason,
            sm.reference_no,
            u.username as created_by
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        LEFT JOIN users u ON sm.created_by = u.id
        WHERE 1=1
    ';
    
    $params = [];
    if ($filter_product) {
        $query .= ' AND sm.product_id = ?';
        $params[] = $filter_product;
    }
    if ($filter_type) {
        $query .= ' AND sm.movement_type = ?';
        $params[] = $filter_type;
    }
    
    $query .= ' ORDER BY sm.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch Movements Error: ' . $e->getMessage());
}

$page_title = 'Inventory Audit Trail';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Top Header & Controls -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">📋 Stock Audit Trail</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Full chronological log of all stock movements and adjustments.</p>
    </div>
    <div>
        <a href="movement.php" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg"></i> New Adjustment
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold text-secondary" style="font-size: 13px;">Filter by Product</label>
            <select name="product_id" id="product_id" class="form-select border rounded-3 p-2.5">
                <option value="">All Products</option>
                <?php foreach ($all_products as $prod): ?>
                    <option value="<?php echo $prod['id']; ?>" <?php echo ($filter_product == $prod['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($prod['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label fw-semibold text-secondary" style="font-size: 13px;">Filter by Type</label>
            <select name="movement_type" id="movement_type" class="form-select border rounded-3 p-2.5">
                <option value="">All Types</option>
                <option value="IN" <?php echo ($filter_type === 'IN') ? 'selected' : ''; ?>>IN (Incoming Restock)</option>
                <option value="OUT" <?php echo ($filter_type === 'OUT') ? 'selected' : ''; ?>>OUT (Outgoing Sales/Loss)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3">Apply</button>
            <?php if ($filter_product || $filter_type): ?>
                <a href="history.php" class="btn btn-outline-danger py-2.5 rounded-3"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Movements Table Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <?php if (empty($movements)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px;"><i class="bi bi-clock-history fs-3"></i></div>
            <h6 class="fw-bold text-secondary mb-1">No transaction history</h6>
            <p class="text-muted mb-3" style="font-size: 12px;">No movements record matches this filter.</p>
            <a href="movement.php" class="btn btn-sm btn-success rounded-pill px-3">+ Add Movement</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Quantity</th>
                        <th>Reason</th>
                        <th>Ref Number</th>
                        <th class="text-end">Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, Y - H:i', strtotime($movement['created_at'])); ?></td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($movement['product_name']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($movement['category'] ?: 'Other'); ?></span></td>
                        <td class="text-center">
                            <?php if ($movement['movement_type'] === 'IN'): ?>
                                <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">📥 IN</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">📤 OUT</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><strong class="fs-6 text-dark"><?php echo (int)$movement['quantity']; ?></strong></td>
                        <td class="text-muted" style="font-size: 13px;"><?php echo htmlspecialchars($movement['reason'] ?: '—'); ?></td>
                        <td><code><?php echo htmlspecialchars($movement['reference_no'] ?: '—'); ?></code></td>
                        <td class="text-end fw-semibold text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($movement['created_by'] ?? 'System'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <span class="text-muted" style="font-size: 12px;">Showing <?php echo count($movements); ?> transactions recorded in history.</span>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/layout-end.php';
?>
