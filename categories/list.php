<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$search = trim($_GET['search'] ?? '');
$categories = [];

try {
    if ($search) {
        $stmt = $pdo->prepare('
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON p.category_id = c.id 
            WHERE c.name LIKE ? 
            GROUP BY c.id 
            ORDER BY c.name ASC
        ');
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $pdo->prepare('
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON p.category_id = c.id 
            GROUP BY c.id 
            ORDER BY c.name ASC
        ');
        $stmt->execute();
    }
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('List Categories Error: ' . $e->getMessage());
}

$page_title = 'Category Manager';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Category Manager</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Organize products into relational classifications securely.</p>
    </div>
    <div>
        <a href="add.php" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg"></i> Add Category
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-10">
            <label class="form-label fw-semibold text-secondary" style="font-size: 13px;">Search Categories</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3">Search</button>
            <?php if ($search): ?>
                <a href="list.php" class="btn btn-outline-secondary py-2.5 rounded-3"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Categories Table Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <?php if (empty($categories)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px;"><i class="bi bi-tags fs-3"></i></div>
            <h6 class="fw-bold text-secondary mb-1">No categories found</h6>
            <p class="text-muted mb-0" style="font-size: 12px;">Try adjusting filters or add a new custom category.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Category Name</th>
                        <th>Associated Products</th>
                        <th>Created Date</th>
                        <th class="text-end" style="min-width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="text-muted">#<?php echo htmlspecialchars($cat['id']); ?></td>
                        <td>
                            <strong class="text-dark fs-6"><?php echo htmlspecialchars($cat['name']); ?></strong>
                            <?php if (strtolower($cat['name']) === 'other'): ?>
                                <span class="badge bg-light text-secondary border ms-2" style="font-size: 10px;">System Default</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 11px;">
                                <?php echo htmlspecialchars($cat['product_count']); ?> items
                            </span>
                        </td>
                        <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size: 11px; font-weight: 600;">Edit</a>
                                <?php if (strtolower($cat['name']) !== 'other'): ?>
                                    <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size: 11px; font-weight: 600;" onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $cat['id']; ?>', 'Are you sure you want to delete this category? All linked products will have their category set to NULL.'); return false;">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted d-inline-block px-3 py-1" style="font-size: 11px; font-style: italic;">Locked</span>
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
