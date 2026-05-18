<?php
/**
 * Settings Page - Smart Inventory Management System (SIMS)
 * Allows users to update their profile and account details.
 */
$path_prefix = '../';
$page_title = 'Settings';

require_once '../middleware/auth.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$success_msg = '';
$error_msg = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT username, email, fullname FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');

        try {
            $update = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $update->execute([$fullname, $email, $user_id]);
            
            // Update session if needed
            $_SESSION['fullname'] = $fullname;
            $user['fullname'] = $fullname;
            $user['email'] = $email;
            
            $success_msg = "Profile updated successfully.";
        } catch (PDOException $e) {
            $error_msg = "Error updating profile. Email might already be in use.";
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || $new_password !== $confirm_password) {
            $error_msg = "New passwords do not match.";
        } else {
            // Verify current password
            $check = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $check->execute([$user_id]);
            $hash = $check->fetchColumn();

            if (password_verify($current_password, $hash)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $user_id]);
                $success_msg = "Password changed successfully.";
            } else {
                $error_msg = "Current password is incorrect.";
            }
        }
    }
}

// Include layout header
require_once '../includes/layout-start.php';
?>

<!-- Header -->
<div class="mb-4">
    <h5 class="fw-bold mb-0 text-dark" style="font-family:'Inter',sans-serif;font-size:18px;">Account Settings</h5>
    <p class="text-muted mb-0" style="font-size:12px;">Manage your profile and security preferences.</p>
</div>

<?php if ($error_msg): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 border-0 rounded-3 p-3 shadow-sm mb-4" style="background: rgba(243,85,136,.1); color: #c0294e;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div style="font-size: 13.5px; font-weight: 500;"><?php echo htmlspecialchars($error_msg); ?></div>
    </div>
<?php endif; ?>

<?php if ($success_msg): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 border-0 rounded-3 p-3 shadow-sm mb-4" style="background: rgba(85,179,138,.1); color: #2d7a5a;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div style="font-size: 13.5px; font-weight: 500;"><?php echo htmlspecialchars($success_msg); ?></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    
    <!-- Profile Information -->
    <div class="col-lg-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-4" style="font-size: 15px; color: var(--text-dark);">
                <i class="bi bi-person-badge text-primary me-2"></i> Profile Information
            </h6>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">Username</label>
                    <input type="text" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px; background: rgba(0,0,0,.02);" value="@<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                    <small class="text-muted" style="font-size: 11px;">Username cannot be changed.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">Full Name</label>
                    <input type="text" name="fullname" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px;" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">Email Address</label>
                    <input type="email" name="email" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold" style="padding:12px; font-size:13.5px;">
                    Save Profile Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="col-lg-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-4" style="font-size: 15px; color: var(--text-dark);">
                <i class="bi bi-shield-lock text-primary me-2"></i> Security Settings
            </h6>

            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">Current Password</label>
                    <input type="password" name="current_password" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px;" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">New Password</label>
                    <input type="password" name="new_password" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px;" required minlength="6">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary" style="font-size: 12.5px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control rounded-3" style="padding:12px 16px; font-size:13.5px;" required minlength="6">
                </div>

                <button type="submit" class="btn btn-outline-primary w-100 rounded-3 fw-bold" style="padding:12px; font-size:13.5px;">
                    Update Password
                </button>
            </form>
        </div>
    </div>

</div>

<?php 
require_once '../includes/layout-end.php'; 
?>
