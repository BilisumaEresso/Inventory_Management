<?php
/**
 * Add New Category — Professional Upgrade
 * Updated: notification trigger on category creation
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/notification_helper.php';

$error_message = '';

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
            // Case-insensitive duplicate check
            $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?)');
            $stmt_check->execute([$name]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'A category with this name already exists (case-insensitive).';
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
                $stmt->execute([$name]);

                // *** NEW: Create notification ***
                createNotification(
                    $pdo,
                    'Category Created',
                    '"' . $name . '" category has been added.',
                    'success',
                    'categories/list.php'
                );

                $_SESSION['flash_msg'] = 'Category "' . htmlspecialchars($name) . '" created successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: Unable to save category. Please try again.';
            error_log('Add Category Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Add Category';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Add New Category</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Create a new logical classification for your products.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-3 shadow-sm">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-12 mx-auto">
        <!-- Info alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(19,102,217,0.1);">
                <i class="bi bi-info-circle text-primary"></i>
            </div>
            <div>
                <strong class="text-dark">Quick Note:</strong> Categories help you organize products. Once created, you can assign products to this category.
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill flex-shrink-0"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm p-4 mb-4 rounded-4">
            <form method="POST" id="addCategoryForm" class="row g-4">
                <!-- Category Name -->
                <div class="col-12">
                    <label for="name" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3"
                           required autofocus
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="e.g. Electronics, Groceries, Stationery"
                           maxlength="50"
                           oninput="document.getElementById('charCount').textContent = this.value.length;">
                    <div class="d-flex justify-content-between mt-1">
                        <div class="form-text" style="font-size: 11px;">Use a clear, descriptive name. Duplicates are not allowed.</div>
                        <small class="text-muted" style="font-size: 11px;"><span id="charCount"><?php echo strlen($_POST['name'] ?? ''); ?></span>/50</small>
                    </div>
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2 d-flex gap-2">
                    <a href="list.php" class="btn btn-outline-secondary px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1 py-3 rounded-3 fw-bold" id="submitBtn">
                        <i class="bi bi-plus-lg"></i> Register Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addCategoryForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
    btn.disabled = true;
});
</script>

<?php
require_once '../includes/layout-end.php';
?>