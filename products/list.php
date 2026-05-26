<?php
/**
 * Products Inventory Listing — Professional Upgrade
 * (Fix: removed p.updated_at to avoid missing column error)
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

$search = trim($_GET['search'] ?? '');
$selected_category_id = trim($_GET['category_id'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$products = [];
$categories = [];

// Stock threshold for low stock warning
$low_stock_threshold = 15;

try {
    // Fetch all categories for filter dropdown
    $stmt_cats = $pdo->query('SELECT id, name FROM categories ORDER BY name');
    $categories = $stmt_cats->fetchAll();

    // Build base query (no updated_at to avoid missing column errors)
    $query = '
        SELECT
            p.id,
            p.name,
            p.sku,
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
        $conditions[] = '(p.name LIKE ? OR p.sku LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if ($selected_category_id !== '') {
        $conditions[] = 'p.category_id = ?';
        $params[] = $selected_category_id;
    }

    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    // Sort handling
    switch ($sort) {
        case 'name_asc':
            $query .= ' ORDER BY p.name ASC';
            break;
        case 'name_desc':
            $query .= ' ORDER BY p.name DESC';
            break;
        case 'price_asc':
            $query .= ' ORDER BY p.price ASC';
            break;
        case 'price_desc':
            $query .= ' ORDER BY p.price DESC';
            break;
        case 'oldest':
            $query .= ' ORDER BY p.created_at ASC';
            break;
        case 'newest':
        default:
            $query .= ' ORDER BY p.created_at DESC';
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_products = $stmt->fetchAll();

    $total_stock_value = 0;
    $total_out_of_stock = 0;
    $total_low_stock = 0;
    $total_in_stock = 0;

    foreach ($all_products as $product) {
        $product['stock'] = getCurrentStock($pdo, $product['id']);
        $total_stock_value += ($product['stock'] * $product['price']);

        if ($product['stock'] == 0) {
            $total_out_of_stock++;
        } elseif ($product['stock'] < $low_stock_threshold) {
            $total_low_stock++;
        } else {
            $total_in_stock++;
        }

        // Apply stock status filter
        if ($filter === 'low_stock' && $product['stock'] >= $low_stock_threshold) {
            continue;
        }
        if ($filter === 'out_of_stock' && $product['stock'] > 0) {
            continue;
        }
        if ($filter === 'in_stock' && $product['stock'] <= 0) {
            continue;
        }

        $products[] = $product;
    }

    // After filtering, re-sort by stock if requested
    if ($sort === 'stock_asc') {
        usort($products, function($a, $b) { return $a['stock'] - $b['stock']; });
    } elseif ($sort === 'stock_desc') {
        usort($products, function($a, $b) { return $b['stock'] - $a['stock']; });
    }

    $total_products_count = count($all_products);
    $filtered_count = count($products);
    $total_categories_count = count($categories);

    // Get count of top-selling products (those with OUT movements)
    $stmt_top_count = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM stock_movements WHERE movement_type = 'OUT'");
    $total_top_selling = (int)$stmt_top_count->fetchColumn();

} catch (PDOException $e) {
    error_log('List Products Error: ' . $e->getMessage());
    $total_products_count = 0;
    $filtered_count = 0;
    $total_categories_count = 0;
    $total_stock_value = 0;
    $total_out_of_stock = 0;
    $total_low_stock = 0;
    $total_in_stock = 0;
    $total_top_selling = 0;
}

// ===================== HANDLE CSV EXPORT =====================
if (isset($_GET['download']) && $_GET['download'] === 'all') {
    ob_clean();

    $search_export = trim($_GET['search'] ?? '');
    $selected_category_id_export = trim($_GET['category_id'] ?? '');

    $query_export = '
        SELECT
            p.id,
            p.name,
            p.sku,
            COALESCE(c.name, p.category) as category_name,
            p.price,
            p.created_at,
            s.name as supplier_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
    ';

    $conditions_export = [];
    $params_export = [];

    if ($search_export) {
        $conditions_export[] = '(p.name LIKE ? OR p.sku LIKE ?)';
        $params_export[] = '%' . $search_export . '%';
        $params_export[] = '%' . $search_export . '%';
    }
    if ($selected_category_id_export !== '') {
        $conditions_export[] = 'p.category_id = ?';
        $params_export[] = $selected_category_id_export;
    }
    if (!empty($conditions_export)) {
        $query_export .= ' WHERE ' . implode(' AND ', $conditions_export);
    }
    $query_export .= ' ORDER BY p.created_at DESC';

    $stmt_export = $pdo->prepare($query_export);
    $stmt_export->execute($params_export);
    $products_for_export = $stmt_export->fetchAll();

    foreach ($products_for_export as &$prod) {
        $prod['stock'] = getCurrentStock($pdo, $prod['id']);
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'SKU', 'Product Name', 'Category', 'Supplier', 'Price (ETB)', 'Current Stock', 'Created Date']);

    foreach ($products_for_export as $prod) {
        fputcsv($output, [
            $prod['id'],
            $prod['sku'] ?? '—',
            $prod['name'],
            $prod['category_name'] ?? 'Other',
            $prod['supplier_name'] ?? '—',
            number_format($prod['price'], 2),
            $prod['stock'],
            date('Y-m-d', strtotime($prod['created_at']))
        ]);
    }

    fclose($output);
    exit;
}

// Flash message handling
$flash_msg = $_SESSION['flash_msg'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
if ($flash_msg) {
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

$page_title = 'Products Inventory';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Flash Message -->
<?php if ($flash_msg): ?>
<div class="alert alert-<?php echo $flash_type === 'error' ? 'danger' : 'success'; ?> border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center gap-2 animate__animated animate__fadeIn">
    <i class="bi bi-<?php echo $flash_type === 'error' ? 'exclamation-octagon' : 'check-circle'; ?> fs-5"></i>
    <?php echo htmlspecialchars($flash_msg); ?>
</div>
<?php endif; ?>

<!-- Top Welcome & Controls -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">📦 Products Inventory</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Manage and monitor registered products
            <?php if ($search || $selected_category_id !== '' || $filter): ?>
                · <span class="fw-medium"><?php echo $filtered_count; ?> result<?php echo $filtered_count !== 1 ? 's' : ''; ?> found</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="add.php" class="btn btn-primary btn-sm px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-1" style="font-weight: 600; font-size: 13px;">
            <i class="bi bi-plus-lg"></i> Add Product
        </a>
    </div>
</div>

<!-- Inline Styles for Products Module -->
<style>
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
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: #5a6a85;
        font-weight: 700;
        padding: 14px 16px;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(19, 102, 217, 0.02);
    }
    @media (min-width: 768px) {
        .border-end-divider {
            border-right: 1px solid var(--navbar-border) !important;
        }
    }
    .stock-badge-in {
        background-color: #d4edda;
        color: #198754;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .stock-badge-low {
        background-color: #fff3cd;
        color: #e28743;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .stock-badge-out {
        background-color: #f8d7da;
        color: #dc3545;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
    }
    /* Dark mode overrides for sticky header */
    [data-bs-theme="dark"] .table-responsive-sticky thead th {
        background-color: #1e293b;
        box-shadow: inset 0 -2px 0 #334155;
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .stock-badge-in {
        background-color: rgba(25, 135, 84, 0.15);
        color: #4ade80;
    }
    [data-bs-theme="dark"] .stock-badge-low {
        background-color: rgba(226, 135, 67, 0.15);
        color: #fbbf24;
    }
    [data-bs-theme="dark"] .stock-badge-out {
        background-color: rgba(220, 53, 69, 0.15);
        color: #f87171;
    }
    @media (max-width: 768px) {
        .summary-card-value { font-size: 1.2rem; }
        .summary-card-label { font-size: 10px; }
        .border-end-divider { border-right: none !important; border-bottom: 1px solid var(--navbar-border); padding-bottom: 10px; margin-bottom: 10px; }
    }
</style>

<!-- Overall Inventory Summary Grid -->
<div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
    <h5 class="fw-bold text-dark mb-4" style="font-family: 'Inter', sans-serif;">Overall Inventory</h5>
    <div class="row g-3">
        <!-- Categories -->
        <div class="col-md-3 col-6 border-end-divider">
            <div class="d-flex flex-column gap-1 ps-2">
                <span class="fw-bold mb-2" style="color: #1366d9; font-size: 13px; font-family: 'Inter', sans-serif;">Categories</span>
                <span class="fw-extrabold text-dark fs-3 mb-0 summary-card-value" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_categories_count; ?></span>
                <span class="text-muted summary-card-label" style="font-size: 11px;">Active groups</span>
            </div>
        </div>

        <!-- Total Products -->
        <div class="col-md-3 col-6 border-end-divider">
            <div class="d-flex flex-column gap-1 ps-3">
                <span class="fw-bold mb-2" style="color: #e28743; font-size: 13px; font-family: 'Inter', sans-serif;">Total Products</span>
                <div class="d-flex justify-content-between align-items-center me-3">
                    <div>
                        <span class="fw-extrabold text-dark fs-3 d-block mb-0 summary-card-value" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_products_count; ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Registered</span>
                    </div>
                    <div class="text-end">
                        <span class="fw-extrabold text-dark fs-5 d-block mb-0" style="font-family: 'Outfit', sans-serif; font-weight: 800;">ETB <?php echo number_format($total_stock_value); ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Stock value</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Health -->
        <div class="col-md-3 col-6 border-end-divider">
            <div class="d-flex flex-column gap-1 ps-3">
                <span class="fw-bold mb-2" style="color: #845ec2; font-size: 13px; font-family: 'Inter', sans-serif;">Stock Health</span>
                <div class="d-flex justify-content-between align-items-center me-3">
                    <div>
                        <span class="fw-extrabold text-dark fs-3 d-block mb-0 summary-card-value" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_in_stock; ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Healthy</span>
                    </div>
                    <div class="text-end">
                        <span class="fw-extrabold text-dark fs-5 d-block mb-0" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_top_selling; ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Top selling</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low & Out of Stock -->
        <div class="col-md-3 col-6">
            <div class="d-flex flex-column gap-1 ps-3">
                <span class="fw-bold mb-2" style="color: #f35588; font-size: 13px; font-family: 'Inter', sans-serif;">Alerts</span>
                <div class="d-flex justify-content-between align-items-center me-2">
                    <div>
                        <span class="fw-extrabold text-dark fs-3 d-block mb-0 summary-card-value" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_low_stock; ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Low stock</span>
                    </div>
                    <div class="text-end">
                        <span class="fw-extrabold text-dark fs-5 d-block mb-0" style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?php echo $total_out_of_stock; ?></span>
                        <span class="text-muted summary-card-label" style="font-size: 11px;">Out of stock</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="collapse mb-4" id="filterCollapse">
    <div class="card border-0 shadow-sm p-4 rounded-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-funnel text-primary fs-5"></i>
            <h5 class="fw-bold text-dark mb-0">Search & Filter Catalog</h5>
        </div>
        <form method="GET" class="row g-3 align-items-end">
            <!-- Search -->
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label text-secondary fw-semibold" style="font-size: 12px;">Search Products</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name or SKU..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <!-- Category -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label text-secondary fw-semibold" style="font-size: 12px;">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($selected_category_id == $cat['id'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Stock Status -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label text-secondary fw-semibold" style="font-size: 12px;">Stock Status</label>
                <select name="filter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="in_stock" <?php echo ($filter === 'in_stock' ? 'selected' : ''); ?>>In Stock</option>
                    <option value="low_stock" <?php echo ($filter === 'low_stock' ? 'selected' : ''); ?>>Low Stock (&lt; <?php echo $low_stock_threshold; ?>)</option>
                    <option value="out_of_stock" <?php echo ($filter === 'out_of_stock' ? 'selected' : ''); ?>>Out of Stock</option>
                </select>
            </div>
            <!-- Sort -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                <label class="form-label text-secondary fw-semibold" style="font-size: 12px;">Sort By</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?php echo ($sort === 'newest' ? 'selected' : ''); ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort === 'oldest' ? 'selected' : ''); ?>>Oldest First</option>
                    <option value="name_asc" <?php echo ($sort === 'name_asc' ? 'selected' : ''); ?>>Name A–Z</option>
                    <option value="name_desc" <?php echo ($sort === 'name_desc' ? 'selected' : ''); ?>>Name Z–A</option>
                    <option value="price_asc" <?php echo ($sort === 'price_asc' ? 'selected' : ''); ?>>Price Low→High</option>
                    <option value="price_desc" <?php echo ($sort === 'price_desc' ? 'selected' : ''); ?>>Price High→Low</option>
                    <option value="stock_asc" <?php echo ($sort === 'stock_asc' ? 'selected' : ''); ?>>Stock Low→High</option>
                    <option value="stock_desc" <?php echo ($sort === 'stock_desc' ? 'selected' : ''); ?>>Stock High→Low</option>
                </select>
            </div>
            <!-- Buttons -->
            <div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Apply</button>
                <?php if ($search || $selected_category_id !== '' || $filter || $sort !== 'newest'): ?>
                    <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 42px; border-radius: 30px;" title="Clear all filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0" style="font-family: 'Inter', sans-serif;">Products</h5>
            <p class="text-muted mb-0" style="font-size: 12px;">
                Showing <strong><?php echo $filtered_count; ?></strong> of <strong><?php echo $total_products_count; ?></strong> product<?php echo $total_products_count !== 1 ? 's' : ''; ?>
                <?php if ($search || $selected_category_id !== '' || $filter): ?>
                    · <a href="list.php" class="text-primary text-decoration-none">Clear filters</a>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 d-flex align-items-center gap-1 bg-white text-dark border shadow-sm" style="font-weight: 600; font-size: 13px;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                <i class="bi bi-funnel"></i> Filters
            </button>
            <a href="?download=all<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $selected_category_id !== '' ? '&category_id='.urlencode($selected_category_id) : ''; ?>" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 d-flex align-items-center gap-1 bg-white text-dark border shadow-sm" style="font-weight: 600; font-size: 13px;">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px; background: rgba(19,102,217,0.06);">
                <?php if ($search || $selected_category_id !== '' || $filter): ?>
                    <i class="bi bi-search fs-3 text-muted"></i>
                <?php else: ?>
                    <i class="bi bi-box-seam fs-3 text-muted"></i>
                <?php endif; ?>
            </div>
            <?php if ($search || $selected_category_id !== '' || $filter): ?>
                <h6 class="fw-bold text-secondary mb-1">No matching products found</h6>
                <p class="text-muted mb-3" style="font-size: 13px;">Try adjusting your search or filter criteria.</p>
                <a href="list.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Clear All Filters</a>
            <?php else: ?>
                <h6 class="fw-bold text-secondary mb-1">No products in inventory yet</h6>
                <p class="text-muted mb-3" style="font-size: 13px;">Start building your catalog by adding the first product.</p>
                <a href="add.php" class="btn btn-primary btn-sm rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i> Add First Product</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product):
                        $stock = (int)$product['stock'];
                        $is_out = $stock === 0;
                        $is_low = $stock > 0 && $stock < $low_stock_threshold;
                        $is_healthy = $stock >= $low_stock_threshold;

                        // Product image
                        $images = glob('../uploads/products/product_' . $product['id'] . '.*');
                        $current_image = $images ? $images[0] : 'https://cdn-icons-png.flaticon.com/512/5164/5164023.png';
                    ?>
                    <tr>
                        <!-- Product: Image + Name + Category -->
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Product" class="rounded-3 shadow-sm border" style="width: 44px; height: 44px; object-fit: cover; background: #fff; flex-shrink: 0;">
                                <div>
                                    <a href="view.php?id=<?php echo $product['id']; ?>" class="fw-bold text-dark text-decoration-none d-block mb-0.5" style="font-family: 'Inter', sans-serif;">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                    <small class="text-secondary fw-semibold" style="font-size: 11px;">
                                        <span class="bg-light px-2 py-0.5 rounded border text-muted">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Other'); ?>
                                        </span>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <!-- SKU -->
                        <td>
                            <code class="text-muted" style="font-size: 12px; background: transparent;"><?php echo htmlspecialchars($product['sku'] ?: '—'); ?></code>
                        </td>
                        <!-- Price -->
                        <td class="fw-bold text-dark">ETB <?php echo number_format($product['price'], 2); ?></td>
                        <!-- Quantity -->
                        <td class="text-center">
                            <span class="fw-bold text-dark"><?php echo $stock; ?></span>
                            <span class="text-muted" style="font-size: 11px;"> units</span>
                        </td>
                        <!-- Stock Status Badge -->
                        <td class="text-center">
                            <?php if ($is_out): ?>
                                <span class="stock-badge-out">Out of Stock</span>
                            <?php elseif ($is_low): ?>
                                <span class="stock-badge-low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-badge-in">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <!-- Actions Dropdown -->
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px; font-weight: 500;">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="font-size: 13px; min-width: 170px;">
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-primary d-flex align-items-center gap-2" href="view.php?id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-eye"></i> View Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-success d-flex align-items-center gap-2" href="../inventory/movement.php?product_id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-arrow-left-right"></i> Stock Adjustment
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1 border-light"></li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-dark d-flex align-items-center gap-2" href="edit.php?id=<?php echo $product['id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-danger d-flex align-items-center gap-2" href="#" onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $product['id']; ?>', 'Are you sure you want to delete &quot;<?php echo htmlspecialchars(addslashes($product['name'])); ?>&quot;? All historical transactions remain logged, but this catalog item will be removed.'); return false;">
                                            <i class="bi bi-trash"></i> Delete
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