<?php
// Step 17, 20-22: List all products with search and stock status
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

$search = trim($_GET['search'] ?? '');
$selected_category_id = trim($_GET['category_id'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$products = [];
$categories = [];

try {
    // Fetch all categories for filter dropdown
    $stmt_cats = $pdo->query('SELECT id, name FROM categories ORDER BY name');
    $categories = $stmt_cats->fetchAll();

    // Step 21: Build JOIN query dynamically
    $query = '
        SELECT 
            p.id, 
            p.name, 
            COALESCE(c.name, p.category) as category_name, 
            p.category_id, 
            p.price, 
            p.created_at, 
            s.name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id
    ';
    
    $conditions = [];
    $params = [];
    
    if ($search) {
        $conditions[] = 'p.name LIKE ?';
        $params[] = '%' . $search . '%';
    }
    
    if ($selected_category_id !== '') {
        $conditions[] = 'p.category_id = ?';
        $params[] = $selected_category_id;
    }
    
    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    
    $query .= ' ORDER BY p.created_at DESC';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_products = $stmt->fetchAll();
    
    foreach ($all_products as $product) {
        $product['stock'] = getCurrentStock($pdo, $product['id']);
        
        if ($filter === 'low_stock' && $product['stock'] >= 5) {
            continue;
        }
        if ($filter === 'out_of_stock' && $product['stock'] > 0) {
            continue;
        }
        
        $products[] = $product;
    }
} catch (PDOException $e) {
    error_log('List Products Error: ' . $e->getMessage());
}

$page_title = 'Products Inventory';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Top Welcome & Controls -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">📦 Products Inventory</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Manage and monitor registered dynamic stock items.</p>
    </div>
    <div>
        <a href="add.php" class="btn btn-primary btn-sm px-4 py-2 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg"></i> Add Product
        </a>
    </div>
</div>

<style>
    /* Sticky Table Header and Custom Spacings */
    .table-responsive-sticky {
        max-height: 650px;
        overflow-y: auto;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table-responsive-sticky thead th {
        position: sticky;
        top: 0;
        background-color: #f8fafc;
        z-index: 10;
        box-shadow: inset 0 -2px 0 #e2e8f0;
    }
</style>

<!-- Search & Filters Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-funnel text-primary fs-5"></i>
        <h5 class="fw-bold text-dark mb-0">Search & Filter Catalog</h5>
    </div>
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-6 col-12">
            <label class="form-label text-secondary">Search Products</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
            <label class="form-label text-secondary">Category</label>
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($selected_category_id == $cat['id'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
            <label class="form-label text-secondary">Stock Status</label>
            <select name="filter" class="form-select">
                <option value="">All Statuses</option>
                <option value="low_stock" <?php echo ($filter === 'low_stock' ? 'selected' : ''); ?>>Low Stock (&lt; 5)</option>
                <option value="out_of_stock" <?php echo ($filter === 'out_of_stock' ? 'selected' : ''); ?>>Out of Stock (= 0)</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
            <?php if ($search || $selected_category_id !== '' || $filter): ?>
                <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 42px; border-radius: 30px;"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products Table Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <?php if (empty($products)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px; border-radius: 50%;"><i class="bi bi-box fs-3"></i></div>
            <h6 class="fw-bold text-secondary mb-1">No products found</h6>
            <p class="text-muted mb-0" style="font-size: 12px;">Try adjusting filters or add a new product.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Product Details</th>
                        <th>Supplier</th>
                        <th class="text-center" style="width: 130px;">Stock</th>
                        <th style="width: 110px;">Price</th>
                        <th class="text-end" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product):
                        $status = getStockStatus($product['stock']);
                        $is_low = $product['stock'] < 5 && $product['stock'] > 0;
                        $is_out = $product['stock'] == 0;
                    ?>
                    <tr>
                        <td class="text-muted fw-medium">#<?php echo htmlspecialchars($product['id']); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $product['id']; ?>" class="fw-bold text-dark text-decoration-none d-block mb-0.5">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                            <small class="text-secondary fw-semibold" style="font-size: 11px;">
                                <span class="bg-light px-2 py-0.5 rounded border text-muted">
                                    <?php echo htmlspecialchars($product['category_name'] ?? $product['category'] ?? 'Other'); ?>
                                </span>
                            </small>
                        </td>
                        <td class="text-muted" style="font-size: 13px;">
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-building me-1 text-secondary"></i>
                                <?php echo htmlspecialchars($product['supplier_name'] ?? '—'); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold text-dark fs-6 mb-1"><?php echo htmlspecialchars($product['stock']); ?></div>
                            <span class="badge <?php echo $is_out ? 'bg-danger-subtle text-danger' : ($is_low ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'); ?>" style="font-size: 9px; padding: 2px 6px;">
                                <?php echo $status['status']; ?>
                            </span>
                        </td>
                        <td class="fw-bold text-dark">$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px; font-weight: 500;">
                                    <i class="bi bi-three-dots"></i> Options
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="font-size: 13px; min-width: 170px;">
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-primary d-flex align-items-center gap-2" href="view.php?id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-person-badge"></i> Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-success d-flex align-items-center gap-2" href="../inventory/movement.php?product_id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-plus-minus"></i> Stock
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1 border-light"></li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-dark d-flex align-items-center gap-2" href="edit.php?id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-danger d-flex align-items-center gap-2" href="#" onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $product['id']; ?>', 'Are you sure you want to delete this product? All historical transactions remain logged, but this catalog item will be deleted.'); return false;">
                                            <i class="bi bi-trash-fill"></i> Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/layout-end.php';
?>
