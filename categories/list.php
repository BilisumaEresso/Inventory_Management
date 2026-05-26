<?php
/**
 * Category Manager – Professional Table Upgrade
 * Preserves: auth, search, CRUD, delete protection, product reassignment
 * Enhances: table design, summary stats, sort, flash messages
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';

$search = trim($_GET['search'] ?? '');
$sort   = trim($_GET['sort'] ?? 'name_asc');
$categories = [];
$total_categories = 0;
$total_assigned   = 0;
$total_products   = 0;

try {
    // Build query with product counts
    $query = "
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
    ";
    $conditions = [];
    $params     = [];

    if ($search) {
        $conditions[] = "c.name LIKE ?";
        $params[]     = '%' . $search . '%';
    }

    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $query .= ' GROUP BY c.id ';

    switch ($sort) {
        case 'name_desc':
            $query .= ' ORDER BY c.name DESC';
            break;
        case 'newest':
            $query .= ' ORDER BY c.created_at DESC';
            break;
        case 'oldest':
            $query .= ' ORDER BY c.created_at ASC';
            break;
        case 'most_products':
            $query .= ' ORDER BY product_count DESC, c.name ASC';
            break;
        case 'least_products':
            $query .= ' ORDER BY product_count ASC, c.name ASC';
            break;
        case 'name_asc':
        default:
            $query .= ' ORDER BY c.name ASC';
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();

    // Summary stats
    $total_categories = count($categories);
    foreach ($categories as $cat) {
        $total_assigned += (int) $cat['product_count'];
    }
    $stmt_total = $pdo->query('SELECT COUNT(*) FROM products');
    $total_products = (int) $stmt_total->fetchColumn();

} catch (PDOException $e) {
    error_log('List Categories Error: ' . $e->getMessage());
}

// Flash message handling
$flash_msg  = $_SESSION['flash_msg'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
if ($flash_msg) {
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

$page_title = 'Category Manager';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Inline Styles -->
<style>
    /* Table styling */
    .category-table thead th {
        background-color: #f8fafc;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: #5a6a85;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
        padding: 14px 16px;
    }
    .category-table tbody td {
        vertical-align: middle;
        padding: 14px 16px;
    }
    .category-table tbody tr:hover {
        background-color: rgba(19, 102, 217, 0.02);
    }
    .product-badge {
        font-size: 12px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .product-badge.zero {
        background-color: #f1f5f9;
        color: #64748b;
    }
    .product-badge.has-items {
        background-color: #d4edda;
        color: #198754;
    }
    .system-badge {
        background-color: #e0f2fe;
        color: #0369a1;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 12px;
    }
    /* Dark mode overrides */
    [data-bs-theme="dark"] .category-table thead th {
        background-color: #1e293b;
        color: #94a3b8;
        border-bottom-color: #334155;
    }
    [data-bs-theme="dark"] .category-table tbody tr:hover {
        background-color: rgba(255,255,255,0.02);
    }
    [data-bs-theme="dark"] .product-badge.zero {
        background-color: #334155;
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .product-badge.has-items {
        background-color: rgba(25,135,84,0.15);
        color: #4ade80;
    }
    [data-bs-theme="dark"] .system-badge {
        background-color: rgba(2,132,199,0.15);
        color: #38bdf8;
    }
</style>

<!-- Flash Message -->
<?php if ($flash_msg): ?>
<div class="alert alert-<?php echo $flash_type === 'error' ? 'danger' : 'success'; ?> border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
    <i class="bi bi-<?php echo $flash_type === 'error' ? 'exclamation-octagon' : 'check-circle'; ?> fs-5"></i>
    <?php echo htmlspecialchars($flash_msg); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Category Manager</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Organize products into logical classifications
            <?php if ($search): ?>
                · <span class="fw-medium"><?php echo $total_categories; ?> result<?php echo $total_categories !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="add.php" class="btn btn-primary btn-sm px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-1" style="font-weight: 600; font-size: 13px;">
            <i class="bi bi-plus-lg"></i> Add Category
        </a>
    </div>
</div>

<!-- Summary Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6 col-12">
        <div class="card border-0 shadow-sm p-3 rounded-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(19,102,217,0.08); color: #1366d9;">
                    <i class="bi bi-tags fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?php echo $total_categories; ?></h5>
                    <small class="text-muted">Total Categories</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="card border-0 shadow-sm p-3 rounded-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(25,135,84,0.08); color: #198754;">
                    <i class="bi bi-box-seam fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?php echo $total_assigned; ?></h5>
                    <small class="text-muted">Assigned Products</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="card border-0 shadow-sm p-3 rounded-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(255,152,0,0.08); color: #ff9800;">
                    <i class="bi bi-question-circle fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0"><?php echo max(0, $total_products - $total_assigned); ?></h5>
                    <small class="text-muted">Uncategorized Products</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Sort Row -->
<div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-6 col-md-8 col-12">
            <label class="form-label fw-semibold text-secondary" style="font-size: 12px;">Search Categories</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12">
            <label class="form-label fw-semibold text-secondary" style="font-size: 12px;">Sort By</label>
            <select name="sort" class="form-select">
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A–Z</option>
                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z–A</option>
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="most_products" <?php echo $sort === 'most_products' ? 'selected' : ''; ?>>Most Products</option>
                <option value="least_products" <?php echo $sort === 'least_products' ? 'selected' : ''; ?>>Least Products</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-12 col-sm-6 col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Apply</button>
            <?php if ($search || $sort !== 'name_asc'): ?>
                <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 42px; border-radius: 30px;" title="Clear all"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Categories Table Card -->
<div class="card border-0 shadow-sm p-4 rounded-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0" style="font-family: 'Inter', sans-serif;">Categories</h5>
        <small class="text-muted"><?php echo $total_categories; ?> category<?php echo $total_categories !== 1 ? 'ies' : 'y'; ?></small>
    </div>

    <?php if (empty($categories)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: rgba(19,102,217,0.06);">
                <?php if ($search): ?>
                    <i class="bi bi-search fs-3 text-muted"></i>
                <?php else: ?>
                    <i class="bi bi-tags fs-3 text-muted"></i>
                <?php endif; ?>
            </div>
            <?php if ($search): ?>
                <h6 class="fw-bold text-secondary mb-1">No matching categories found</h6>
                <p class="text-muted mb-3" style="font-size: 13px;">Try a different search term or clear the filters.</p>
                <a href="list.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Clear Filters</a>
            <?php else: ?>
                <h6 class="fw-bold text-secondary mb-1">No categories defined yet</h6>
                <p class="text-muted mb-3" style="font-size: 13px;">Start organizing your products by creating your first category.</p>
                <a href="add.php" class="btn btn-primary btn-sm rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i> Add First Category</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table category-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Category Name</th>
                        <th>Products</th>
                        <th>Created</th>
                        <th class="text-end" style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat):
                        $product_count = (int) $cat['product_count'];
                        $is_default    = (strtolower($cat['name']) === 'other');
                    ?>
                    <tr>
                        <td class="text-muted fw-semibold">#<?php echo htmlspecialchars($cat['id']); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                <?php if ($is_default): ?>
                                    <span class="system-badge">System Default</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($product_count > 0): ?>
                                <span class="product-badge has-items"><?php echo $product_count; ?> product<?php echo $product_count !== 1 ? 's' : ''; ?></span>
                            <?php else: ?>
                                <span class="product-badge zero">0 products</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size: 12px; font-weight: 600;">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <?php if ($is_default): ?>
                                    <span class="btn btn-sm btn-outline-secondary rounded-pill px-3 disabled" style="font-size: 12px; cursor: not-allowed;">
                                        <i class="bi bi-lock me-1"></i> Locked
                                    </span>
                                <?php else: ?>
                                    <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size: 12px; font-weight: 600;"
                                       onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $cat['id']; ?>', 'Are you sure you want to delete <strong><?php echo htmlspecialchars(addslashes($cat['name'])); ?></strong>? <?php echo $product_count > 0 ? 'The ' . $product_count . ' product(s) will be reassigned to &quot;Other&quot;.' : ''; ?>'); return false;">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </a>
                                <?php endif; ?>
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