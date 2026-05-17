<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error_message = 'Supplier name is required.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO suppliers (name, email, phone, address) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $address]);

            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error_message = 'Error adding supplier. Please try again.';
            error_log('Add Supplier Error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Add Supplier';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏢 Add New Supplier</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Add a new vendor catalog for inventory sourcing audits.</p>
    </div>
    <div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Suppliers
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
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
                    <label for="name" class="form-label fw-semibold text-secondary">Supplier / Company Name *</label>
                    <input type="text" id="name" name="name" class="form-control p-3 rounded-3" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="e.g. Acme Corporation">
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control p-3 rounded-3" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="e.g. contact@acme.com">
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <label for="phone" class="form-label fw-semibold text-secondary">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control p-3 rounded-3" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="e.g. +1 (555) 019-2834">
                </div>

                <!-- Address -->
                <div class="col-12">
                    <label for="address" class="form-label fw-semibold text-secondary">Corporate Address</label>
                    <textarea id="address" name="address" class="form-control p-3 rounded-3" rows="3" placeholder="Enter corporate physical address..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <!-- Submit -->
                <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"><i class="bi bi-plus-lg"></i> Register Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/layout-end.php';
?>
