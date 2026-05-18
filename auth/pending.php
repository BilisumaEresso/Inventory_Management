<?php
/**
 * Pending Approval Screen - Smart Inventory Management System (SIMS)
 * Displays when a user's status is 'PENDING'.
 */
session_start();

// We must include route protection but skip the infinite loop on pending.php itself
// In middleware/auth.php, if status is PENDING and current page is pending.php, it lets them through.
// If status is APPROVED, it redirects them to dashboard.php.
// If status is BANNED/DECLINED, it terminates session and redirects to login.php.
require_once __DIR__ . '/../middleware/auth.php';

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approval Pending - SIMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color-main: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --btn-border: #cbd5e1;
            --btn-hover: #f8fafc;
            --card-border: rgba(0, 0, 0, 0.05);
        }

        [data-bs-theme="dark"] {
            --bg-color-main: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --btn-border: rgba(255, 255, 255, 0.15);
            --btn-hover: rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color-main);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .pending-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .brand-logo {
            width: 72px;
            height: auto;
            margin-bottom: 24px;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.06));
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }

        .description {
            font-size: 14.5px;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* Highlight box */
        .time-badge-box {
            background-color: rgba(13, 110, 253, 0.06);
            border: 1px solid rgba(13, 110, 253, 0.15);
            color: #0d6efd;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 30px;
        }

        [data-bs-theme="dark"] .time-badge-box {
            background-color: rgba(13, 110, 253, 0.15);
            color: #3b82f6;
        }

        /* Buttons */
        .btn-check-status {
            background: #0d6efd !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 14.5px !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2) !important;
            transition: all 0.2s ease-in-out !important;
            width: 100%;
            margin-bottom: 14px;
        }

        .btn-check-status:hover {
            background-color: #0b5ed7 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.3) !important;
        }

        .btn-logout {
            background-color: transparent !important;
            border: 1px solid var(--btn-border) !important;
            color: var(--text-main) !important;
            font-weight: 600 !important;
            font-size: 14.5px !important;
            padding: 11px 24px !important;
            border-radius: 12px !important;
            transition: all 0.2s ease-in-out !important;
            width: 100%;
        }

        .btn-logout:hover {
            background-color: var(--btn-hover) !important;
        }

        /* Floating Theme Toggle */
        .theme-toggle-floating {
            position: absolute;
            top: 24px;
            right: 24px;
            background-color: var(--card-bg);
            border: 1px solid var(--btn-border);
            color: var(--text-main);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }

        .theme-toggle-floating:hover {
            background-color: var(--btn-hover);
            transform: scale(1.05);
        }
    </style>
</head>
<body>

    <div class="pending-card">
        
        <!-- Theme Toggle -->
        <button class="theme-toggle-floating" id="themeBtn" title="Toggle Theme">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
        </button>

        <!-- Brand Icon -->
        <img src="../assets/images/logo.png" class="brand-logo" alt="SIMS Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">

        <h3 class="title">Approval Pending</h3>
        <p class="description">
            Hello <strong><?php echo htmlspecialchars($username); ?></strong>! Your account was registered successfully and is currently waiting for administrator verification.
        </p>

        <!-- Dynamic Indicator -->
        <div class="time-badge-box">
            <i class="bi bi-clock-history fs-5 animate-pulse"></i>
            <span>Maximum wait time: 24 hours</span>
        </div>

        <!-- Action triggers -->
        <button type="button" class="btn btn-check-status" id="checkStatusBtn">
            <i class="bi bi-arrow-repeat me-1" id="refreshIcon"></i> Check Status
        </button>

        <a href="logout.php" class="btn btn-logout">
            <i class="bi bi-box-arrow-right me-1"></i> Log Out
        </a>

    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Theme System and Check State Script -->
    <script>
        // Light & Dark theme toggle
        const htmlEl = document.documentElement;
        const themeBtn = document.getElementById('themeBtn');
        const themeIcon = document.getElementById('themeIcon');

        const currentTheme = localStorage.getItem('theme') || 'light';
        htmlEl.setAttribute('data-bs-theme', currentTheme);
        updateThemeUI(currentTheme);

        themeBtn.addEventListener('click', () => {
            const current = htmlEl.getAttribute('data-bs-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            htmlEl.setAttribute('data-bs-theme', target);
            localStorage.setItem('theme', target);
            updateThemeUI(target);
        });

        function updateThemeUI(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-moon-fill';
                themeIcon.style.color = '#f8fafc';
            } else {
                themeIcon.className = 'bi bi-sun-fill';
                themeIcon.style.color = '#fb8c00';
            }
        }

        // Check Status Button loader
        const checkStatusBtn = document.getElementById('checkStatusBtn');
        const refreshIcon = document.getElementById('refreshIcon');

        checkStatusBtn.addEventListener('click', () => {
            // Animate spin on icon
            refreshIcon.classList.add('bi-spin');
            checkStatusBtn.disabled = true;
            checkStatusBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying...';

            setTimeout(() => {
                // Reload page. The middleware/auth.php will automatically evaluate
                // their status. If approved, they get redirected to dashboard.php!
                window.location.reload();
            }, 1000);
        });
    </script>
</body>
</html>
