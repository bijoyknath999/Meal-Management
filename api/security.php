<?php
/**
 * API Security Middleware
 * Rate limiting, input sanitization, brute force protection
 */

class Security {
    private static $rateLimitFile = null;
    private static $bruteForceFile = null;

    // ============ RATE LIMITING ============
    
    /**
     * Check rate limit per IP address
     * @param int $maxRequests Maximum requests allowed in the window
     * @param int $windowSeconds Time window in seconds
     */
    public static function checkRateLimit($maxRequests = 60, $windowSeconds = 60) {
        $ip = self::getClientIp();
        $cacheDir = sys_get_temp_dir() . '/api_rate_limits';
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }
        
        $file = $cacheDir . '/rate_' . md5($ip) . '.json';
        $now = time();
        $data = ['requests' => [], 'blocked_until' => 0];
        
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $data = json_decode($content, true) ?? $data;
        }
        
        // Check if currently blocked
        if ($data['blocked_until'] > $now) {
            $retryAfter = $data['blocked_until'] - $now;
            header("Retry-After: $retryAfter");
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many requests. Try again in ' . $retryAfter . ' seconds.'
            ]);
            exit();
        }
        
        // Clean old requests outside the window
        $data['requests'] = array_filter($data['requests'], function($ts) use ($now, $windowSeconds) {
            return ($now - $ts) < $windowSeconds;
        });
        
        // Check limit
        if (count($data['requests']) >= $maxRequests) {
            // Block for double the window
            $data['blocked_until'] = $now + ($windowSeconds * 2);
            file_put_contents($file, json_encode($data), LOCK_EX);
            
            header("Retry-After: " . ($windowSeconds * 2));
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Rate limit exceeded. Blocked for ' . ($windowSeconds * 2) . ' seconds.'
            ]);
            exit();
        }
        
        // Record this request
        $data['requests'][] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    // ============ BRUTE FORCE PROTECTION ============
    
    /**
     * Record a failed login attempt
     */
    public static function recordFailedLogin($username) {
        $ip = self::getClientIp();
        $cacheDir = sys_get_temp_dir() . '/api_brute_force';
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }
        
        // Track by both IP and username
        $ipFile = $cacheDir . '/bf_ip_' . md5($ip) . '.json';
        $userFile = $cacheDir . '/bf_user_' . md5(strtolower($username)) . '.json';
        
        $now = time();
        $window = 900; // 15 minutes
        $maxAttempts = 5;
        
        // Update IP-based tracking
        $ipData = self::getBruteForceData($ipFile);
        $ipData['attempts'][] = $now;
        $ipData['attempts'] = array_filter($ipData['attempts'], function($ts) use ($now, $window) {
            return ($now - $ts) < $window;
        });
        
        if (count($ipData['attempts']) >= $maxAttempts) {
            $ipData['blocked_until'] = $now + 1800; // Block for 30 minutes
        }
        
        file_put_contents($ipFile, json_encode($ipData), LOCK_EX);
        
        // Update username-based tracking
        $userData = self::getBruteForceData($userFile);
        $userData['attempts'][] = $now;
        $userData['attempts'] = array_filter($userData['attempts'], function($ts) use ($now, $window) {
            return ($now - $ts) < $window;
        });
        
        if (count($userData['attempts']) >= $maxAttempts) {
            $userData['blocked_until'] = $now + 1800;
        }
        
        file_put_contents($userFile, json_encode($userData), LOCK_EX);
    }
    
    /**
     * Check if login is blocked for this IP or username
     * @return array ['blocked' => bool, 'message' => string, 'retry_after' => int]
     */
    public static function checkLoginAllowed($username = '') {
        $ip = self::getClientIp();
        $cacheDir = sys_get_temp_dir() . '/api_brute_force';
        $now = time();
        
        // Check IP block
        $ipFile = $cacheDir . '/bf_ip_' . md5($ip) . '.json';
        $ipData = self::getBruteForceData($ipFile);
        
        if ($ipData['blocked_until'] > $now) {
            $retryAfter = $ipData['blocked_until'] - $now;
            return [
                'blocked' => true,
                'message' => 'Too many failed attempts from this IP. Try again in ' . ceil($retryAfter / 60) . ' minutes.',
                'retry_after' => $retryAfter
            ];
        }
        
        // Check username block
        if (!empty($username)) {
            $userFile = $cacheDir . '/bf_user_' . md5(strtolower($username)) . '.json';
            $userData = self::getBruteForceData($userFile);
            
            if ($userData['blocked_until'] > $now) {
                $retryAfter = $userData['blocked_until'] - $now;
                return [
                    'blocked' => true,
                    'message' => 'Too many failed attempts for this account. Try again in ' . ceil($retryAfter / 60) . ' minutes.',
                    'retry_after' => $retryAfter
                ];
            }
        }
        
        return ['blocked' => false];
    }
    
    /**
     * Clear failed login attempts on successful login
     */
    public static function clearFailedLogins($username) {
        $ip = self::getClientIp();
        $cacheDir = sys_get_temp_dir() . '/api_brute_force';
        
        $ipFile = $cacheDir . '/bf_ip_' . md5($ip) . '.json';
        $userFile = $cacheDir . '/bf_user_' . md5(strtolower($username)) . '.json';
        
        @unlink($ipFile);
        @unlink($userFile);
    }
    
    private static function getBruteForceData($file) {
        $default = ['attempts' => [], 'blocked_until' => 0];
        if (!file_exists($file)) return $default;
        $content = @file_get_contents($file);
        return json_decode($content, true) ?? $default;
    }
    
    // ============ INPUT SANITIZATION ============
    
    /**
     * Sanitize a string input
     */
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) return '';
        $input = trim($input);
        $input = mb_substr($input, 0, $maxLength);
        $input = strip_tags($input);
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        return $input;
    }
    
    /**
     * Sanitize and validate email
     */
    public static function sanitizeEmail($email) {
        $email = trim($email);
        if (empty($email)) return '';
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return '';
        return mb_substr($email, 0, 100);
    }
    
    /**
     * Sanitize phone number (allow only digits, +, -, spaces, parentheses)
     */
    public static function sanitizePhone($phone) {
        $phone = trim($phone);
        if (empty($phone)) return '';
        $phone = preg_replace('/[^0-9+\-\s()]/', '', $phone);
        return mb_substr($phone, 0, 20);
    }
    
    /**
     * Validate and sanitize integer ID
     */
    public static function validateId($id) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }
    
    /**
     * Validate date format (Y-m-d)
     */
    public static function validateDate($date) {
        if (!is_string($date) || empty($date)) return null;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return $date;
        }
        return null;
    }
    
    /**
     * Validate numeric amount
     */
    public static function validateAmount($amount) {
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        return ($amount !== false && $amount >= 0) ? $amount : null;
    }
    
    /**
     * Validate meal count (0-10)
     */
    public static function validateMealCount($count) {
        $count = filter_var($count, FILTER_VALIDATE_INT);
        if ($count === false || $count < 0 || $count > 10) return null;
        return $count;
    }
    
    /**
     * Sanitize description text
     */
    public static function sanitizeDescription($input, $maxLength = 1000) {
        if (!is_string($input)) return '';
        $input = trim($input);
        $input = mb_substr($input, 0, $maxLength);
        $input = strip_tags($input);
        $input = str_replace("\0", '', $input);
        return $input;
    }
    
    // ============ SECURITY HEADERS ============
    
    /**
     * Send security headers
     */
    public static function sendSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Content Security Policy
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        // Strict Transport Security (enable in production with HTTPS)
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        // Cache control - no caching for API responses
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    
    // ============ REQUEST VALIDATION ============
    
    /**
     * Validate request method
     */
    public static function validateMethod($method, $allowed) {
        if (!in_array($method, $allowed)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
            exit();
        }
    }
    
    /**
     * Validate Content-Type for POST/PUT requests
     */
    public static function validateContentType($method) {
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (!empty($contentType) && strpos($contentType, 'application/json') === false) {
                http_response_code(415);
                echo json_encode(['success' => false, 'error' => 'Unsupported Media Type. Use application/json.']);
                exit();
            }
        }
    }
    
    /**
     * Validate request body size (max 1MB)
     */
    public static function validateRequestSize() {
        $maxSize = 1048576; // 1MB
        $contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > $maxSize) {
            http_response_code(413);
            echo json_encode(['success' => false, 'error' => 'Request body too large. Maximum 1MB allowed.']);
            exit();
        }
    }
    
    // ============ JWT HELPERS ============
    
    /**
     * Generate a simple HMAC-based token
     * For production, use a proper JWT library like firebase/php-jwt
     */
    public static function generateToken($adminId, $username) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub' => $adminId,
            'name' => $username,
            'iat' => time(),
            'exp' => time() + 86400, // 24 hours
            'jti' => bin2hex(random_bytes(16))
        ]));
        $secret = self::getJwtSecret();
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Validate a JWT token
     * @return array|null Token payload or null if invalid
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        [$header, $payload, $signature] = $parts;
        
        // Verify signature
        $secret = self::getJwtSecret();
        $expectedSig = hash_hmac('sha256', "$header.$payload", $secret, true);
        $expectedSig = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');
        
        if (!hash_equals($expectedSig, $signature)) return null;
        
        // Decode payload
        $payloadData = json_decode(base64_decode($payload), true);
        if (!$payloadData) return null;
        
        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) return null;
        
        return $payloadData;
    }
    
    private static function getJwtSecret() {
        // In production, store this in environment variables
        $secret = getenv('JWT_SECRET');
        if (empty($secret)) {
            // Fallback: derive from config (change this in production!)
            $secret = hash('sha256', (defined('DB_PASS') ? DB_PASS : '') . 'meal-api-secret-key-2025');
        }
        return $secret;
    }
    
    // ============ IP DETECTION ============
    
    /**
     * Get client IP address (handles proxies)
     */
    public static function getClientIp() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    // ============ LOGGING ============
    
    /**
     * Log API activity
     */
    public static function logActivity($action, $details = '', $level = 'INFO') {
        $logDir = sys_get_temp_dir() . '/api_logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0700, true);
        }
        
        $logFile = $logDir . '/api_' . date('Y-m-d') . '.log';
        $ip = self::getClientIp();
        $timestamp = date('Y-m-d H:i:s');
        $user = $_SESSION['admin_username'] ?? 'anonymous';
        
        $logEntry = "[$timestamp] [$level] [$ip] [$user] $action";
        if (!empty($details)) {
            $logEntry .= " | $details";
        }
        $logEntry .= PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log security events (failed logins, suspicious activity)
     */
    public static function logSecurityEvent($event, $details = '') {
        self::logActivity($event, $details, 'SECURITY');
    }
    
    // ============ SANITIZE OUTPUT ============
    
    /**
     * Remove sensitive fields from output
     */
    public static function sanitizeOutput($data, $removeFields = ['password', 'token', 'secret']) {
        if (is_array($data)) {
            foreach ($removeFields as $field) {
                unset($data[$field]);
            }
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = self::sanitizeOutput($value, $removeFields);
                }
            }
        }
        return $data;
    }
    
    // ============ CLEANUP ============
    
    /**
     * Clean up old rate limit and brute force files
     * Call this periodically or on each request with low probability
     */
    public static function cleanupOldFiles() {
        // Only run 1% of the time to avoid performance impact
        if (mt_rand(1, 100) !== 1) return;
        
        $dirs = [
            sys_get_temp_dir() . '/api_rate_limits',
            sys_get_temp_dir() . '/api_brute_force'
        ];
        
        $maxAge = 3600; // 1 hour
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $files = glob($dir . '/*.json');
            if (!$files) continue;
            
            $now = time();
            foreach ($files as $file) {
                if ($now - filemtime($file) > $maxAge) {
                    @unlink($file);
                }
            }
        }
    }
}
