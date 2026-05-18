<?php
/**
 * Suppliers List - Smart Inventory Management System (SIMS)
 * Figma-matching design: Supplier Name | Product | Contact | Email | Type | On the way
 * No DB schema changes - uses existing suppliers + products + stock_movements tables.
 */
require_once '../middleware/auth.php';
require_once '../config/db.php';

$search   = trim($_GET['search']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$suppliers    = [];
$total_rows   = 0;
$total_pages  = 1;

try {
    // Count for pagination
    $count_sql = "SELECT COUNT(*) FROM suppliers s";
    $count_params = [];
    if ($search) {
        $count_sql  .= " WHERE s.name LIKE ?";
        $count_params[] = '%' . $search . '%';
    }
    $total_rows  = (int)$pdo->prepare($count_sql)->execute($count_params)
                    ? ($pdo->prepare($count_sql) && ($cs = $pdo->prepare($count_sql)) && $cs->execute($count_params) ? (int)$cs->fetchColumn() : 0)
                    : 0;

    // Cleaner count fetch
    $cs = $pdo->prepare($count_sql);
    $cs->execute($count_params);
    $total_rows  = (int)$cs->fetchColumn();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
    $offset      = ($page - 1) * $per_page;

    // Main query: supplier + first linked product name + on-the-way (recent IN movements in last 30d)
    $sql = "
        SELECT
            s.*,
            (SELECT p.name FROM products p WHERE p.supplier_id = s.id ORDER BY p.id LIMIT 1) AS product_name,
            (
                SELECT COALESCE(SUM(sm.quantity), 0)
                FROM stock_movements sm
                JOIN products p2 ON sm.product_id = p2.id
                WHERE p2.supplier_id = s.id
                  AND sm.movement_type = 'IN'
                  AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) AS on_the_way
        FROM suppliers s
    ";
    $params = [];
    if ($search) {
        $sql   .= " WHERE s.name LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $sql .= " ORDER BY s.created_at DESC LIMIT $per_page OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Suppliers List Error: ' . $e->getMessage());
}

// "Type" is derived: if the supplier has any return-tagged OUT movements → "Taking Return" (green),
// default all to "Taking Return" since no DB column exists (no schema change).
// We mark "Not Taking Return" only if supplier has had no IN movements at all (never restocked us).
// ────────────────────────────────────────────────────────────────────────────────────────────────

// Download CSV
if (isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="suppliers_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Supplier Name', 'Product', 'Contact Number', 'Email', 'Type', 'On The Way']);
    foreach ($suppliers as $s) {
        $type = ((int)$s['on_the_way'] > 0) ? 'Taking Return' : 'Not Taking Return';
        fputcsv($out, [
            $s['name'],
            $s['product_name'] ?? '—',
            $s['phone'] ?? '—',
            $s['email'] ?? '—',
            $type,
            $s['on_the_way'] > 0 ? $s['on_the_way'] : '-'
        ]);
    }
    fclose($out);
    exit;
}

$page_title  = 'Suppliers';
$path_prefix = '../';
require_once '../includes/layout-start.php';
?>

<style>
    .sup-table th {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        padding: 12px 16px;
        background: var(--card-bg);
        border-color: var(--navbar-border);
    }
    .sup-table td {
        font-size: 13.5px;
        padding: 13px 16px;
        border-color: var(--navbar-border);
        vertical-align: middle;
        color: var(--text-dark);
    }
    .sup-table tbody tr:hover { background: rgba(19,102,217,.025); }
    .type-taking     { color: #55b38a; font-weight: 700; font-size: 13px; }
    .type-not-taking { color: #f35588; font-weight: 700; font-size: 13px; }
    .page-btn {
        border: 1px solid var(--navbar-border);
        background: var(--card-bg);
        color: var(--text-dark);
        font-size: 13px;
        font-weight: 600;
        padding: 7px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }
    .page-btn:hover { background: rgba(19,102,217,.06); color: #1366d9; }
    .page-btn.disabled { opacity: .4; pointer-events: none; }
</style>

<!-- ═══════════════════════════════════════
     SUPPLIERS TABLE CARD
═══════════════════════════════════════════ -->
<div class="card border-0 shadow-sm rounded-4 p-0 overflow-hidden">

    <!-- Header row inside card -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center px-4 pt-4 pb-3 gap-3">
        <h5 class="fw-bold mb-0 text-dark" style="font-family:'Inter',sans-serif; font-size:18px;">Suppliers</h5>
        <div class="d-flex align-items-center gap-2">
            <!-- Add Supplier -->
            <a href="add.php" class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-1" style="font-weight:600; font-size:13px;">
                Add Supplier
            </a>
            <!-- Filters toggle -->
            <button class="btn btn-outline-secondary px-3 py-2 rounded-3 d-flex align-items-center gap-1 bg-white border shadow-sm" style="font-weight:600; font-size:13px;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-funnel"></i> Filters
            </button>
            <!-- Download -->
            <a href="?<?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>download=1"
               class="btn btn-outline-secondary px-3 py-2 rounded-3 bg-white border shadow-sm" style="font-weight:600; font-size:13px;">
                Download all
            </a>
        </div>
    </div>

    <!-- Collapsible Search Filter -->
    <div class="collapse px-4 pb-3" id="filterCollapse">
        <form method="GET" class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by supplier name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary px-4 rounded-3">Search</button>
            <?php if ($search): ?>
                <a href="list.php" class="btn btn-outline-secondary rounded-3"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($suppliers)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-4 text-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 text-primary"
                 style="width:54px;height:54px;background:rgba(19,102,217,.08);">
                <i class="bi bi-people fs-4"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">No suppliers found</h6>
            <p class="text-muted mb-0" style="font-size:13px;">
                <?php echo $search ? 'Try a different search term.' : 'Add your first supplier to get started.'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table sup-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Product</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th class="text-center">On the way</th>
                        <th class="text-end" style="width:110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s):
                        $on_way = (int)$s['on_the_way'];
                        // Derive "Type": has had recent inbound = Taking Return (active restocking relationship)
                        $taking = $on_way > 0;
                        // Fallback: check if they have any movements at all
                        if (!$taking) {
                            try {
                                $chk = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm JOIN products p ON sm.product_id=p.id WHERE p.supplier_id=? AND sm.movement_type='IN' LIMIT 1");
                                $chk->execute([$s['id']]);
                                $taking = (int)$chk->fetchColumn() > 0;
                            } catch (Exception $e) { $taking = true; }
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="view.php?id=<?php echo $s['id']; ?>" class="fw-semibold text-dark text-decoration-none">
                                <?php echo htmlspecialchars($s['name']); ?>
                            </a>
                            <?php if (!empty($s['address'])): ?>
                                <div class="text-muted" style="font-size:11px;"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($s['address']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo htmlspecialchars($s['product_name'] ?? '—'); ?>
                        </td>
                        <td class="text-dark">
                            <?php echo htmlspecialchars($s['phone'] ?: '—'); ?>
                        </td>
                        <td>
                            <?php if (!empty($s['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" class="text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($s['email']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <?php if ($taking): ?>
                                <span class="type-taking">Taking Return</span>
                            <?php else: ?>
                                <span class="type-not-taking">Not Taking Return</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-semibold text-dark">
                            <?php echo $on_way > 0 ? $on_way : '<span class="text-muted">-</span>'; ?>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 dropdown-toggle shadow-none"
                                        type="button" data-bs-toggle="dropdown" style="font-size:12px;font-weight:500;">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="font-size:13px;min-width:150px;">
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-primary d-flex align-items-center gap-2"
                                           href="view.php?id=<?php echo $s['id']; ?>">
                                            <i class="bi bi-person-badge"></i> View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-dark d-flex align-items-center gap-2"
                                           href="edit.php?id=<?php echo $s['id']; ?>">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1 border-light"></li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-2 text-danger d-flex align-items-center gap-2" href="#"
                                           onclick="triggerGlobalDeleteModal('delete.php?id=<?php echo $s['id']; ?>', 'Delete this supplier? Their products will keep their history.'); return false;">
                                            <i class="bi bi-trash-fill"></i> Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top: 1px solid var(--navbar-border);">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">Previous</a>
            <?php else: ?>
                <span class="page-btn disabled">Previous</span>
            <?php endif; ?>

            <span class="text-muted fw-semibold" style="font-size:13px;">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </span>

            <?php if ($page < $total_pages): ?>
                <a class="page-btn" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">Next</a>
            <?php else: ?>
                <span class="page-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/layout-end.php'; ?>
