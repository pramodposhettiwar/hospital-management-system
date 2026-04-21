# Security Guidelines & Implementation Guide

## 🚨 CRITICAL VULNERABILITIES FOUND & FIXED

### 1. File Upload Vulnerabilities (CRITICAL - CWE-434)
**Status:** ✅ FIXED in backend/admin/assets/inc/security.php

**Vulnerability:** No validation on file uploads - attackers can upload malicious files
**Fix Used:** `secure_file_upload()` function with:
- MIME type validation
- File size limits
- Random filename generation
- Proper error handling

**Implementation:** Use this function in all upload handlers:
```php
require_once 'backend/admin/assets/inc/security.php';

$upload = secure_file_upload('user_photo', 'assets/images/', ['image/jpeg', 'image/png'], 5242880);
if ($upload['success']) {
    $filename = $upload['filename']; // Safe to use
}
```

---

### 2. Missing CSRF Protection (CRITICAL - CWE-352)
**Status:** ✅ FIXED in backend/admin/assets/inc/security.php

**Vulnerability:** Forms vulnerable to cross-site request forgery attacks
**Fix Used:** Token-based CSRF protection

**Implementation Steps:**

**Step 1 - Add token to forms:**
```html
<form method="POST" action="process.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- other form fields -->
</form>
```

**Step 2 - Validate on submission:**
```php
<?php
require_once 'backend/admin/assets/inc/security.php';
init_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('CSRF token validation failed');
    }
    // Process form...
}
?>
```

---

### 3. Weak Password Hashing (HIGH - CWE-326)
**Status:** ✅ FIXED in backend/admin/assets/inc/security.php

**Current Issue:** Using SHA1(MD5()) - cryptographically broken
**Fix:** Use bcrypt via `hash_password()` and `verify_password()`

**Implementation:**

**For Registration/Password Change:**
```php
require_once 'backend/admin/assets/inc/security.php';

$hashed = hash_password($_POST['password']); // Use this
// Store in database: INSERT INTO users (password) VALUES ('$hashed')
```

**For Login:**
```php
$stored_hash = $user->password; // From database

if (verify_password($_POST['password'], $stored_hash)) {
    setup_user_session($user->id);
    log_successful_login($user->id);
} else {
    log_failed_login($username);
}
```

---

### 4. Insecure Session Management (HIGH - CWE-384)
**Status:** ✅ FIXED in backend/admin/assets/inc/security.php

**Vulnerabilities:**
- No session timeout
- No IP address verification
- No user agent validation

**Fix:** Use `check_secure_login()` function

**Implementation:**
```php
<?php
require_once 'backend/admin/assets/inc/security.php';
init_secure_session();

// Check at start of protected pages
if (!check_secure_login('ad_id')) {
    header("Location: index.php");
    exit();
}
?>
```

---

### 5. Missing Security Headers (MEDIUM - CWE-693)
**Status:** ✅ Available in security.php - `set_security_headers()`

**Implementation:**
Add to your main config file:
```php
<?php
require_once 'backend/admin/assets/inc/security.php';

// Call this early in your application
set_security_headers();

// Add HTTPS enforcement
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $url", true, 301);
    exit();
}
?>
```

---

### 6. Insecure Password Reset (HIGH - CWE-640)
**Status:** ✅ Available in security.php

**Current Issue:** Passwords exposed in emails and URLs
**Fix:** Use secure tokens with expiration

**Implementation:**
```php
<?php
require_once 'backend/admin/assets/inc/security.php';

// Generate secure token (NOT password)
$reset_token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $reset_token);
$expiry = time() + (1 * 60 * 60); // 1 hour

// Store hash in database (NOT the plain token)
$mysqli->query("UPDATE users SET reset_token_hash='$token_hash', reset_token_expiry=$expiry WHERE email='$email'");

// Send ONLY the token in email (not password!)
$reset_link = "https://yourdomain.com/reset_password.php?token=" . $reset_token;
?>
```

---

### 7. Missing Input Validation (MEDIUM - CWE-20)
**Status:** ✅ Functions available in security.php

**Implementation:**
```php
<?php
require_once 'backend/admin/assets/inc/security.php';

// Validate numeric input
if (!validate_numeric($_GET['id'], 1, 999999)) {
    die('Invalid ID');
}
$id = (int)$_GET['id'];

// Validate email
if (!validate_email($_POST['email'])) {
    die('Invalid email');
}

// Sanitize string
$name = sanitize_input($_POST['name']);
?>
```

---

### 8. Potential XSS Vulnerabilities (MEDIUM - CWE-79)
**Status:** ✅ Function available: `escape_html()`

**Implementation:**
```php
<?php
require_once 'backend/admin/assets/inc/security.php';

// ALWAYS escape output
echo escape_html($user->name);

// For URLs
<a href="?id=<?php echo escape_url($id); ?>">Link</a>
?>
```

---

## 🛠️ Quick Integration Guide

### **Step 1: Add Security Functions to Config**
Add to `backend/admin/assets/inc/config.php`:
```php
<?php
// ... existing config ...

require_once dirname(__FILE__) . '/security.php';

// Initialize security
init_secure_session();
set_security_headers();
?>
```

### **Step 2: Update Login Files**
Update `backend/admin/index.php` and `backend/doc/index.php`:

```php
<?php
require_once 'assets/inc/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('CSRF validation failed');
    }
    
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Get user from database
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_object();
    
    if ($user && verify_password($password, $user->password)) {
        setup_user_session($user->id, 'ad_id');
        log_successful_login($user->id);
        header("Location: his_admin_dashboard.php");
    } else {
        log_failed_login($username);
        $error = "Invalid credentials";
    }
}
?>
```

### **Step 3: Protect All Forms**
Add CSRF token to every form:
```html
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- form fields -->
</form>
```

### **Step 4: Fix File Uploads**
Replace all file upload code with:
```php
<?php
$upload = secure_file_upload('photo', 'assets/images/', ['image/jpeg', 'image/png', 'image/gif']);
if ($upload['success']) {
    // Store $upload['filename'] in database
} else {
    echo "Upload failed: " . $upload['error'];
}
?>
```

### **Step 5: Add Security Checks to Protected Pages**
Add to every protected page:
```php
<?php
require_once 'assets/inc/config.php';

if (!check_secure_login('ad_id')) {
    header("Location: index.php");
    exit();
}
// Page content here
?>
```

---

## 📊 Security Checklist

### CRITICAL (Must Fix)
- [ ] Implement CSRF token protection on all forms
- [ ] Fix file upload handlers with validation
- [ ] Replace password hashing with bcrypt
- [ ] Improve session validation with IP checks

### HIGH (Should Fix Soon)
- [ ] Fix password reset to not send passwords
- [ ] Add session timeout (30 minutes)
- [ ] Implement security event logging

### MEDIUM (Should Fix)
- [ ] Add security headers to all responses
- [ ] Validate all user input
- [ ] Escape all HTML output
- [ ] Add input length validation
- [ ] Implement rate limiting for login

### LOW (Nice to Have)
- [ ] Enforce HTTPS
- [ ] Add password strength validation
- [ ] Implement account lockout after failed attempts
- [ ] Add 2FA support

---

## 🔐 Security Helper Functions Reference

All these functions are available in `backend/admin/assets/inc/security.php`:

### Session Management
- `init_secure_session()` - Initialize secure session
- `check_secure_login($key)` - Verify user is logged in securely
- `setup_user_session($user_id, $key)` - Set up user session after login
- `secure_logout()` - Properly destroy session

### Password Security
- `hash_password($password)` - Hash password with bcrypt
- `verify_password($password, $hash)` - Verify password
- `check_password_strength($password)` - Check password strength

### CSRF Protection
- `get_csrf_token()` - Get current CSRF token
- `validate_csrf_token($name)` - Validate CSRF token

### File Uploads
- `secure_file_upload($input, $dir, $types, $max_size)` - Safe file upload

### Input Validation
- `sanitize_input($input)` - Sanitize string input
- `validate_email($email)` - Validate email format
- `validate_numeric($input, $min, $max)` - Validate number
- `escape_html($text)` - Escape for HTML
- `escape_url($param)` - Escape for URL

### Security
- `set_security_headers()` - Set all security headers
- `log_security_event($event, $data, $severity)` - Log security events
- `log_failed_login($username)` - Log failed login
- `log_successful_login($user_id)` - Log successful login

---

## 📚 Testing Security

### Test CSRF Protection
1. Create form with CSRF token
2. Try to submit without valid token - should fail
3. Try with valid token - should work

### Test File Upload
1. Try uploading non-image file - should fail
2. Try uploading image larger than 5MB - should fail
3. Upload valid image - should work with random filename

### Test Password Hashing
1. Hash a password
2. Try verifying with wrong password - should fail
3. Try verifying with correct password - should succeed

### Test Session Validation
1. Login successfully
2. Change IP address - should logout
3. Keep same IP - should stay logged in

---

## 🚀 Production Deployment

Before deploying to production:
- [ ] Enable HTTPS/SSL certificate
- [ ] Set error_reporting(0) in production
- [ ] Enable session.secure = 1 in php.ini
- [ ] Enable session.httponly = 1 in php.ini
- [ ] Set strong database passwords
- [ ] Enable database backups
- [ ] Set up log monitoring
- [ ] Configure WAF (Web Application Firewall)
- [ ] Enable rate limiting
- [ ] Run penetration testing

---

**Last Updated:** April 20, 2026  
**Version:** 1.0  
**Security Helper Functions:** Available in `backend/admin/assets/inc/security.php`
