<?php
// Step 10: Session Setup
session_start();
require_once '../config/db.php';

$error_message = '';

// Step 9: Handle Login POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Get form data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 2. Validate empty fields
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill all fields';
    } else {
        try {
            // 3. Query database - Find user by username using prepared statement
            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 4. Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Password is valid - Create session (Step 10)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;

                // Step 11: Redirect After Login
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error_message = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error. Please try again.';
            error_log('Login Error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Inventory Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                        #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 45px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }
        
        .brand-icon {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            font-size: 24px;
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
        }
        
        .form-control {
            background-color: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            color: #f8fafc !important;
            font-size: 14px !important;
            transition: all 0.2s ease-in-out !important;
        }
        
        .form-control:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25) !important;
            outline: 0 !important;
            background-color: rgba(15, 23, 42, 0.8) !important;
        }
        
        .form-control::placeholder {
            color: #64748b !important;
        }
        
        .form-label {
            font-weight: 600 !important;
            color: #94a3b8 !important;
            margin-bottom: 8px !important;
            font-size: 13px !important;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            border: none !important;
            font-weight: 600 !important;
            letter-spacing: -0.2px !important;
            padding: 12px 24px !important;
            border-radius: 30px !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3) !important;
            transition: all 0.2s ease-in-out !important;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.45) !important;
            filter: brightness(1.08) !important;
        }
        
        .error-alert {
            background-color: rgba(239, 68, 68, 0.15) !important;
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
            color: #fca5a5 !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-size: 13px !important;
            margin-bottom: 24px !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">
            <i class="bi bi-box-seam-fill"></i>
        </div>
        <h3 class="text-center text-white fw-bold mb-1">Smart Inventory</h3>
        <p class="text-center text-muted mb-4" style="font-size: 13px;">Enterprise Resource Management</p>

        <?php if ($error_message): ?>
            <div class="error-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?php echo htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="position-relative">
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-submit">Sign In</button>
        </form>
    </div>
    
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
