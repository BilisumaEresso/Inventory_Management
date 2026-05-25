<?php
// Step 14-16: Add Product with Validation and Insert (Metadata Only)
require_once '../middleware/auth.php';
require_once '../config/db.php';

$error_message = '';
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
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $supplier_id = trim($_POST['supplier_id'] ?? '');
    if ($supplier_id === '') $supplier_id = null;

    // Step 15: Validate
    if (empty($name)) {
        $error_message = 'Product name is required.';
    } elseif (empty($category_id)) {
        $error_message = 'Category is required.';
    } elseif ($price === '' || !is_numeric($price) || $price < 0) {
        $error_message = 'Price must be a number and not negative.';
    } else {
        try {
            // Get category name for old column compatibility
            $stmt_c = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
            $stmt_c->execute([$category_id]);
            $category_name = $stmt_c->fetchColumn() ?: 'Other';

            // Insert Product using prepared statement with 7 placeholders
            $stmt = $pdo->prepare('INSERT INTO products (name, category_id, category, price, sku, description, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $category_id, $category_name, (float)$price, $sku, $description, $supplier_id]);
            $new_product_id = $pdo->lastInsertId();

            // Handle Image Upload
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['product_image']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $filename = 'product_' . $new_product_id . '.' . $ext;
                    move_uploaded_file($tmp_name, '../uploads/products/' . $filename);
                }
            }

            // Redirect to list after success
            $_SESSION['flash_msg'] = 'Product "' . $name . '" registered successfully.';
            $_SESSION['flash_type'] = 'success';
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error_message = 'Error adding product. Please try again.';
            error_log('Add Product Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Add New Product';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">📦 Add New Product</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Create a brand-new dynamic product catalog item.</p>
    </div>
    <div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
        <!-- Info alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-info-circle-fill text-info fs-3"></i>
            <div>
                <strong class="text-dark">Quick Note:</strong> Add product metadata details here. Initial warehouse stock units are logged by performing a <strong>Stock Adjustment</strong> in inventory movements after saving.
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card card-custom border-0 shadow-sm p-4 mb-4">
            <form method="POST" enctype="multipart/form-data" class="row g-4">
                <!-- Name -->
                <div class="col-12">
                    <label for="name" class="form-label fw-semibold text-secondary">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="e.g. Dell XPS 15 Laptop">
                </div>

                <!-- Product Image -->
                <div class="col-12">
                    <label for="product_image" class="form-label fw-semibold text-secondary">Product Image</label>
                    <input type="file" id="product_image" name="product_image" class="form-control p-3 rounded-3" accept="image/*">
                    <div class="form-text" style="font-size: 11px;">Recommended: Square image (JPG, PNG, WEBP).</div>
                </div>

                <!-- Category -->
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-semibold text-secondary">Category *</label>
                    <select id="category_id" name="category_id" class="form-select p-3 rounded-3" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ((isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Supplier -->
                <div class="col-md-6">
                    <label for="supplier_id" class="form-label fw-semibold text-secondary">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select p-3 rounded-3">
                        <option value="">-- No Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo ((isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['id']) ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- SKU -->
                <div class="col-md-6">
                    <label for="sku" class="form-label fw-semibold text-secondary">SKU Code</label>
                    <input type="text" id="sku" name="sku" class="form-control p-3 rounded-3" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" placeholder="e.g. DELL-XPS15-001">
                </div>

                <!-- Price -->
                <div class="col-md-6">
                    <label for="price" class="form-label fw-semibold text-secondary">Price (ETB) *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">ETB</span>
                        <input type="number" id="price" name="price" class="form-control p-3 rounded-end-3" min="0" step="0.01" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" placeholder="0.00">
                    </div>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold text-secondary">Product Description</label>
                    <textarea id="description" name="description" class="form-control p-3 rounded-3" rows="4" placeholder="Enter catalog item description here..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"><i class="bi bi-plus-lg"></i> Register Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/layout-end.php';
?>
