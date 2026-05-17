<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

$supplier_id = $_GET['id'] ?? null;
$supplier = null;
$products = [];

if (!$supplier_id || !is_numeric($supplier_id)) {
    header('Location: list.php');
    exit;
}

try {
    // Get supplier info
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        header('Location: list.php');
        exit;
    }

    // Get supplied products
    $stmt_prod = $pdo->prepare('SELECT id, name, category, price FROM products WHERE supplier_id = ? ORDER BY name');
    $stmt_prod->execute([$supplier_id]);
    $all_products = $stmt_prod->fetchAll();

    foreach ($all_products as $prod) {
        $prod['stock'] = getCurrentStock($pdo, $prod['id']);
        $products[] = $prod;
    }

} catch (PDOException $e) {
    error_log('View Supplier Error: ' . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = htmlspecialchars($supplier['name']) . ' Profile';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏢 Supplier Profile</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">View logical metrics and active catalog items sourced from this vendor.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-pencil-square"></i> Edit Supplier
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Profile Card -->
    <div class="col-lg-4 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold text-dark mb-4 border-bottom pb-2"><i class="bi bi-info-circle-fill"></i> Vendor Overview</h5>
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">COMPANY NAME</div>
                    <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($supplier['name']); ?></div>
                </div>
                <div>
                    <div class="text-muted uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">EMAIL CONTACT</div>
                    <?php if ($supplier['email']): ?>
                        <div class="fw-medium"><a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-primary"><?php echo htmlspecialchars($supplier['email']); ?></a></div>
                    <?php else: ?>
                        <div class="text-muted">Not provided</div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="text-muted uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PHONE NUMBER</div>
                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($supplier['phone'] ?: 'Not provided'); ?></div>
                </div>
                <div>
                    <div class="text-muted uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">REGISTERED ON</div>
                    <div class="fw-medium text-dark" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($supplier['created_at'])); ?></div>
                </div>
                <?php if (!empty($supplier['address'])): ?>
                    <div class="mt-2 pt-3 border-top">
                        <div class="text-muted uppercase fw-semibold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">PHYSICAL ADDRESS</div>
                        <p class="text-dark mb-0" style="font-size: 13px; line-height: 1.5; text-align: justify;"><?php echo nl2br(htmlspecialchars($supplier['address'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Products Table -->
    <div class="col-lg-8 col-12">
        <div class="card card-custom border-0 shadow-sm p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam"></i> Sourced Inventory Catalog</h5>
            <?php if (empty($products)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center py-5">
                    <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px;"><i class="bi bi-box fs-3"></i></div>
                    <h6 class="fw-bold text-secondary mb-1">No products sourced</h6>
                    <p class="text-muted mb-0" style="font-size: 12px;">This supplier has no active inventory items linked to them.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product):
                                $status = getStockStatus($product['stock']);
                            ?>
                            <tr>
                                <td>
                                    <a href="../products/view.php?id=<?php echo $product['id']; ?>" class="text-primary fw-bold">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($product['category'] ?: 'Other'); ?></span></td>
                                <td class="text-center fw-semibold text-dark"><?php echo htmlspecialchars($product['stock']); ?> units</td>
                                <td class="text-center">
                                    <span class="badge" style="background-color: <?php echo $status['bg']; ?>; color: <?php echo $status['color']; ?>; font-size: 11px; padding: 5px 10px;">
                                        <?php echo $status['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">$<?php echo number_format($product['price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../includes/layout-end.php';
?>
