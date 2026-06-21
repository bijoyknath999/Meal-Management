<?php
/**
 * Database connection for API - Security Hardened
 */

function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                // Don't expose connection details in production
                $isDev = defined('DISPLAY_ERRORS') && DISPLAY_ERRORS;
                $msg = $isDev ? 'Database connection failed: ' . $conn->connect_error : 'Database connection failed.';
                Security::logActivity('DB connection failed: ' . $conn->connect_error, '', 'ERROR');
                sendError($msg, 500);
            }
            
            $conn->set_charset("utf8mb4");
            
            // Set strict SQL mode for security
            $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
        } catch (Exception $e) {
            Security::logActivity('DB exception: ' . $e->getMessage(), '', 'ERROR');
            sendError('Database connection failed.', 500);
        }
    }
    
    return $conn;
}

/**
 * Close database connection
 */
function closeDB() {
    // Static connection will be closed when script ends
}

/**
 * Begin transaction
 */
function beginTransaction() {
    $db = getDB();
    $db->begin_transaction();
}

/**
 * Commit transaction
 */
function commitTransaction() {
    $db = getDB();
    $db->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction() {
    $db = getDB();
    $db->rollback();
}
