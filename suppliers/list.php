<?php
require_once '../middleware/auth.php';
require_once '../config/db.php';

$search = trim($_GET['search'] ?? '');
$suppliers = [];

try {
    if ($search) {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE name LIKE ? ORDER BY created_at DESC');
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM suppliers ORDER BY created_at DESC');
        $stmt->execute();
    }
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('List Suppliers Error: ' . $e->getMessage());
}

$page_title = 'Supplier Directory';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">🏢 Supplier Directory</h4>
        <p class="text-muted mb-0" style="font-size: 13px;">Manage product sourcing contacts and vendor relationships.</p>
    </div>
    <div>
        <a href="add.php" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg"></i> Add Supplier
        </a>
    </div>
</div>

<!-- Search & Filters Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-10">
            <label class="form-label fw-semibold text-secondary" style="font-size: 13px;">Search Vendors</label>
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

<!-- Suppliers Table Card -->
<div class="card card-custom border-0 shadow-sm p-4 mb-4">
    <?php if (empty($suppliers)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="avatar shadow-sm bg-light text-muted mb-3" style="width: 50px; height: 50px;"><i class="bi bi-person-lines-fill fs-3"></i></div>
            <h6 class="fw-bold text-secondary mb-1">No suppliers found</h6>
            <p class="text-muted mb-0" style="font-size: 12px;">Try adjusting filters or register a new sourcing vendor.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Supplier Details</th>
                        <th>Email Contact</th>
                        <th>Phone Number</th>
                        <th>Created Date</th>
                        <th class="text-end" style="min-width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td class="text-muted">#<?php echo htmlspecialchars($supplier['id']); ?></td>
                        <td>
                            <strong class="text-dark fs-6 d-block"><?php echo htmlspecialchars($supplier['name']); ?></strong>
                            <?php if (!empty($supplier['address'])): ?>
                                <span class="text-muted" style="font-size: 11px;"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($supplier['address']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($supplier['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-primary fw-medium" style="font-size: 13px;"><?php echo htmlspecialchars($supplier['email']); ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-dark fw-medium" style="font-size: 13px;">
                            <?php echo htmlspecialchars($supplier['phone'] ?: '—'); ?>
                        </td>
                        <td class="text-muted" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($supplier['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="view.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size: 11px; font-weight: 600;">View Profile</a>
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size: 11px; font-weight: 600;">Edit</a>
                                <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size: 11px; font-weight: 600;" onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $supplier['id']; ?>', 'Are you sure you want to delete this supplier? Products linked to this supplier will have their supplier reference set to NULL.'); return false;">Delete</a>
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
