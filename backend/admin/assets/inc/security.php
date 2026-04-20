<?php
/**
 * Security Helper Functions
 * 
 * This file contains reusable security functions for the application
 * Include this file in your main config or use it throughout the app
 * 
 * Usage: require_once 'path/to/security.php';
 */

// ==================== PASSWORD SECURITY ====================

/**
 * Hash password securely using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    if (empty($password)) {
        throw new Exception('Password cannot be empty');
    }
    
    if (strlen($password) < 12) {
        throw new Exception('Password must be at least 12 characters');
    }
    
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Stored password hash
 * @return bool True if password matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check password strength
 * 
 * @param string $password Password to check
 * @return array ['strength' => 0-100, 'errors' => []]
 */
function check_password_strength($password) {
    $strength = 0;
    $errors = [];
    
    if (strlen($password) >= 12) {
        $strength += 25;
    } else {
        $errors[] = 'Password must be at least 12 characters';
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 25;
    } else {
        $errors[] = 'Must contain uppercase letters';
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $strength += 25;
    } else {
        $errors[] = 'Must contain lowercase letters';
    }
    
    if (preg_match('/[0-9]/', $password)) {
        $strength += 15;
    } else {
        $errors[] = 'Must contain numbers';
    }
    
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $strength += 10;
    } else {
        $errors[] = 'Must contain special characters';
    }
    
    return [
        'strength' => min($strength, 100),
        'errors' => $errors
    ];
}

// ==================== SESSION SECURITY ====================

/**
 * Initialize secure session
 * Call this at the start of your application
 */
function init_secure_session() {
    // Session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // HTTPS only
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    
    session_start();
    
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Check if user is logged in with security validation
 * 
 * @param string $user_id_key Session key for user ID
 * @return bool True if valid session
 */
function check_secure_login($user_id_key = 'ad_id') {
    // Check if session exists
    if (!isset($_SESSION[$user_id_key])) {
        session_destroy();
        return false;
    }
    
    // Check IP address hasn't changed (session fixation protection)
    if (!isset($_SESSION['ip_address'])) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > (30 * 60)) {
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Set up secure user session after login
 * 
 * @param mixed $user_id User identifier
 * @param string $user_id_key Session key name
 */
function setup_user_session($user_id, $user_id_key = 'ad_id') {
    // Regenerate session ID (prevent fixation)
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION[$user_id_key] = $user_id;
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Logout and destroy session
 */
function secure_logout() {
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

// ==================== CSRF PROTECTION ====================

/**
 * Get CSRF token for forms
 * 
 * @return string CSRF token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST/GET
 * 
 * @param string $token_name Parameter name (default: 'csrf_token')
 * @return bool True if token is valid
 */
function validate_csrf_token($token_name = 'csrf_token') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Skip for GET requests
    }
    
    if (empty($_POST[$token_name]) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST[$token_name])) {
        return false;
    }
    
    // Regenerate token after use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    return true;
}

// ==================== FILE UPLOAD SECURITY ====================

/**
 * Securely handle file uploads
 * 
 * @param string $input_name $_FILES key
 * @param string $upload_dir Upload directory
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function secure_file_upload($input_name, $upload_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Check if upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Check if file was uploaded
    if (!isset($_FILES[$input_name]) || !is_uploaded_file($_FILES[$input_name]['tmp_name'])) {
        $result['error'] = 'No file uploaded or upload error occurred';
        return $result;
    }
    
    // Validate file size
    if ($_FILES[$input_name]['size'] > $max_size) {
        $result['error'] = 'File size exceeds maximum allowed size';
        return $result;
    }
    
    // Validate file type using MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($finfo, $_FILES[$input_name]['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($file_type, $allowed_types)) {
        $result['error'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
        return $result;
    }
    
    // Generate random filename
    $file_ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    
    if (!in_array(strtolower($file_ext), $allowed_ext)) {
        $result['error'] = 'Invalid file extension';
        return $result;
    }
    
    $new_filename = 'file_' . uniqid() . '.' . strtolower($file_ext);
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES[$input_name]['tmp_name'], $upload_path)) {
        $result['error'] = 'Failed to move uploaded file';
        return $result;
    }
    
    // Set proper permissions
    chmod($upload_path, 0644);
    
    $result['success'] = true;
    $result['filename'] = $new_filename;
    
    return $result;
}

// ==================== INPUT VALIDATION ====================

/**
 * Sanitize string input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate numeric input
 * 
 * @param mixed $input Input to validate
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return bool True if valid
 */
function validate_numeric($input, $min = null, $max = null) {
    if (!is_numeric($input)) {
        return false;
    }
    
    $num = (int)$input;
    
    if ($min !== null && $num < $min) {
        return false;
    }
    
    if ($max !== null && $num > $max) {
        return false;
    }
    
    return true;
}

/**
 * Escape HTML output
 * 
 * @param string $text Text to escape
 * @return string Escaped text
 */
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape URL parameter
 * 
 * @param string $param Parameter to escape
 * @return string Escaped parameter
 */
function escape_url($param) {
    return urlencode($param);
}

// ==================== SECURITY HEADERS ====================

/**
 * Set all security headers
 * Call this in your main config file
 */
function set_security_headers() {
    // Prevent clickjacking
    header("X-Frame-Options: DENY");
    
    // XSS Protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Prevent MIME sniffing
    header("X-Content-Type-Options: nosniff");
    
    // HTTPS enforcement
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Feature Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

// ==================== LOGGING & MONITORING ====================

/**
 * Log security events
 * 
 * @param string $event Event name
 * @param array $data Event data
 * @param string $severity Event severity (info, warning, error, critical)
 */
function log_security_event($event, $data = [], $severity = 'info') {
    $log_file = 'logs/security.log';
    
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['ad_id'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_entry = sprintf(
        "[%s] [%s] [%s] Event: %s | User: %s | IP: %s | Data: %s\n",
        $timestamp,
        strtoupper($severity),
        get_class(),
        $event,
        $user_id,
        $ip,
        json_encode($data)
    );
    
    error_log($log_entry, 3, $log_file);
}

/**
 * Log failed login attempt
 * 
 * @param string $username Username attempted
 */
function log_failed_login($username) {
    log_security_event('FAILED_LOGIN_ATTEMPT', [
        'username' => $username,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ], 'warning');
}

/**
 * Log successful login
 * 
 * @param string $user_id User ID
 */
function log_successful_login($user_id) {
    log_security_event('LOGIN_SUCCESS', [
        'user_id' => $user_id,
        'ip' => $_SERVER['REMOTE_ADDR']
    ], 'info');
}

?>
