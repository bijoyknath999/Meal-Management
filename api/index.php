<?php
/**
 * Meal Management System - REST API
 * Main router file with security hardening
 */

// Load config from parent directory
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/security.php';

// === SECURITY HEADERS ===
Security::sendSecurityHeaders();

// CORS - restrict in production
$allowedOrigins = ['http://localhost', 'http://10.0.2.2', 'https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins) || defined('DEVELOPMENT_MODE')) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *'); // Allow all for mobile apps
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// === REQUEST VALIDATION ===
$method = $_SERVER['REQUEST_METHOD'];
Security::validateRequestSize();
Security::validateContentType($method);

// Rate limiting: 120 requests per minute for general, stricter for auth
$rateLimit = ($method === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', '/auth/') !== false) ? 10 : 120;
Security::checkRateLimit($rateLimit, 60);

// Cleanup old security files (runs ~1% of the time)
Security::cleanupOldFiles();

// === ROUTE PARSING ===
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri);
$uri = trim($uri, '/');
$segments = explode('/', $uri);
$resource = $segments[0] ?? '';
$id = null;

// Validate and sanitize ID if present
if (isset($segments[1]) && is_numeric($segments[1])) {
    $id = Security::validateId($segments[1]);
}

// Load core helpers
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

// === AUTHENTICATION ===
$apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($apiKey) && isset($_GET['api_key'])) {
    $apiKey = 'Bearer ' . $_GET['api_key'];
}

// Auth endpoint doesn't require token (it's the login endpoint)
$publicEndpoints = ['', 'auth'];
$isPublic = in_array($resource, $publicEndpoints);

if (!$isPublic && !authenticate($apiKey)) {
    Security::logSecurityEvent('Unauthorized access attempt', "Resource: $resource, IP: " . Security::getClientIp());
    sendError('Unauthorized. Valid API key required.', 401);
}

// === ROUTE DISPATCH ===
try {
    switch ($resource) {
        case 'auth':
            Security::validateMethod($method, ['POST']);
            require __DIR__ . '/endpoints/auth.php';
            break;

        case 'members':
            Security::validateMethod($method, ['GET', 'POST', 'PUT', 'DELETE']);
            require __DIR__ . '/endpoints/members.php';
            break;

        case 'periods':
            Security::validateMethod($method, ['GET', 'POST', 'PUT', 'DELETE']);
            require __DIR__ . '/endpoints/periods.php';
            break;

        case 'meals':
            Security::validateMethod($method, ['GET', 'POST']);
            require __DIR__ . '/endpoints/meals.php';
            break;

        case 'expenses':
            Security::validateMethod($method, ['GET', 'POST', 'PUT', 'DELETE']);
            require __DIR__ . '/endpoints/expenses.php';
            break;

        case 'reports':
            Security::validateMethod($method, ['GET']);
            require __DIR__ . '/endpoints/reports.php';
            break;

        case 'settlements':
            Security::validateMethod($method, ['GET', 'POST']);
            require __DIR__ . '/endpoints/settlements.php';
            break;

        case 'dashboard':
            Security::validateMethod($method, ['GET']);
            require __DIR__ . '/endpoints/dashboard.php';
            break;

        case '':
            sendResponse([
                'name' => 'Meal Management API',
                'version' => '1.0.0',
                'status' => 'running'
            ]);
            break;

        default:
            Security::logSecurityEvent('Unknown endpoint accessed', "Resource: $resource");
            sendError('Endpoint not found.', 404);
    }
} catch (Exception $e) {
    // Don't expose internal errors in production
    $isDev = defined('DEVELOPMENT_MODE') || (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS);
    $message = $isDev ? 'Server error: ' . $e->getMessage() : 'An internal error occurred.';
    Security::logActivity('Exception: ' . $e->getMessage(), '', 'ERROR');
    sendError($message, 500);
}
