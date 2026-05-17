<?php
$prefix = isset($path_prefix) ? $path_prefix : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$user_initial = strtoupper(substr($username, 0, 1));
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <button class="btn btn-link d-lg-none p-0 text-darkme-2" id="sidebarToggle" style="box-shadow: none !important;">
            <i class="bi bi-list fs-3"></i>
        </button>
        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Smart Inventory'; ?>
        </h5>
    </div>
    
    <div class="navbar-right">
        <!-- Minimal Search Pill -->
        <div class="d-none d-md-flex align-items-center px-3 py-2 rounded-pill" style="background-color: #f1f5f9; width: 260px; transition: all 0.2s;">
            <i class="bi bi-search text-muted me-2" style="font-size: 14px;"></i>
            <input type="text" placeholder="Global search..." class="border-0 bg-transparent text-dark w-100" style="outline: none; font-size: 13px; font-weight: 500;">
        </div>
        
        <!-- Premium Notifications Icon with Pulse -->
        <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center p-2 rounded-circle" style="background-color: #f1f5f9; width: 38px; height: 38px; transition: all 0.2s;">
            <i class="bi bi-bell-fill fs-6 text-secondary"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 6px; margin-left: -6px;"></span>
        </div>
        
        <!-- User Profile Area -->
        <div class="user-profile border-start ps-3" style="border-color: #e2e8f0 !important;">
            <div class="avatar shadow-sm"><?php echo $user_initial; ?></div>
            <div class="d-none d-sm-block">
                <div class="fw-bold mb-0 text-dark" style="font-size: 13px; line-height: 1.2;"><?php echo htmlspecialchars($username); ?></div>
                <span class="text-muted" style="font-size: 10px; font-weight: 500;">Administrator</span>
            </div>
        </div>
    </div>
</nav>
