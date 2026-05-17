<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$error_message = '';
$category = null;
$category_id = $_GET['id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    header('Location: list.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        header('Location: list.php');
        exit;
    }

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
    } else {
        try {
            // Check if name is unique (exclude current)
            $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?');
            $stmt_check->execute([$name, $category_id]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'Category already exists.';
            } else {
                $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
                $stmt->execute([$name, $category_id]);

                // Also update the old category text field in products for backward compatibility!
                $stmt_prod_update = $pdo->prepare('UPDATE products SET category = ? WHERE category_id = ?');
                $stmt_prod_update->execute([$name, $category_id]);

                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating category. Please try again.';
            error_log('Update Category Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Edit Category';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Edit Category</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Modify logic classifications and update all associated item descriptions.</p>
    </div>
    <div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Categories
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-12 mx-auto">
        <!-- Info alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-info-circle-fill text-info fs-3"></i>
            <div>
                <strong class="text-dark">Quick Note:</strong> Updating this name will also update the legacy string fields inside all corresponding product items.
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <?php if ($category): ?>
        <div class="card card-custom border-0 shadow-sm p-4 mb-4">
            <form method="POST" class="row g-4">
                <!-- Name -->
                <div class="col-12">
                    <label for="name" class="form-label fw-semibold text-secondary">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3" required value="<?php echo htmlspecialchars($_POST['name'] ?? $category['name']); ?>" placeholder="e.g. Networking Equipments">
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"><i class="bi bi-check-circle"></i> Save Category Changes</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/layout-end.php';
?>
