<?php
/**
 * API Helper Functions - Security Hardened
 */

// Send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Send error response (don't leak internal details)
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Send success response
function sendSuccess($data = null, $message = 'Success') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendResponse($response);
}

// Get and decode JSON input with validation
function getJsonInput() {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $input = file_get_contents('php://input');
    
    // Limit input size (1MB)
    if (strlen($input) > 1048576) {
        sendError('Request body too large.', 413);
    }
    
    $decoded = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON input: ' . json_last_error_msg());
    }
    
    $cached = $decoded ?? [];
    return $cached;
}

// Authenticate request - supports JWT tokens and API keys
function authenticate($authHeader) {
    if (empty($authHeader)) return false;
    
    // Remove 'Bearer ' prefix
    $token = preg_replace('/^Bearer\s+/i', '', trim($authHeader));
    
    if (empty($token)) return false;
    
    // First, try JWT validation
    $payload = Security::validateToken($token);
    if ($payload && isset($payload['sub'])) {
        // Valid JWT - store user info in session for use by endpoints
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_id'] = $payload['sub'];
        $_SESSION['admin_username'] = $payload['name'] ?? 'api_user';
        return true;
    }
    
    // Then, check static API keys (for Flutter app)
    $validKeys = [
        'meal-app-api-key-2025',  // Flutter app API key
    ];
    
    // Also accept if there's a valid admin session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
        return true;
    }
    
    return in_array($token, $validKeys);
}

// Validate required fields with type checking
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing));
    }
}

// Sanitize a string input
function sanitizeInput($input, $maxLength = 255) {
    return Security::sanitizeString($input, $maxLength);
}

// Validate and sanitize integer ID
function validateId($id) {
    return Security::validateId($id);
}

// Format date for display
function formatDate($date) {
    $d = Security::validateDate($date);
    if (!$d) return '';
    return date('d M Y', strtotime($d));
}

// Format currency
function formatCurrency($amount) {
    return number_format(floatval($amount), 2);
}

// Get current authenticated admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Get current authenticated admin username
function getCurrentAdminUsername() {
    return $_SESSION['admin_username'] ?? 'system';
}
