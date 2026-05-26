<?php
/**
 * Sidebar Component - Smart Inventory Management System (SIMS)
 * Polished: active accent bar, refined spacing, smoother transitions
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$prefix = isset($path_prefix) ? $path_prefix : '';
$user_role = $_SESSION['role'] ?? '';
?>

<div class="sidebar">

    <!-- Sidebar Header -->
    <div class="sidebar-header d-flex align-items-center gap-2 px-4 py-4"
         style="border-bottom: 1px solid var(--sidebar-border);">

        <img src="<?php echo $prefix; ?>assets/images/logo.png"
             alt="SIMS Logo"
             style="height: 36px; width: auto; object-fit: contain;"
             onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">

        <span class="fw-extrabold fs-4 text-dark mb-0"
              style="font-family: 'Inter', sans-serif; letter-spacing: -1px; font-weight: 800; color: #1366d9 !important;">
            SIMS
        </span>
    </div>

    <!-- Sidebar Menu -->
    <ul class="sidebar-menu d-flex flex-column h-100">

        <!-- Dashboard -->
        <li class="menu-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Inventory -->
        <li class="menu-item <?php echo ($current_dir === 'products') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>products/list.php">
                <i class="bi bi-box-fill"></i>
                <span>Inventory</span>
            </a>
        </li>

        <!-- Reports -->
        <li class="menu-item <?php echo ($current_dir === 'reports') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>reports/index.php">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Reports</span>
            </a>
        </li>

        <!-- Suppliers -->
        <li class="menu-item <?php echo ($current_dir === 'suppliers') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>suppliers/list.php">
                <i class="bi bi-people-fill"></i>
                <span>Suppliers</span>
            </a>
        </li>

        <!-- Stock Movements -->
        <li class="menu-item <?php echo ($current_dir === 'inventory') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>inventory/history.php">
                <i class="bi bi-arrow-down-up"></i>
                <span>Stock Movements</span>
            </a>
        </li>

        <!-- Categories -->
        <li class="menu-item <?php echo ($current_dir === 'categories') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>categories/list.php">
                <i class="bi bi-tags-fill"></i>
                <span>Manage Categories</span>
            </a>
        </li>

        <!-- Manage Users -->
        <li class="menu-item <?php echo ($current_page === 'manage.php' && $current_dir === 'users') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>users/manage.php">
                <i class="bi bi-people-fill"></i>
                <span>Manage Users</span>
            </a>
        </li>

        <!-- Admin Only: User Approvals -->
        <?php if ($user_role === 'admin'): ?>
        <li class="menu-item <?php echo ($current_page === 'approvals.php') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>users/approvals.php">
                <i class="bi bi-person-check-fill"></i>
                <span>User Approvals</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Push Footer Items to Bottom -->
        <div class="mt-auto"></div>

        <!-- Settings -->
        <li class="menu-item border-top pt-3 <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>"
            style="border-color: var(--sidebar-border) !important;">

            <a href="<?php echo $prefix; ?>settings/index.php">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>

        <!-- Logout -->
        <li class="menu-item">
            <a href="<?php echo $prefix; ?>auth/logout.php" class="text-danger">
                <i class="bi bi-box-arrow-left text-danger"></i>
                <span>Log Out</span>
            </a>
        </li>

    </ul>
</div>