<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error_message = 'Category name is required.';
    } else {
        try {
            // Check if name is unique
            $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ?');
            $stmt_check->execute([$name]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'Category already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
                $stmt->execute([$name]);

                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Error adding category. Please try again.';
            error_log('Add Category Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Add Category';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏷️ Add New Category</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Create a new logical catalog classification.</p>
    </div>
    <div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Categories
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-12 mx-auto">
        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card card-custom border-0 shadow-sm p-4 mb-4">
            <form method="POST" class="row g-4">
                <!-- Name -->
                <div class="col-12">
                    <label for="name" class="form-label fw-semibold text-secondary">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="e.g. Networking Equipments">
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"><i class="bi bi-plus-lg"></i> Register Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/layout-end.php';
?>
