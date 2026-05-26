<?php
require_once __DIR__ . '/../middleware/auth.php';
// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $status = 'active'; // auto-activate when created by admin

    // Check if username exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $error = "Username already exists.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $password, $role, $status])) {
            logActivity($pdo, $_SESSION['user_id'], "Created user", ['username' => $username, 'role' => $role]);
            $success = "User created successfully.";
        } else {
            $error = "Failed to create user.";
        }
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $user_id])) {
        logActivity($pdo, $_SESSION['user_id'], "Updated user role", ['user_id' => $user_id, 'new_role' => $new_role]);
        $success = "Role updated.";
    } else {
        $error = "Failed to update role.";
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id != $_SESSION['user_id']) { // prevent self-deletion
        // Get username before deleting for log
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $deleted_user = $stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            logActivity($pdo, $_SESSION['user_id'], "Deleted user", ['user_id' => $delete_id, 'username' => $deleted_user['username']]);
            $success = "User deleted.";
        } else {
            $error = "Failed to delete user.";
        }
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, role, status, created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - SIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container mt-4">
    <h2>User Management</h2>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="card mb-4">
        <div class="card-header">Add New User</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Role</label>
                    <select name="role" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="clerk">Clerk</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">Existing Users</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role" onchange="this.form.submit()" class="form-select form-select-sm" style="width: auto;">
                                    <option value="admin" <?= $user['role']=='admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="manager" <?= $user['role']=='manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="clerk" <?= $user['role']=='clerk' ? 'selected' : '' ?>>Clerk</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td><?= $user['status'] ?></td>
                        <td><?= $user['created_at'] ?></td>
                        <td>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>