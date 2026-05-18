<?php
// Stock Adjustment Module - Create stock movements
require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/stock_helper.php';

$error_message = '';
$success_message = '';
$products = [];

try {
    // Get all products
    $stmt = $pdo->prepare('SELECT id, name, category FROM products ORDER BY name');
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch Products Error: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = trim($_POST['product_id'] ?? '');
    $movement_type = trim($_POST['movement_type'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $reference_no = trim($_POST['reference_no'] ?? '');

    // Validate
    if (empty($product_id) || !is_numeric($product_id)) {
        $error_message = 'Please select a product.';
    } elseif (empty($movement_type) || !in_array($movement_type, ['IN', 'OUT'])) {
        $error_message = 'Please select a movement type (IN or OUT).';
    } elseif ($quantity === '' || !is_numeric($quantity) || $quantity <= 0) {
        $error_message = 'Quantity must be a positive number.';
    } elseif (empty($reason)) {
        $error_message = 'Please provide a reason.';
    } else {
        try {
            // Validate insufficient stock for OUT movements
            if ($movement_type === 'OUT') {
                $current_stock = getCurrentStock($pdo, $product_id);
                if ($current_stock < $quantity) {
                    $error_message = "Insufficient stock. Current stock: $current_stock";
                } else {
                    // Create stock movement
                    $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$product_id, $movement_type, (int)$quantity, $reason, $reference_no, $_SESSION['user_id']]);

                    $_SESSION['flash_msg'] = 'Stock movement recorded successfully!';
                    $_SESSION['flash_type'] = 'success';
                    $success_message = 'Stock movement recorded successfully!';
                    $_POST = []; // Clear form
                }
            } else {
                // IN movement (always valid)
                $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$product_id, $movement_type, (int)$quantity, $reason, $reference_no, $_SESSION['user_id']]);

                $_SESSION['flash_msg'] = 'Stock movement recorded successfully!';
                $_SESSION['flash_type'] = 'success';
                $success_message = 'Stock movement recorded successfully!';
                $_POST = []; // Clear form
            }
        } catch (PDOException $e) {
            $error_message = 'Error recording movement. Please try again.';
            error_log('Stock Movement Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Stock Adjustment';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0 text-dark" style="font-family:'Inter',sans-serif;font-size:18px;">New Stock Movement</h5>
        <p class="text-muted mb-0" style="font-size:12px;">Log stock intake (IN) or dispatch (OUT) against any product.</p>
    </div>
    <a href="history.php" class="btn btn-outline-secondary px-4 py-2 rounded-3 d-flex align-items-center gap-2" style="font-weight:600;font-size:13px;">
        <i class="bi bi-clock-history"></i> View History
    </a>
</div>


<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
        <!-- Info alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-info-circle-fill text-info fs-3"></i>
            <div>
                <strong class="text-dark">Manual Entry Warning:</strong> Stock movements recorded here instantly recalculate current inventory counts. Always specify reference numbers for cross-referencing audit trails.
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card card-custom border-0 shadow-sm p-4 mb-4">
            <form method="POST" class="row g-4">
                <!-- Product -->
                <div class="col-12">
                    <label for="product_id" class="form-label fw-semibold text-secondary">Product to Adjust *</label>
                    <select id="product_id" name="product_id" class="form-select p-3 rounded-3" required onchange="updateStockInfo()">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $product): 
                            $selected = ((isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) || (isset($_GET['product_id']) && $_GET['product_id'] == $product['id'])) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['category'] ?: 'Other'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Stock Info Hook (styled nicely via JS below) -->
                <div class="col-12 my-0" id="stock-info-container"></div>

                <!-- Movement Type -->
                <div class="col-md-6">
                    <label for="movement_type" class="form-label fw-semibold text-secondary">Movement Direction *</label>
                    <select id="movement_type" name="movement_type" class="form-select p-3 rounded-3" required onchange="updateReasons()">
                        <option value="">-- Select Direction --</option>
                        <option value="IN" <?php echo (isset($_POST['movement_type']) && $_POST['movement_type'] === 'IN') ? 'selected' : ''; ?>>
                            📥 IN (Incoming / Increase Stock)
                        </option>
                        <option value="OUT" <?php echo (isset($_POST['movement_type']) && $_POST['movement_type'] === 'OUT') ? 'selected' : ''; ?>>
                            📤 OUT (Outgoing / Decrease Stock)
                        </option>
                    </select>
                </div>

                <!-- Quantity -->
                <div class="col-md-6">
                    <label for="quantity" class="form-label fw-semibold text-secondary">Movement Quantity *</label>
                    <input type="number" id="quantity" name="quantity" class="form-control p-3 rounded-3" min="1" required value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" placeholder="e.g. 50">
                </div>

                <!-- Reason -->
                <div class="col-md-6">
                    <label for="reason" class="form-label fw-semibold text-secondary">Reason Category *</label>
                    <select id="reason" name="reason" class="form-select p-3 rounded-3" required>
                        <option value="">-- Select Reason --</option>
                        <?php if (isset($_POST['reason']) && !empty($_POST['reason'])): ?>
                            <option value="<?php echo htmlspecialchars($_POST['reason']); ?>" selected><?php echo htmlspecialchars($_POST['reason']); ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Reference Number -->
                <div class="col-md-6">
                    <label for="reference_no" class="form-label fw-semibold text-secondary">Reference Code / ID</label>
                    <input type="text" id="reference_no" name="reference_no" class="form-control p-3 rounded-3" placeholder="e.g. PO-10023, REF-8812" value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>">
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"><i class="bi bi-check-circle"></i> Commit Stock Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const reasonOptions = {
        'IN': ['Initial Stock', 'Purchase', 'Return', 'Adjustment'],
        'OUT': ['Sale', 'Damage', 'Expired', 'Loss', 'Adjustment']
    };

    function updateReasons() {
        const type = document.getElementById('movement_type').value;
        const reasonSelect = document.getElementById('reason');
        const currentReason = '<?php echo htmlspecialchars($_POST["reason"] ?? ""); ?>';
        
        reasonSelect.innerHTML = '<option value="">-- Select Reason --</option>';
        
        if (type && reasonOptions[type]) {
            reasonOptions[type].forEach(reason => {
                const selected = (reason === currentReason) ? 'selected' : '';
                reasonSelect.innerHTML += `<option value="${reason}" ${selected}>${reason}</option>`;
            });
        }
    }

    function updateStockInfo() {
        const productSelect = document.getElementById('product_id');
        const productId = productSelect.value;
        const stockContainer = document.getElementById('stock-info-container');

        if (!productId) {
            stockContainer.innerHTML = '';
            return;
        }

        // Fetch stock level via helper
        fetch('../config/stock_helper.php?action=get_stock&id=' + productId)
            .then(response => response.json())
            .then(data => {
                const status = data.stock == 0 ? 'Out of Stock' : (data.stock < 5 ? 'Low Stock' : 'Available');
                const badgeClass = data.stock == 0 ? 'bg-danger text-white' : (data.stock < 5 ? 'bg-warning text-dark' : 'bg-success text-white');
                stockContainer.innerHTML = `
                    <div class="alert alert-light border shadow-sm rounded-3 p-3 my-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">CURRENT ON-HAND STOCK:</span>
                            <span class="fw-bold fs-6 text-dark ms-2">${data.stock} units</span>
                        </div>
                        <span class="badge ${badgeClass} px-3 py-2 rounded-pill fw-semibold" style="font-size: 11px;">${status}</span>
                    </div>
                `;
            })
            .catch(error => console.error('Error fetching stock:', error));
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateReasons();
        if (document.getElementById('product_id').value) {
            updateStockInfo();
        }
    });
</script>

<?php
require_once '../includes/layout-end.php';
?>
