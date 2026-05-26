<?php
/**
 * User Approvals & Management - Smart Inventory Management System (SIMS)
 * Open to all authenticated users. (Admin restriction removed.)
 * Updated: Notifications for user status changes.
 */
$path_prefix = '../';
$page_title = 'User Approvals';

require_once '../middleware/auth.php';
require_once '../config/db.php';
require_once '../config/notification_helper.php';

// Removed admin-only check – all authenticated users can now access this page.

// Handle approval/ban actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Prevent users from modifying their own status
    if ($target_user_id === intval($_SESSION['user_id'])) {
        $_SESSION['flash_msg'] = "Error: You cannot modify your own status.";
        $_SESSION['flash_type'] = "danger";
        header('Location: manage.php');
        exit;
    }

    if ($target_user_id > 0) {
        // Fetch user info for notification message
        $stmt_user = $pdo->prepare("SELECT username, fullname FROM users WHERE id = ?");
        $stmt_user->execute([$target_user_id]);
        $target_user = $stmt_user->fetch();
        $target_name = $target_user ? ($target_user['fullname'] ?: $target_user['username']) : 'Unknown User';

        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'APPROVED' WHERE id = ?");
                $stmt->execute([$target_user_id]);
                $_SESSION['flash_msg'] = "User approved successfully! They can now access the system.";
                $_SESSION['flash_type'] = "success";
                createNotification($pdo, 'User Approved', $target_name . ' has been approved and can now log in.', 'success', 'manage.php');
            } elseif ($action === 'decline' || $action === 'ban') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'BANNED' WHERE id = ?");
                $stmt->execute([$target_user_id]);
                $_SESSION['flash_msg'] = "User declined/banned successfully. Their session has been terminated.";
                $_SESSION['flash_type'] = "warning";
                createNotification($pdo, 'User Banned', $target_name . ' has been declined / banned.', 'danger', 'manage.php');
            }
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Database error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
    header('Location: manage.php');
    exit;
}

// Fetch users with filters
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT id, username, email, fullname, status FROM users";
$params = [];
$conditions = [];

// Apply status filters
if ($filter === 'pending') {
    $conditions[] = "status = 'PENDING'";
} elseif ($filter === 'approved') {
    $conditions[] = "status = 'APPROVED'";
} elseif ($filter === 'banned') {
    $conditions[] = "status = 'BANNED' OR status = 'DECLINED'";
}

// Apply search filter
if (!empty($search)) {
    $conditions[] = "(username LIKE ? OR email LIKE ? OR fullname LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Order by ID (newest first)
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users_list = $stmt->fetchAll();

// Get counts for tabs
$count_pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'PENDING'")->fetchColumn();
$count_approved = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'APPROVED'")->fetchColumn();
$count_banned = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'BANNED' OR status = 'DECLINED'")->fetchColumn();

// Include layout header
require_once '../includes/layout-start.php';
?>

<style>
    .user-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        padding: 12px 16px;
        background: var(--card-bg);
        border-color: var(--navbar-border);
    }
    .user-table td {
        font-size: 13.5px;
        padding: 13px 16px;
        border-color: var(--navbar-border);
        vertical-align: middle;
        color: var(--text-dark);
    }
    .user-table tbody tr:hover { background: rgba(19,102,217,.025); }
    .status-badge { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; white-space: nowrap; }
    .status-approved { background: rgba(85,179,138,.12); color: #2d7a5a; }
    .status-pending { background: rgba(226,135,67,.12); color: #b56a1a; }
    .status-banned { background: rgba(243,85,136,.10); color: #c0294e; }
    .filter-tab { font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 20px; color: var(--text-muted); text-decoration: none; transition: .2s; }
    .filter-tab:hover { background: rgba(19,102,217,.05); color: #1366d9; }
    .filter-tab.active { background: #1366d9; color: #fff; }
</style>

<!-- Action Status Message Toast banner -->
<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type'] === 'danger' ? 'danger' : 'success'; ?> d-flex align-items-center gap-2 border-0 rounded-3 p-3 shadow-sm mb-4"
         style="background: <?php echo $_SESSION['flash_type'] === 'danger' ? 'rgba(243,85,136,.1)' : 'rgba(85,179,138,.1)'; ?>;
                color: <?php echo $_SESSION['flash_type'] === 'danger' ? '#c0294e' : '#2d7a5a'; ?>;">
        <i class="bi bi-<?php echo $_SESSION['flash_type'] === 'danger' ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> fs-5"></i>
        <div style="font-size: 13.5px; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['flash_msg']); ?></div>
    </div>
<?php endif; ?>

<!-- Main Card -->
<div class="card border-0 shadow-sm rounded-4 p-0 overflow-hidden">

    <!-- Header row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center px-4 pt-4 pb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark" style="font-family:'Inter',sans-serif;font-size:18px;">User Approvals</h5>
            <p class="text-muted mb-0" style="font-size:12px;">Manage system access and review pending registrations.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-warning-subtle text-dark py-2 px-3 rounded-pill fw-bold" style="font-size: 12px; border: 1px solid #ffc107;">
                <i class="bi bi-clock-history me-1"></i> <?php echo $count_pending; ?> Pending
            </span>
            <span class="badge bg-success-subtle text-success py-2 px-3 rounded-pill fw-bold" style="font-size: 12px; border: 1px solid #198754;">
                <i class="bi bi-check-circle-fill me-1"></i> <?php echo $count_approved; ?> Approved
            </span>
        </div>
    </div>

    <!-- Filters & Tabs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center px-4 pb-3 gap-3" style="border-bottom: 1px solid var(--navbar-border);">
        <div class="d-flex flex-wrap gap-2">
            <a href="manage.php?filter=all&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Users</a>
            <a href="manage.php?filter=pending&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="manage.php?filter=approved&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="manage.php?filter=banned&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'banned' ? 'active' : ''; ?>">Banned</a>
        </div>

        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Search user..." value="<?php echo htmlspecialchars($search); ?>" style="font-size: 13px;">
            </div>
            <?php if (!empty($search)): ?>
                <a href="manage.php?filter=<?php echo htmlspecialchars($filter); ?>" class="btn btn-outline-secondary rounded-3 px-3"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table user-table align-middle mb-0">
            <thead>
                <tr>
                    <th>User Details</th>
                    <th class="text-center">Status</th>
                    <th class="text-end" style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users_list)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 text-primary" style="width:54px;height:54px;background:rgba(19,102,217,.08);">
                                <i class="bi bi-people fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">No users found</h6>
                            <p class="text-muted mb-0" style="font-size: 13px;">No accounts match the current filter.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users_list as $row): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width:40px;height:40px;background:rgba(19,102,217,.1);font-weight:700;font-size:16px;">
                                        <?php echo strtoupper(substr($row['fullname'] ?: $row['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($row['fullname'] ?: '@'.$row['username']); ?></h6>
                                        <small class="text-muted d-block" style="font-size: 12px;"><?php echo htmlspecialchars($row['email'] ?: 'No email'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($row['status'] === 'APPROVED'): ?>
                                    <span class="status-badge status-approved"><i class="bi bi-check-circle-fill me-1"></i> Approved</span>
                                <?php elseif ($row['status'] === 'PENDING'): ?>
                                    <span class="status-badge status-pending"><i class="bi bi-clock-fill me-1"></i> Pending</span>
                                <?php elseif ($row['status'] === 'BANNED' || $row['status'] === 'DECLINED'): ?>
                                    <span class="status-badge status-banned"><i class="bi bi-slash-circle-fill me-1"></i> Banned</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if ($row['status'] === 'PENDING'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:11.5px;font-weight:600;">Approve</button>
                                        </form>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:11.5px;font-weight:600;">Decline</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'APPROVED'): ?>
                                        <?php if ($row['username'] !== 'admin'): ?>
                                            <form method="POST" class="m-0" onsubmit="return confirm('Ban @<?php echo htmlspecialchars($row['username']); ?>?');">
                                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="ban">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:11.5px;font-weight:600;">Ban Account</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3 py-1 rounded-pill" style="font-size:11px;">Admin</span>
                                        <?php endif; ?>
                                    <?php elseif ($row['status'] === 'BANNED' || $row['status'] === 'DECLINED'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:11.5px;font-weight:600;">Unban</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Clear flash messages after displaying
if (isset($_SESSION['flash_msg'])) {
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}
require_once '../includes/layout-end.php';
?>