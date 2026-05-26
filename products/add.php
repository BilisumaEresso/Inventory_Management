<?php
/**
 * Add New Product — Professional Upgrade
 * Updated: notification trigger on product creation
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/notification_helper.php';

$error_message = '';
$success_fields = [];
$suppliers = [];
$categories = [];

try {
    $stmt = $pdo->query('SELECT id, name FROM suppliers ORDER BY name');
    $suppliers = $stmt->fetchAll();

    $stmt_cat = $pdo->query('SELECT id, name FROM categories ORDER BY name');
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch Data Error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $supplier_id = trim($_POST['supplier_id'] ?? '');
    if ($supplier_id === '') $supplier_id = null;

    // Validate
    if (empty($name)) {
        $error_message = 'Product name is required.';
    } elseif (strlen($name) < 2) {
        $error_message = 'Product name must be at least 2 characters.';
    } elseif (empty($category_id)) {
        $error_message = 'Please select a category.';
    } elseif ($price === '' || !is_numeric($price) || $price < 0) {
        $error_message = 'Price must be a valid positive number.';
    } elseif ($price > 99999999) {
        $error_message = 'Price is too high. Please enter a reasonable amount.';
    } else {
        try {
            // Check for duplicate SKU if provided
            if (!empty($sku)) {
                $stmt_dup = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = ?');
                $stmt_dup->execute([$sku]);
                if ($stmt_dup->fetchColumn() > 0) {
                    $error_message = 'A product with SKU "' . htmlspecialchars($sku) . '" already exists. Please use a unique SKU.';
                }
            }

            if (empty($error_message)) {
                // Get category name for old column compatibility
                $stmt_c = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
                $stmt_c->execute([$category_id]);
                $category_name = $stmt_c->fetchColumn() ?: 'Other';

                // Insert Product
                $stmt = $pdo->prepare('INSERT INTO products (name, category_id, category, price, sku, description, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $category_id, $category_name, (float)$price, $sku, $description, $supplier_id]);
                $new_product_id = $pdo->lastInsertId();

                // Handle Image Upload
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['product_image']['tmp_name'];
                    $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($ext, $allowed)) {
                        if (!is_dir('../uploads/products')) {
                            mkdir('../uploads/products', 0755, true);
                        }
                        $filename = 'product_' . $new_product_id . '.' . $ext;
                        move_uploaded_file($tmp_name, '../uploads/products/' . $filename);
                    }
                }

                // *** NEW: Create notification ***
                createNotification(
                    $pdo,
                    'Product Added',
                    '"' . $name . '" has been registered in the inventory catalog.',
                    'success',
                    'products/view.php?id=' . $new_product_id
                );

                $_SESSION['flash_msg'] = 'Product "' . $name . '" registered successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: Unable to save product. Please try again.';
            error_log('Add Product Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Add New Product';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">📦 Add New Product</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Create a new product in your inventory catalog.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-3 shadow-sm">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
        <!-- Info alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(19,102,217,0.1);">
                <i class="bi bi-info-circle text-primary"></i>
            </div>
            <div>
                <strong class="text-dark">Quick Note:</strong> Add product metadata here. Initial stock quantities are set later through <strong>Stock Adjustment</strong> in the inventory movements section.
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
            <form method="POST" enctype="multipart/form-data" id="addProductForm" class="row g-4">

                <!-- Section: Basic Information -->
                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-size: 14px;">
                        <i class="bi bi-info-circle me-2 text-primary"></i>Basic Information
                    </h6>
                </div>

                <!-- Product Name -->
                <div class="col-md-8 col-12">
                    <label for="name" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3" required
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="e.g. Organic Coffee Beans 500g"
                           maxlength="150"
                           autofocus>
                    <div class="form-text" style="font-size: 11px;">Use a clear, descriptive name for easy identification.</div>
                </div>

                <!-- SKU -->
                <div class="col-md-4 col-12">
                    <label for="sku" class="form-label fw-semibold text-secondary" style="font-size: 13px;">SKU Code</label>
                    <input type="text" id="sku" name="sku" class="form-control p-3 rounded-3"
                           value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>"
                           placeholder="e.g. COF-500-001"
                           maxlength="50"
                           style="font-family: monospace;">
                    <div class="form-text" style="font-size: 11px;">Optional unique stock-keeping unit.</div>
                </div>

                <!-- Section: Categorization -->
                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-size: 14px;">
                        <i class="bi bi-tags me-2 text-success"></i>Categorization
                    </h6>
                </div>

                <!-- Category -->
                <div class="col-md-6 col-12">
                    <label for="category_id" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Category <span class="text-danger">*</span></label>
                    <select id="category_id" name="category_id" class="form-select p-3 rounded-3" required>
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ((isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($categories)): ?>
                        <div class="form-text text-warning" style="font-size: 11px;">
                            <i class="bi bi-exclamation-triangle"></i> No categories defined. <a href="../categories/add.php" target="_blank">Add one first</a>.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Supplier -->
                <div class="col-md-6 col-12">
                    <label for="supplier_id" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select p-3 rounded-3">
                        <option value="">— No Supplier —</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo ((isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['id']) ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Section: Pricing & Media -->
                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-size: 14px;">
                        <i class="bi bi-currency-dollar me-2 text-warning"></i>Pricing &amp; Media
                    </h6>
                </div>

                <!-- Price -->
                <div class="col-md-6 col-12">
                    <label for="price" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Unit Price (ETB) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold" style="font-size: 13px;">ETB</span>
                        <input type="number" id="price" name="price" class="form-control p-3 rounded-end-3"
                               min="0" max="99999999" step="0.01" required
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Product Image -->
                <div class="col-md-6 col-12">
                    <label for="product_image" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Product Image</label>
                    <input type="file" id="product_image" name="product_image" class="form-control p-3 rounded-3" accept="image/*">
                    <div class="form-text" style="font-size: 11px;">Recommended: Square image (JPG, PNG, WEBP). Max 5MB.</div>
                </div>

                <!-- Section: Details -->
                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-size: 14px;">
                        <i class="bi bi-journal-text me-2 text-secondary"></i>Details
                    </h6>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Product Description</label>
                    <textarea id="description" name="description" class="form-control p-3 rounded-3" rows="4"
                              placeholder="Enter product details, specifications, or notes here..."
                              maxlength="1000"
                              oninput="document.getElementById('charCount').textContent = this.value.length;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="d-flex justify-content-between">
                        <div class="form-text" style="font-size: 11px;">Optional. Describe the product for reference.</div>
                        <small class="text-muted" style="font-size: 11px;"><span id="charCount"><?php echo strlen($_POST['description'] ?? ''); ?></span>/1000</small>
                    </div>
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2 d-flex gap-2">
                    <a href="list.php" class="btn btn-outline-secondary px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1 py-3 rounded-3 fw-bold" id="submitBtn">
                        <i class="bi bi-plus-lg"></i> Register Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading state script -->
<script>
document.getElementById('addProductForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
    btn.disabled = true;
});
</script>

<?php
require_once '../includes/layout-end.php';
?>