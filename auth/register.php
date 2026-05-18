<?php
/**
 * Register Screen - Smart Inventory Management System (SIMS)
 * Beautiful split-screen design matching Figma specs, dark/light theme, and Google signup
 */
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Server-side validation
    if (empty($fullname) || empty($email) || empty($password)) {
        $error_message = 'Please fill all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = 'An account with this email already exists';
            } else {
                // Generate unique username from name or email
                $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
                $username = $base_username;
                
                // Idempotency: append suffix if username exists
                $check_stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                $check_stmt->execute([$username]);
                if ($check_stmt->fetch()) {
                    $username = $base_username . rand(100, 999);
                }

                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert into users
                $insert_stmt = $pdo->prepare('
                    INSERT INTO users (username, email, fullname, password, status) 
                    VALUES (?, ?, ?, ?, "PENDING")
                ');
                $insert_stmt->execute([$username, $email, $fullname, $hashed_password]);
                $user_id = $pdo->lastInsertId();

                // Log the user in
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $fullname;
                $_SESSION['email'] = $email;
                $_SESSION['logged_in'] = true;

                $_SESSION['flash_msg'] = "Account created successfully! Please wait for admin approval.";
                $_SESSION['flash_type'] = "success";

                header('Location: pending.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Database error. Please try again.';
            error_log('Registration Database Error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account - SIMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Google Identity Services (GIS) library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        :root {
            --bg-color-main: #ffffff;
            --form-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-text: #1e293b;
            --btn-google-bg: #ffffff;
            --btn-google-border: #cbd5e1;
            --btn-google-text: #334155;
            --btn-google-hover: #f8fafc;
            --left-banner-bg: #f8fafc;
        }

        [data-bs-theme="dark"] {
            --bg-color-main: #0f172a;
            --form-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-text: #f8fafc;
            --btn-google-bg: #1e293b;
            --btn-google-border: rgba(255, 255, 255, 0.15);
            --btn-google-text: #e2e8f0;
            --btn-google-hover: rgba(255, 255, 255, 0.05);
            --left-banner-bg: #090d16;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color-main);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Split Layout container */
        .split-container {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* Left banner showcase */
        .left-banner {
            flex: 1;
            background-color: var(--left-banner-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            transition: background-color 0.3s ease;
        }
        
        [data-bs-theme="dark"] .left-banner {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .left-banner img {
            width: 160px;
            height: auto;
            margin-bottom: 24px;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.08));
        }

        .left-banner h1 {
            font-size: 38px;
            font-weight: 800;
            color: #0d6efd; /* Premium Brand Blue */
            letter-spacing: -1px;
            margin: 0;
        }

        /* Right form container */
        .right-form {
            flex: 1.1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 60px;
            position: relative;
            background-color: var(--bg-color-main);
            transition: background-color 0.3s ease;
        }

        .form-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .small-brand-logo {
            width: 42px;
            height: auto;
            margin-bottom: 20px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* Floating Theme Toggle */
        .theme-toggle-floating {
            position: absolute;
            top: 24px;
            right: 24px;
            z-index: 100;
            background-color: var(--btn-google-bg);
            border: 1px solid var(--btn-google-border);
            color: var(--text-main);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }

        .theme-toggle-floating:hover {
            background-color: var(--btn-google-hover);
            transform: scale(1.05);
        }

        /* Inputs */
        .form-label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .form-control {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-text) !important;
            border-radius: 10px !important;
            padding: 11px 16px !important;
            font-size: 14px !important;
            transition: all 0.2s ease-in-out !important;
        }

        .form-control:focus {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important;
            outline: 0 !important;
        }

        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.6;
        }

        .input-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Checkbox styling */
        .form-check-input {
            border-color: var(--input-border) !important;
            background-color: var(--input-bg) !important;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }
        .form-check-label {
            font-size: 13.5px;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* Buttons */
        .btn-submit {
            background: #0d6efd !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 14.5px !important;
            padding: 12px 24px !important;
            border-radius: 10px !important;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2) !important;
            transition: all 0.2s ease-in-out !important;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #0b5ed7 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.3) !important;
        }

        /* Google Sign Up button */
        .btn-google {
            background-color: var(--btn-google-bg) !important;
            border: 1px solid var(--btn-google-border) !important;
            color: var(--btn-google-text) !important;
            font-weight: 600 !important;
            font-size: 14.5px !important;
            padding: 11px 24px !important;
            border-radius: 10px !important;
            transition: all 0.2s ease-in-out !important;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
        }

        .btn-google:hover {
            background-color: var(--btn-google-hover) !important;
            transform: translateY(-1px) !important;
        }

        .btn-google svg {
            width: 18px;
            height: 18px;
        }

        .divider-container {
            display: flex;
            align-items: center;
            margin: 20px 0;
            width: 100%;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background-color: var(--input-border);
            opacity: 0.5;
        }
        .divider-text {
            padding: 0 12px;
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-footer {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-top: 24px;
            text-align: center;
        }
        .form-footer a {
            color: #0d6efd;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.1s ease;
        }
        .form-footer a:hover {
            color: #0b5ed7;
            text-decoration: underline;
        }

        /* Error/Alert box */
        .alert-custom {
            background-color: rgba(239, 68, 68, 0.08) !important;
            border: 1px solid rgba(239, 68, 68, 0.18) !important;
            color: #ef4444 !important;
            border-radius: 10px !important;
            padding: 12px 16px !important;
            font-size: 13.5px !important;
            margin-bottom: 20px !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .left-banner {
                display: none;
            }
            .right-form {
                padding: 40px 24px;
            }
        }

        /* Premium Simulation Modal Styles */
        .mock-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .mock-modal-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .mock-modal-card {
            background-color: #ffffff;
            width: 100%;
            max-width: 380px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 24px;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            color: #202124;
            text-align: left;
        }

        [data-bs-theme="dark"] .mock-modal-card {
            background-color: #202124;
            color: #e8eaed;
        }

        .mock-modal-overlay.show .mock-modal-card {
            transform: scale(1);
        }

        .mock-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .mock-modal-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .mock-account-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border: 1px solid #dadce0;
            margin-bottom: 12px;
        }

        [data-bs-theme="dark"] .mock-account-item {
            border-color: #5f6368;
        }

        .mock-account-item:hover {
            background-color: #f1f3f4;
        }

        [data-bs-theme="dark"] .mock-account-item:hover {
            background-color: #303134;
        }

        .mock-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #ffffff;
        }

        .mock-account-name {
            font-size: 13.5px;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .mock-account-email {
            font-size: 11.5px;
            color: #5f6368;
            margin: 0;
        }
        [data-bs-theme="dark"] .mock-account-email {
            color: #9aa0a6;
        }
    </style>
</head>
<body>

    <div class="split-container">
        
        <!-- Left Side: Brand Showcase -->
        <div class="left-banner">
            <img src="../assets/images/logo.png" alt="SIMS Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">
            <h1>SIMS</h1>
            <p class="text-secondary mt-2 fw-medium" style="font-size: 14px;">Smart Inventory Management System</p>
        </div>

        <!-- Right Side: Interactive Form -->
        <div class="right-form">
            
            <!-- Floating Theme Toggle Switch -->
            <button class="theme-toggle-floating" id="themeBtn" title="Toggle Theme">
                <i class="bi bi-sun-fill" id="themeIcon"></i>
            </button>

            <div class="form-wrapper">
                
                <!-- Small branding header (visible on mobile/desktop form container top) -->
                <div class="text-center d-lg-none">
                    <img src="../assets/images/logo.png" class="small-brand-logo" alt="SIMS Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">
                </div>

                <div class="small-brand-logo d-none d-lg-block">
                    <img src="../assets/images/logo.png" style="width: 100%; height: auto;" alt="SIMS Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5164/5164023.png';">
                </div>

                <h2 class="form-title">Create an account</h2>
                <p class="form-subtitle">Start your 30-day free trial.</p>

                <?php if ($error_message): ?>
                    <div class="alert-custom">
                        <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                        <div><?php echo htmlspecialchars($error_message); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off" id="signupForm">
                    
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Name*</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter your name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email*</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password*</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required minlength="8">
                        <div class="input-hint">Must be at least 8 characters.</div>
                    </div>

                    <button type="submit" class="btn btn-submit mb-3">Get started</button>

                    <button type="button" class="btn btn-google" id="googleSignUpBtn">
                        <svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                        </svg>
                        Sign up with Google
                    </button>

                </form>

                <div class="form-footer">
                    Already have an account? <a href="login.php">Log in</a>
                </div>

            </div>

        </div>

    </div>

    <!-- Google OAuth / Simulator Sandbox Overlay Modal -->
    <div class="mock-modal-overlay" id="googleMockModal">
        <div class="mock-modal-card">
            <div class="mock-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    <h6 class="mock-modal-title">Sign in with Google</h6>
                </div>
                <button type="button" class="btn-close shadow-none btn-close-white" id="mockModalCloseBtn" style="font-size: 12px;"></button>
            </div>
            
            <p class="text-secondary mb-3" style="font-size: 13px;">Choose an account to continue to <strong>SIMS</strong></p>
            
            <div class="mock-account-item" onclick="selectMockAccount('Jane Doe', 'jane.doe@gmail.com', 'google_jane_doe_123')">
                <div class="mock-avatar" style="background-color: #ea4335;">J</div>
                <div>
                    <h6 class="mock-account-name">Jane Doe</h6>
                    <p class="mock-account-email">jane.doe@gmail.com</p>
                </div>
            </div>
            
            <div class="mock-account-item" onclick="selectMockAccount('Alex Smith', 'alex.smith@example.com', 'google_alex_smith_456')">
                <div class="mock-avatar" style="background-color: #4285f4;">A</div>
                <div>
                    <h6 class="mock-account-name">Alex Smith</h6>
                    <p class="mock-account-email">alex.smith@example.com</p>
                </div>
            </div>
            
            <div class="mock-account-item" onclick="selectMockAccount('Sarah Jenkins', 'sarah.j@outlook.com', 'google_sarah_j_789')">
                <div class="mock-avatar" style="background-color: #34a853;">S</div>
                <div>
                    <h6 class="mock-account-name">Sarah Jenkins</h6>
                    <p class="mock-account-email">sarah.j@outlook.com</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- UI Interaction Scripts -->
    <script>
        // Light & Dark theme toggle logic
        const htmlEl = document.documentElement;
        const themeBtn = document.getElementById('themeBtn');
        const themeIcon = document.getElementById('themeIcon');

        // Check stored theme or system settings
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
                themeIcon.style.color = '#fb8c00'; // Warm sun color
            }
        }

        // ----------------------------------------------------
        // GOOGLE SIGN IN & SIMULATOR LOGIC
        // ----------------------------------------------------
        const googleClientId = '<?php echo GOOGLE_CLIENT_ID; ?>';
        const googleSignUpBtn = document.getElementById('googleSignUpBtn');
        const googleMockModal = document.getElementById('googleMockModal');
        const mockModalCloseBtn = document.getElementById('mockModalCloseBtn');

        googleSignUpBtn.addEventListener('click', () => {
            if (googleClientId && googleClientId !== '') {
                // Trigger real Google GIS prompt (in case they have it loaded and active)
                try {
                    google.accounts.id.initialize({
                        client_id: googleClientId,
                        callback: handleGoogleCredentialResponse
                    });
                    google.accounts.id.prompt(); // shows One Tap prompt
                } catch (e) {
                    console.error('Google API Error, falling back to simulation:', e);
                    showMockGoogleModal();
                }
            } else {
                // Fall back to custom account selection simulation
                showMockGoogleModal();
            }
        });

        mockModalCloseBtn.addEventListener('click', hideMockGoogleModal);

        function showMockGoogleModal() {
            googleMockModal.classList.add('show');
        }

        function hideMockGoogleModal() {
            googleMockModal.classList.remove('show');
        }

        // Handle selection inside simulated modal
        function selectMockAccount(name, email, google_id) {
            hideMockGoogleModal();
            
            // Post payload to callback handler
            const payload = {
                is_mock: true,
                google_id: google_id,
                email: email,
                name: name
            };

            submitGoogleAuth(payload);
        }

        // Handler for real Google credentials
        function handleGoogleCredentialResponse(response) {
            const payload = {
                is_mock: false,
                credential: response.credential
            };
            submitGoogleAuth(payload);
        }

        // Submits auth info to the backend securely
        function submitGoogleAuth(payload) {
            // Show loading indicators if wanted
            googleSignUpBtn.disabled = true;
            googleSignUpBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            fetch('google-auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert('Auth Error: ' + (data.error || 'Unknown error'));
                    resetGoogleButton();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection Error during Google Sign-in. Please try again.');
                resetGoogleButton();
            });
        }

        function resetGoogleButton() {
            googleSignUpBtn.disabled = false;
            googleSignUpBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                </svg>
                Sign up with Google
            `;
        }
    </script>
</body>
</html>
