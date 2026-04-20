# Security Guidelines & Vulnerabilities Fix

## 🚨 CRITICAL VULNERABILITIES (Fix Immediately)

### 1. File Upload Vulnerabilities (CWE-434)
**Affected Files:**
- `backend/admin/his_admin_account.php`
- `backend/doc/his_doc_update-account.php`
- `backend/admin/his_admin_update_single_employee.php`

**Current Vulnerable Code:**
```php
$ad_dpic=$_FILES["ad_dpic"]["name"];
move_uploaded_file($_FILES["ad_dpic"]["tmp_name"],"assets/images/users/".$_FILES["ad_dpic"]["name"]);
```

**FIX - Secure File Upload:**
```php
// 1. Check if file is uploaded correctly
if (!isset($_FILES['ad_dpic']) || !is_uploaded_file($_FILES['ad_dpic']['tmp_name'])) {
    die('No file uploaded or upload error');
}

// 2. Validate file type (whitelist approach)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = mime_content_type($_FILES['ad_dpic']['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    die('Invalid file type. Only JPEG, PNG, GIF allowed');
}

// 3. Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['ad_dpic']['size'] > $max_size) {
    die('File too large. Maximum 5MB allowed');
}

// 4. Rename file to random name
$file_ext = pathinfo($_FILES['ad_dpic']['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . uniqid() . '.' . $file_ext;
$upload_dir = 'assets/images/users/';

// 5. Move file
if (!move_uploaded_file($_FILES['ad_dpic']['tmp_name'], $upload_dir . $new_filename)) {
    die('File upload failed');
}

// 6. Store filename in database
// $new_filename is now safe to store
```

---

### 2. Missing CSRF Protection (CWE-352)
**Affects:** ALL forms throughout application

**FIX - Add CSRF Protection:**

**Step 1: Generate token on form page:**
```php
<?php
session_start();
// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form method="POST" action="process.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- other form fields -->
    <button type="submit">Submit</button>
</form>
```

**Step 2: Validate on form submission:**
```php
<?php
session_start();

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed. Request rejected.');
    }
    
    // Token is valid, proceed with processing
    // ... your code here ...
    
    // Generate new token for next request
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
```

---

## 🔴 HIGH SEVERITY VULNERABILITIES

### 3. Weak Password Hashing (CWE-326)
**Affected Files:**
- `backend/admin/index.php`
- `backend/doc/index.php`
- `backend/admin/his_admin_account.php`

**Current Vulnerable Code:**
```php
$ad_pwd = sha1(md5($_POST['ad_pwd'])); // Extremely insecure!
```

**FIX - Modern Password Hashing:**

**For Registration/Password Change:**
```php
// Hash password securely
$hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
// Store $hashed_password in database
```

**For Login:**
```php
// Retrieve hashed password from database
$stored_hash = $user->password; // from DB

// Verify password
if (password_verify($_POST['password'], $stored_hash)) {
    // Password is correct - allow login
    $_SESSION['user_id'] = $user->id;
} else {
    // Password is incorrect
    die('Invalid credentials');
}
```

---

### 4. Insecure Session Validation (CWE-384)
**File:** `backend/admin/assets/inc/checklogin.php` and similar

**Current Weak Code:**
```php
function check_login() {
    if(strlen($_SESSION['ad_id'])==0) {
        // Redirect
    }
}
```

**FIX - Secure Session Management:**
```php
<?php
function check_login() {
    // Check if session variable exists
    if (!isset($_SESSION['ad_id'])) {
        session_destroy();
        header("Location: index.php?error=Session expired");
        exit();
    }
    
    // Verify IP address hasn't changed (Session fixation protection)
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        header("Location: index.php?error=Session tampered");
        exit();
    }
    
    // Check session timeout (30 minutes of inactivity)
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > (30 * 60)) {
        session_destroy();
        header("Location: index.php?error=Session timeout");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// On successful login, set these:
function login_success($user_id) {
    $_SESSION['ad_id'] = $user_id;
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
}
?>
```

---

### 5. Password Exposure in Reset URLs (CWE-640)
**File:** `backend/admin/his_admin_manage_password_resets.php`

**NEVER send passwords via email!**

**FIX - Secure Password Reset:**
```php
<?php
// Step 1: User requests password reset
if (isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    $user = // get user by email from DB
    
    // Generate secure token
    $reset_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $reset_token);
    $expiry = time() + (1 * 60 * 60); // 1 hour expiry
    
    // Store in database (NOT the plain token)
    $mysqli->query("UPDATE users SET reset_token_hash='$token_hash', reset_token_expiry=$expiry WHERE email='$email'");
    
    // Send email with ONLY the reset token (not password!)
    $reset_link = "https://yourdomain.com/reset_password.php?token=" . $reset_token;
    mail($email, "Password Reset", "Click here to reset: " . $reset_link);
    
    echo "Password reset link sent to email";
}

// Step 2: User clicks link and submits new password
if (isset($_POST['new_password']) && isset($_GET['token'])) {
    $reset_token = $_GET['token'];
    $token_hash = hash('sha256', $reset_token);
    $new_password = $_POST['new_password'];
    
    // Verify token and expiry
    $user = $mysqli->query("SELECT * FROM users WHERE reset_token_hash='$token_hash' AND reset_token_expiry > " . time());
    
    if ($user) {
        // Hash new password
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password and clear token
        $mysqli->query("UPDATE users SET password='$hashed', reset_token_hash=NULL WHERE reset_token_hash='$token_hash'");
        
        echo "Password reset successful!";
    } else {
        die("Invalid or expired token");
    }
}
?>
```

---

## 🟡 MEDIUM SEVERITY VULNERABILITIES

### 6. Input Validation Missing
**FIX - Validate $_GET parameters:**
```php
<?php
// For numeric IDs
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid ID');
}
$id = (int)$_GET['id'];

// For string parameters
if (!isset($_GET['search']) || !preg_match('/^[a-zA-Z0-9\s]+$/', $_GET['search'])) {
    die('Invalid search term');
}
$search = $_GET['search'];
?>
```

### 7. Missing Security Headers
**FIX - Add to `backend/admin/assets/inc/config.php` after opening `<?php`:**
```php
<?php
// Security Headers
header("X-Frame-Options: DENY"); // Prevent clickjacking
header("X-XSS-Protection: 1; mode=block"); // XSS protection
header("X-Content-Type-Options: nosniff"); // Prevent MIME sniffing
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // HTTPS only

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

// HTTPS Enforcement
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $url", true, 301);
    exit();
}
?>
```

### 8. Weak Password Reset Token Generation
**Fix token generation in password reset files:**
```php
<?php
// INSTEAD OF:
// $_token = substr(str_shuffle('...'), 1, 20);

// USE:
$_token = bin2hex(random_bytes(32)); // 64-character cryptographically secure token
?>
```

### 9. Output Escaping for XSS Prevention
**FIX - Always escape output:**
```php
<?php
// INSTEAD OF:
// echo $row->name;

// USE:
echo htmlspecialchars($row->name, ENT_QUOTES, 'UTF-8');

// For URLs:
echo "?id=" . urlencode($id);
?>
```

---

## ✅ Security Best Practices

### Password Requirements
```php
// Enforce strong passwords
function is_strong_password($password) {
    return strlen($password) >= 12 &&
           preg_match('/[A-Z]/', $password) && // Uppercase
           preg_match('/[a-z]/', $password) && // Lowercase
           preg_match('/[0-9]/', $password) && // Number
           preg_match('/[!@#$%^&*]/', $password); // Special char
}
```

### Database Connection Security
```php
// Use parameterized queries (already done - good!)
$stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
```

### File Permissions
```bash
# Set proper permissions on sensitive files
chmod 600 .env              # Read/write owner only
chmod 700 backend/          # Execute directory only for owner
chmod 600 backend/*/index.php # Sensitive files
chmod 755 assets/           # Public assets
chmod 700 logs/             # Logs owner only
chmod 700 config/           # Config owner only
```

---

## 📋 Security Checklist

- [ ] Fix file upload vulnerabilities (CRITICAL)
- [ ] Implement CSRF token protection (CRITICAL)
- [ ] Replace weak hashing with bcrypt (HIGH)
- [ ] Improve session validation (HIGH)
- [ ] Fix password reset flow (HIGH)
- [ ] Add input validation for all $_GET/$_POST
- [ ] Add security headers
- [ ] Use strong token generation (random_bytes)
- [ ] Escape all output (htmlspecialchars)
- [ ] Enforce HTTPS
- [ ] Set proper file permissions
- [ ] Add password strength requirements
- [ ] Implement rate limiting for login attempts
- [ ] Add logging for security events
- [ ] Regular security audits

---

## 🔐 Production Deployment Checklist

- [ ] Enable HTTPS/SSL certificate
- [ ] Set `error_reporting(0)` in production
- [ ] Set `display_errors = Off` in php.ini
- [ ] Enable error logging to file (not screen)
- [ ] Set `session.secure = 1` for HTTPS-only cookies
- [ ] Set `session.httponly = 1` to prevent XSS cookie theft
- [ ] Set `session.samesite = Strict` for CSRF prevention
- [ ] Use strong database passwords (not default)
- [ ] Regular database backups with encryption
- [ ] Monitor logs for suspicious activity
- [ ] Keep PHP and dependencies updated
- [ ] Use Web Application Firewall (WAF)
- [ ] Implement rate limiting
- [ ] Regular penetration testing

---

## 📚 Additional Resources

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- CWE/SANS Top 25: https://cwe.mitre.org/top25/
- PHP Security: https://www.php.net/manual/en/security.php
- NIST Guidelines: https://csrc.nist.gov/

---

**Last Updated:** April 20, 2026  
**Version:** 1.0
