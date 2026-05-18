<?php
/**
 * Google Auth Callback Handler
 * Supports real Google token validation and a simulated development login mode
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$credential = $input['credential'] ?? '';
$is_mock = $input['is_mock'] ?? false;

$google_id = '';
$email = '';
$name = '';

if ($is_mock) {
    // ----------------------------------------------------
    // SIMULATION MODE
    // ----------------------------------------------------
    $google_id = trim($input['google_id'] ?? '');
    $email = trim($input['email'] ?? '');
    $name = trim($input['name'] ?? '');

    if (empty($google_id) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid simulation data']);
        exit;
    }
} else {
    // ----------------------------------------------------
    // REAL GOOGLE TOKEN VALIDATION
    // ----------------------------------------------------
    if (empty($credential)) {
        http_response_code(400);
        echo json_encode(['error' => 'No credential provided']);
        exit;
    }

    // Verify token with Google's API
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($credential);
    $response = @file_get_contents($url);
    
    if ($response === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to verify Google token']);
        exit;
    }

    $data = json_decode($response, true);
    
    // Check if token verification is successful
    if (isset($data['error_description']) || !isset($data['sub'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Google token: ' . ($data['error_description'] ?? 'unknown error')]);
        exit;
    }

    // Verify that the client ID matches
    if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '' && $data['aud'] !== GOOGLE_CLIENT_ID) {
        http_response_code(400);
        echo json_encode(['error' => 'Client ID mismatch']);
        exit;
    }

    $google_id = $data['sub'];
    $email = $data['email'] ?? '';
    $name = $data['name'] ?? '';
}

try {
    // 1. Find user by google_id
    $stmt = $pdo->prepare('SELECT id, username, email, fullname, status FROM users WHERE google_id = ? LIMIT 1');
    $stmt->execute([$google_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // 2. If not found by google_id, try to find by email
        if (!empty($email)) {
            $stmt = $pdo->prepare('SELECT id, username, email, fullname, google_id, status FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }
        
        if ($user) {
            // User exists by email, link Google account
            $update_stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $update_stmt->execute([$google_id, $user['id']]);
        } else {
            // 3. User does not exist, create a new one (Sign Up)
            // Generate a unique username based on the email
            $base_username = explode('@', $email)[0];
            $username = $base_username;
            
            // Check if username is already taken, if so, append random suffix
            $check_stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $check_stmt->execute([$username]);
            if ($check_stmt->fetch()) {
                $username = $base_username . rand(100, 999);
            }
            
            // Create strong random password (user can change later, or just log in via Google)
            $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            
            $insert_stmt = $pdo->prepare('
                INSERT INTO users (username, email, fullname, google_id, password, status) 
                VALUES (?, ?, ?, ?, ?, "PENDING")
            ');
            $insert_stmt->execute([$username, $email, $name, $google_id, $random_password]);
            
            // Fetch the newly created user
            $user_id = $pdo->lastInsertId();
            $user = [
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'fullname' => $name,
                'status' => 'PENDING'
            ];
        }
    }

    // 4. Start session for the user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['fullname'] ?? $user['username'];
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['logged_in'] = true;

    $status = $user['status'] ?? 'PENDING';

    if ($status === 'PENDING') {
        $_SESSION['flash_msg'] = "Account created! Please wait for admin approval.";
        $_SESSION['flash_type'] = "info";
        $redirect = 'pending.php';
    } elseif ($status === 'BANNED' || $status === 'DECLINED') {
        // Safe check
        session_destroy();
        $_SESSION = [];
        http_response_code(403);
        echo json_encode(['error' => 'This account has been banned/declined by the administrator.']);
        exit;
    } else {
        $_SESSION['flash_msg'] = "Welcome back, " . ($_SESSION['username']) . "!";
        $_SESSION['flash_type'] = "success";
        $redirect = '../dashboard.php';
    }

    echo json_encode([
        'success' => true,
        'redirect' => $redirect
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error. Please try again.']);
    error_log('Google Auth Database Error: ' . $e->getMessage());
}
