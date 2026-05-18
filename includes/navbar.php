<?php
/**
 * Top Navbar Component - Smart Inventory Management System (SIMS)
 * Overhauled to perfectly match the Figma layout: left-aligned search input,bell icons, and premium user photo
 */
$prefix = isset($path_prefix) ? $path_prefix : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$user_initial = strtoupper(substr($username, 0, 1));
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <!-- Sidebar Toggle for Mobile -->
        <button class="btn btn-link d-lg-none p-0 text-dark me-2" id="sidebarToggle" style="box-shadow: none !important;">
            <i class="bi bi-list fs-3 text-dark"></i>
        </button>
        
        <!-- Figma-Style Search Input -->
        <div class="d-none d-md-flex align-items-center px-3 py-2 rounded-3" style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 440px; transition: all 0.2s;">
            <i class="bi bi-search text-muted me-2" style="font-size: 15px;"></i>
            <input type="text" placeholder="Search product, supplier, order" class="border-0 bg-transparent text-dark w-100" style="outline: none; font-size: 13.5px; font-weight: 500;">
        </div>
    </div>
    
    <div class="navbar-right">
        <!-- Premium Notifications Icon with Pulse -->
        <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center p-2 rounded-circle" style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 38px; height: 38px; transition: all 0.2s; cursor: pointer;">
            <i class="bi bi-bell fs-5 text-secondary"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 8px; margin-left: -8px;"></span>
        </div>

        <!-- Premium Global Light/Dark Theme Toggle -->
        <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center p-2 rounded-circle" id="globalThemeToggle" title="Toggle Theme" style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 38px; height: 38px; transition: all 0.2s; cursor: pointer;">
            <i class="bi bi-sun-fill fs-6" id="globalThemeIcon" style="color: #fb8c00;"></i>
        </div>
        
        <!-- Figma-Style User Profile Area -->
        <div class="user-profile ps-2">
            <img id="user-avatar-img" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop" class="avatar shadow-sm" alt="User Profile" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;" onerror="this.style.display='none'; document.getElementById('user-avatar-fallback').style.display='flex';">
            <div id="user-avatar-fallback" class="avatar shadow-sm" style="display: none; width: 38px; height: 38px; border-radius: 50%; font-weight: 700; align-items: center; justify-content: center;"><?php echo $user_initial; ?></div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('globalThemeToggle');
        const themeIcon = document.getElementById('globalThemeIcon');
        const htmlEl = document.documentElement;

        function updateIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-moon-fill fs-6';
                themeIcon.style.color = '#f8fafc';
            } else {
                themeIcon.className = 'bi bi-sun-fill fs-6';
                themeIcon.style.color = '#fb8c00';
            }
        }

        // Initialize state
        const activeTheme = htmlEl.getAttribute('data-bs-theme') || 'light';
        updateIcon(activeTheme);

        toggleBtn.addEventListener('click', () => {
            const current = htmlEl.getAttribute('data-bs-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            htmlEl.setAttribute('data-bs-theme', target);
            localStorage.setItem('theme', target);
            updateIcon(target);
            
            // Sync with local page elements if they exist
            const localIcon = document.getElementById('themeIcon');
            if (localIcon) {
                if (target === 'dark') {
                    localIcon.className = 'bi bi-moon-fill';
                    localIcon.style.color = '#f8fafc';
                } else {
                    localIcon.className = 'bi bi-sun-fill';
                    localIcon.style.color = '#fb8c00';
                }
            }
        });
    });
</script>
