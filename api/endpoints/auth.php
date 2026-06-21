<?php
/**
 * Auth API Endpoint - Security Hardened
 * POST /api/auth/login - Admin login with brute force protection
 */

$db = getDB();

// Only POST allowed (enforced in index.php already, but double-check)
if ($method !== 'POST') {
    sendError('Method not allowed.', 405);
}

$input = getJsonInput();

if (($segments[1] ?? '') !== 'login') {
    sendError('Invalid action. Use /api/auth/login.', 404);
}

$username = Security::sanitizeString($input['username'] ?? '', 50);
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    sendError('Username and password are required.');
}

// Password length limit (prevent DoS with huge passwords)
if (strlen($password) > 128) {
    sendError('Password too long.');
}

// === BRUTE FORCE CHECK ===
$loginCheck = Security::checkLoginAllowed($username);
if ($loginCheck['blocked']) {
    Security::logSecurityEvent('Login blocked (brute force)', "User: $username, IP: " . Security::getClientIp());
    header("Retry-After: " . $loginCheck['retry_after']);
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => $loginCheck['message']
    ]);
    exit();
}

// === AUTHENTICATE ===
$stmt = $db->prepare("SELECT id, username, password, email FROM admins WHERE username = ? AND username != ''");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    
    if (password_verify($password, $admin['password'])) {
        // Success - clear failed attempts
        Security::clearFailedLogins($username);
        
        // Generate JWT token
        $token = Security::generateToken($admin['id'], $admin['username']);
        
        // Store in session for web use
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip'] = Security::getClientIp();
        
        Security::logActivity('Login successful', "User: $username");
        
        sendSuccess([
            'token' => $token,
            'expires_in' => 86400,
            'admin' => [
                'id' => intval($admin['id']),
                'username' => $admin['username'],
                'email' => $admin['email'] ?? ''
            ]
        ], 'Login successful.');
    }
}

// Failed login
Security::recordFailedLogin($username);
Security::logSecurityEvent('Login failed', "User: $username, IP: " . Security::getClientIp());

// Use generic message to prevent user enumeration
sendError('Invalid username or password.', 401);
