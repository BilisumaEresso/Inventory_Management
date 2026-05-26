<?php
require_once __DIR__ . '/../middleware/auth.php';
// Only admins can view activity log
if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

require_once __DIR__ . '/../config/db.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filter by action type
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$count_query = "SELECT COUNT(*) as total FROM activity_log al JOIN users u ON al.user_id = u.id";
$data_query = "SELECT al.*, u.username FROM activity_log al JOIN users u ON al.user_id = u.id";

if (!empty($filter)) {
    $count_query .= " WHERE al.action LIKE :filter";
    $data_query .= " WHERE al.action LIKE :filter";
    $data_query .= " ORDER BY al.created_at DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($count_query);
    $stmt->execute(['filter' => "%$filter%"]);
    $total = $stmt->fetch()['total'];

    $stmt = $pdo->prepare($data_query);
    $stmt->execute(['filter' => "%$filter%"]);
    $logs = $stmt->fetchAll();
} else {
    $total = $pdo->query($count_query)->fetch()['total'];
    $logs = $pdo->query($data_query . " ORDER BY al.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
}

$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log - SIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container mt-4">
    <h2>Activity Log</h2>

    <!-- Filter Form -->
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="filter" class="form-control" placeholder="Filter by action..." value="<?= htmlspecialchars($filter) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="activity-log.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Log Table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td>
                            <?php
                            if (!empty($log['details'])) {
                                $details = json_decode($log['details'], true);
                                if ($details) {
                                    echo '<pre style="margin:0; font-size:12px;">' . htmlspecialchars(print_r($details, true)) . '</pre>';
                                } else {
                                    echo htmlspecialchars($log['details']);
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= $log['ip_address'] ?></td>
                        <td><?= $log['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No activity logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>