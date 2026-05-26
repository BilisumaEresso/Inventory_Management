<?php
/**
 * Top Navbar Component – Smart Inventory Management System (SIMS)
 * Upgraded: context‑aware search bar + clickable user profile with details
 */
$prefix = isset($path_prefix) ? $path_prefix : '';
$username = $_SESSION['username'] ?? 'Admin';
$fullname = $_SESSION['fullname'] ?? $username;
$user_role = $_SESSION['role'] ?? 'User';
$user_initial = strtoupper(substr($username, 0, 1));

// ---------- CONTEXT DETECTION ----------
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Determine search target based on current page/directory
if ($current_dir === 'suppliers' || $current_page === 'list.php' && $current_dir === 'suppliers') {
    $search_action = $prefix . 'suppliers/list.php';
    $search_placeholder = 'Search suppliers…';
    $search_param = 'search';
} elseif ($current_dir === 'categories') {
    $search_action = $prefix . 'categories/list.php';
    $search_placeholder = 'Search categories…';
    $search_param = 'search';
} elseif ($current_page === 'dashboard.php') {
    // Dashboard – search all products (most useful global action)
    $search_action = $prefix . 'products/list.php';
    $search_placeholder = 'Search all products…';
    $search_param = 'search';
} else {
    // Fallback: search products
    $search_action = $prefix . 'products/list.php';
    $search_placeholder = 'Search products…';
    $search_param = 'search';
}

// Get unread notification count (if DB and helper available)
$unread_count = 0;
$recent_notifications = [];
if (file_exists($prefix . 'config/notification_helper.php')) {
    require_once $prefix . 'config/notification_helper.php';
    global $pdo;
    if (isset($pdo)) {
        $unread_count = getUnreadCount($pdo);
        $recent_notifications = getRecentNotifications($pdo, 6);
    }
}

// Determine base path for API calls
$base_path = $prefix ?: './';
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <!-- Sidebar Toggle for Mobile -->
        <button class="btn btn-link d-lg-none p-0 text-dark me-2" id="sidebarToggle" style="box-shadow: none !important;">
            <i class="bi bi-list fs-3 text-dark"></i>
        </button>

        <!-- CONTEXT‑AWARE SEARCH BAR -->
        <form action="<?php echo htmlspecialchars($search_action); ?>" method="GET" class="d-none d-md-flex align-items-center px-3 py-2 rounded-3" style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 440px; transition: all 0.2s;">
            <i class="bi bi-search text-muted me-2" style="font-size: 15px;"></i>
            <input type="text" name="<?php echo $search_param; ?>" class="border-0 bg-transparent text-dark w-100" placeholder="<?php echo htmlspecialchars($search_placeholder); ?>" style="outline: none; font-size: 13.5px; font-weight: 500;" value="">
        </form>
    </div>

    <div class="navbar-right">
        <!-- Notification Bell with Dropdown (unchanged) -->
        <div class="dropdown">
            <button class="btn btn-link p-0 text-decoration-none" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none;">
                <div class="position-relative d-flex align-items-center justify-content-center p-2 rounded-circle notification-bell"
                     style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 38px; height: 38px; transition: all 0.2s; cursor: pointer;">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="font-size: 10px; padding: 3px 6px; margin-top: 6px; margin-left: -8px;">
                            <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                        </span>
                    <?php else: ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle" id="notificationDot" style="margin-top: 8px; margin-left: -8px; opacity: 0.4;"></span>
                    <?php endif; ?>
                </div>
            </button>

            <!-- Notification Dropdown (unchanged) -->
            <div class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-0 mt-2" style="width: 360px; max-height: 420px; overflow: hidden;" aria-labelledby="notificationDropdown">
                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom" style="border-color: var(--navbar-border) !important;">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 14px;">
                        <i class="bi bi-bell me-2"></i>Notifications
                    </h6>
                    <?php if ($unread_count > 0): ?>
                        <a href="#" class="text-muted small text-decoration-none" onclick="markAllRead(); return false;" style="font-size: 11px; cursor: pointer;">
                            <i class="bi bi-check-all me-1"></i>Mark all read
                        </a>
                    <?php endif; ?>
                </div>

                <div style="max-height: 350px; overflow-y: auto;" id="notificationList">
                    <?php if (empty($recent_notifications)): ?>
                        <div class="text-center py-4 px-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px; background: rgba(0,0,0,0.03);">
                                <i class="bi bi-bell-slash text-muted"></i>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 13px;">No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_notifications as $notif):
                            $icon = 'bi-info-circle text-info';
                            $bg_color = 'rgba(13,202,240,0.08)';
                            if ($notif['type'] === 'warning' || $notif['type'] === 'low_stock') {
                                $icon = 'bi-exclamation-triangle text-warning';
                                $bg_color = 'rgba(255,152,0,0.08)';
                            } elseif ($notif['type'] === 'danger' || $notif['type'] === 'out_of_stock') {
                                $icon = 'bi-x-circle text-danger';
                                $bg_color = 'rgba(220,53,69,0.08)';
                            } elseif ($notif['type'] === 'success') {
                                $icon = 'bi-check-circle text-success';
                                $bg_color = 'rgba(25,135,84,0.08)';
                            }
                            $is_read = (int)$notif['is_read'];

                            // Fix relative links with prefix
                            $link = $notif['link'] ?: '#';
                            if (!preg_match('/^(https?:|\/)/i', $link)) {
                                $link = $prefix . $link;
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($link); ?>"
                               class="dropdown-item d-flex align-items-start gap-3 px-3 py-2.5 border-bottom notification-item <?php echo $is_read ? 'opacity-75' : ''; ?>"
                               style="border-color: var(--navbar-border) !important; text-decoration: none; transition: background 0.15s;"
                               onclick="markRead(<?php echo $notif['id']; ?>)">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5" style="width: 32px; height: 32px; background: <?php echo $bg_color; ?>;">
                                    <i class="bi <?php echo $icon; ?> fs-6"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-semibold text-dark mb-0.5" style="font-size: 13px;"><?php echo htmlspecialchars($notif['title']); ?></div>
                                    <div class="text-muted" style="font-size: 11.5px; line-height: 1.3;"><?php echo htmlspecialchars($notif['message']); ?></div>
                                    <small class="text-muted mt-1 d-block" style="font-size: 10px;">
                                        <?php
                                            $time = strtotime($notif['created_at']);
                                            $diff = time() - $time;
                                            if ($diff < 60) echo 'Just now';
                                            elseif ($diff < 3600) echo floor($diff/60) . 'm ago';
                                            elseif ($diff < 86400) echo floor($diff/3600) . 'h ago';
                                            else echo date('M j', $time);
                                        ?>
                                    </small>
                                </div>
                                <?php if (!$is_read): ?>
                                    <span class="badge bg-primary rounded-pill flex-shrink-0 mt-2" style="width: 6px; height: 6px; padding: 0;"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Theme Toggle (unchanged) -->
        <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center p-2 rounded-circle" id="globalThemeToggle" title="Toggle Theme" style="background-color: var(--input-bg); border: 1px solid var(--navbar-border); width: 38px; height: 38px; transition: all 0.2s; cursor: pointer;">
            <i class="bi bi-sun-fill fs-6" id="globalThemeIcon" style="color: #fb8c00;"></i>
        </div>

        <!-- CLICKABLE User Profile with Dropdown -->
        <div class="dropdown">
            <a class="user-profile text-decoration-none dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none;">
                <img id="user-avatar-img" src="https://plus.unsplash.com/premium_vector-1727953895370-731b77162e13?q=80&w=880&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="avatar shadow-sm" alt="User Profile" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;" onerror="this.style.display='none'; document.getElementById('user-avatar-fallback').style.display='flex';">
                <div id="user-avatar-fallback" class="avatar shadow-sm" style="display: none; width: 38px; height: 38px; border-radius: 50%; font-weight: 700; align-items: center; justify-content: center;"><?php echo $user_initial; ?></div>
                <span class="d-none d-md-inline ms-2 fw-semibold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($fullname); ?></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-2 mt-2" style="min-width: 220px;" aria-labelledby="userDropdown">
                <li class="px-3 py-2">
                    <div class="fw-bold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="text-muted" style="font-size: 12px;">@<?php echo htmlspecialchars($username); ?></div>
                </li>
                <li><hr class="dropdown-divider my-1" style="border-color: var(--navbar-border);"></li>
                <li><span class="dropdown-item-text text-muted px-3 py-1" style="font-size: 12px;">Role: <?php echo ucfirst($user_role); ?></span></li>
                <li><hr class="dropdown-divider my-1" style="border-color: var(--navbar-border);"></li>
                <li><a class="dropdown-item py-2 rounded-2 text-danger d-flex align-items-center gap-2" href="<?php echo $prefix; ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Notification Scripts (unchanged) -->
<script>
    function markRead(id) {
        fetch('<?php echo $base_path; ?>api/mark-notification-read.php?id=' + id, { method: 'POST' })
            .catch(e => console.error('Mark read failed:', e));
    }

    function markAllRead() {
        fetch('<?php echo $base_path; ?>api/mark-all-read.php', { method: 'POST' })
            .then(() => {
                document.querySelectorAll('.notification-item .badge.bg-primary.rounded-pill').forEach(b => b.remove());
                document.querySelectorAll('.notification-item').forEach(i => i.classList.add('opacity-75'));
                const badge = document.getElementById('notificationBadge');
                if (badge) badge.remove();
                const dot = document.getElementById('notificationDot');
                if (dot) dot.style.opacity = '0.4';
            })
            .catch(e => console.error('Mark all read failed:', e));
    }

    setInterval(() => {
        fetch('<?php echo $base_path; ?>api/unread-count.php')
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('notificationBadge');
                const dot = document.getElementById('notificationDot');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                    } else if (dot) {
                        dot.outerHTML = '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="font-size: 10px; padding: 3px 6px; margin-top: 6px; margin-left: -8px;">' + (data.count > 99 ? '99+' : data.count) + '</span>';
                    }
                }
            })
            .catch(() => {});
    }, 60000);
</script>

<!-- Theme Toggle Script (unchanged) -->
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

        const activeTheme = htmlEl.getAttribute('data-bs-theme') || 'light';
        updateIcon(activeTheme);

        toggleBtn.addEventListener('click', () => {
            const current = htmlEl.getAttribute('data-bs-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            htmlEl.setAttribute('data-bs-theme', target);
            localStorage.setItem('theme', target);
            updateIcon(target);

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