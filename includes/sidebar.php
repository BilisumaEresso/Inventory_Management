<?php
/**
 * Sidebar Component - Smart Inventory Management System (SIMS)
 * Overhauled to perfectly match the Figma design structure and responsive layout
 */
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$prefix = isset($path_prefix) ? $path_prefix : '';
?>
<div class="sidebar">
    <div class="sidebar-header d-flex align-items-center gap-2 px-4 py-4" style="border-bottom: 1px solid var(--navbar-border);">
        <img src="<?php echo $prefix; ?>assets/images/logo.png" alt="SIMS Logo" style="height: 36px; width: auto; object-fit: contain;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">
        <span class="fw-extrabold fs-4 text-dark mb-0" style="font-family: 'Inter', sans-serif; letter-spacing: -1px; font-weight: 800; color: #1366d9 !important;">SIMS</span>
    </div>
    
    <ul class="sidebar-menu">
        <!-- 1. Dashboard -->
        <li class="menu-item <?php echo ($current_page === 'dashboard.php' && !strpos($_SERVER['REQUEST_URI'], '#analytics-section')) ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <!-- 2. Inventory -->
        <li class="menu-item <?php echo ($current_dir === 'products' && $current_page !== 'add.php') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>products/list.php">
                <i class="bi bi-box-fill"></i>
                <span>Inventory</span>
            </a>
        </li>
        
        <!-- 3. Reports -->
        <li class="menu-item <?php echo ($current_dir === 'reports' || $current_page === 'index.php' && $current_dir === 'reports') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>reports/index.php">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Reports</span>
            </a>
        </li>
        
        <!-- 4. Suppliers -->
        <li class="menu-item <?php echo ($current_dir === 'suppliers') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>suppliers/list.php">
                <i class="bi bi-people-fill"></i>
                <span>Suppliers</span>
            </a>
        </li>
        
        <!-- 5. Stock Movements -->
        <li class="menu-item <?php echo ($current_dir === 'inventory' || $current_page === 'history.php') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>inventory/history.php">
                <i class="bi bi-arrow-down-up"></i>
                <span>Stock Movements</span>
            </a>
        </li>
        
        <!-- 6. Manage Category -->
        <li class="menu-item <?php echo ($current_dir === 'categories') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>categories/list.php">
                <i class="bi bi-tags-fill"></i>
                <span>Manage Category</span>
            </a>
        </li>

        <!-- 7. Manage User -->
        <li class="menu-item <?php echo ($current_dir === 'users') ? 'active' : ''; ?>"
            <a href="<?php echo $prefix; ?>users/manage.php">
                <i class="bi bi-person-check-fill"></i>
                <span>Manage User</span>
            </a>
        </li>

        <!-- Admin user approvals center -->
        <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
        <li class="menu-item <?php echo ($current_dir === 'users') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>users/manage.php">
                <i class="bi bi-person-check-fill"></i>
                <span>User Approvals</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Footer menu items: Settings & Logout -->
        <li class="menu-item mt-auto border-top pt-3 <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>" style="border-color: var(--navbar-border) !important;">
            <a href="<?php echo $prefix; ?>settings/index.php" style="color: var(--text-muted);">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?php echo $prefix; ?>auth/logout.php" class="text-danger">
                <i class="bi bi-box-arrow-left text-danger"></i>
                <span>Log Out</span>
            </a>
        </li>
    </ul>
</div>
