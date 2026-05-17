<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$prefix = isset($path_prefix) ? $path_prefix : '';
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="avatar shadow-sm">
            <i class="bi bi-box-seam-fill"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-white">Smart Inventory</h6>
            <span class="text-muted" style="font-size: 11px;">v2.0 Premium</span>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-item <?php echo ($current_page === 'dashboard.php' && !strpos($_SERVER['REQUEST_URI'], '#analytics-section')) ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($current_dir === 'products') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>products/list.php">
                <i class="bi bi-box-fill"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($current_dir === 'inventory') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>inventory/history.php">
                <i class="bi bi-arrow-down-up"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($current_dir === 'categories') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>categories/list.php">
                <i class="bi bi-tags-fill"></i>
                <span>Categories</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($current_dir === 'suppliers') ? 'active' : ''; ?>">
            <a href="<?php echo $prefix; ?>suppliers/list.php">
                <i class="bi bi-building-fill"></i>
                <span>Suppliers</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?php echo $prefix; ?>dashboard.php#analytics-section">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?php echo $prefix; ?>public/live-inventory.php" target="_blank">
                <i class="bi bi-tv-fill"></i>
                <span>Public Dashboard</span>
            </a>
        </li>
        
        <li class="menu-item mt-auto border-top pt-3" style="border-color: rgba(255,255,255,0.05) !important;">
            <a href="<?php echo $prefix; ?>auth/logout.php" class="text-danger">
                <i class="bi bi-door-closed-fill"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
