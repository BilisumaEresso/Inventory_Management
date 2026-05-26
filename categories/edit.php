<?php
/**
 * Edit Category — Professional Upgrade
 * Updated: notification trigger on category update
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/notification_helper.php';

$error_message = '';
$category = null;
$product_count = 0;
$category_id = $_GET['id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    header('Location: list.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        WHERE c.id = ?
        GROUP BY c.id
    ');
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        header('Location: list.php');
        exit;
    }

    $product_count = (int)$category['product_count'];

    // Protect system category
    if (strtolower($category['name']) === 'other') {
        header('Location: list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Fetch Category Error: ' . $e->getMessage());
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error_message = 'Category name is required.';
    } elseif (strlen($name) < 2) {
        $error_message = 'Category name must be at least 2 characters.';
    } elseif (strlen($name) > 50) {
        $error_message = 'Category name must not exceed 50 characters.';
    } else {
        try {
            $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?');
            $stmt_check->execute([$name, $category_id]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'Another category with this name already exists (case-insensitive).';
            } else {
                $old_name = $category['name'];
                $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
                $stmt->execute([$name, $category_id]);

                // Update legacy text field in products
                $stmt_prod_update = $pdo->prepare('UPDATE products SET category = ? WHERE category_id = ?');
                $stmt_prod_update->execute([$name, $category_id]);

                // *** NEW: Create notification ***
                createNotification(
                    $pdo,
                    'Category Updated',
                    '"' . $old_name . '" has been renamed to "' . $name . '".',
                    'info',
                    'categories/list.php'
                );

                $_SESSION['flash_msg'] = 'Category "' . htmlspecialchars($name) . '" updated successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: Unable to update category. Please try again.';
            error_log('Update Category Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Edit Category';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Edit Category</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Modify category <strong><?php echo htmlspecialchars($category['name']); ?></strong> (ID #<?php echo $category_id; ?>).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-3 shadow-sm">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-12 mx-auto">
        <div class="alert alert-<?php echo $product_count > 0 ? 'info' : 'light'; ?> border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(19,102,217,0.1);">
                <i class="bi bi-box-seam text-primary"></i>
            </div>
            <div>
                <strong class="text-dark"><?php echo $product_count; ?> product<?php echo $product_count !== 1 ? 's' : ''; ?> assigned</strong>
                <p class="mb-0 mt-1" style="font-size: 12px;">
                    <?php if ($product_count > 0): ?>
                        Renaming this category will update the legacy category field for all <?php echo $product_count; ?> linked product(s).
                    <?php else: ?>
                        This category has no products assigned yet.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill flex-shrink-0"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
            <form method="POST" id="editCategoryForm" class="row g-4">
                <div class="col-12">
                    <label for="name" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3"
                           required autofocus
                           value="<?php echo htmlspecialchars($_POST['name'] ?? $category['name']); ?>"
                           placeholder="e.g. Electronics, Groceries, Stationery"
                           maxlength="50"
                           oninput="document.getElementById('charCount').textContent = this.value.length;">
                    <div class="d-flex justify-content-between mt-1">
                        <div class="form-text" style="font-size: 11px;">Duplicates are not allowed (case-insensitive).</div>
                        <small class="text-muted" style="font-size: 11px;"><span id="charCount"><?php echo strlen($_POST['name'] ?? $category['name']); ?></span>/50</small>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border-top pt-3 mt-2">
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> Created: <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                            &nbsp;·&nbsp; ID: #<?php echo $category_id; ?>
                        </small>
                    </div>
                </div>

                <div class="col-12 pt-2 d-flex gap-2">
                    <a href="list.php" class="btn btn-outline-secondary px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1 py-3 rounded-3 fw-bold" id="submitBtn">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editCategoryForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
    btn.disabled = true;
});
</script>

<?php
require_once '../includes/layout-end.php';
?>