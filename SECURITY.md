# Security Documentation

This document outlines the security measures implemented in the mclogs application.

## Recent Security Improvements (2025-10-08)

### Critical Fixes Implemented

#### 1. Error Display Disabled in Production
**File:** `docker/php-fpm/security.ini`

- Disabled `display_errors` and `display_startup_errors`
- Errors now logged to `/var/log/php-fpm/error.log` instead of being displayed
- Prevents exposure of internal file paths and sensitive information

**Impact:** Stack traces and error details no longer visible to end users.

#### 2. Timing Attack Prevention
**File:** `api/endpoints/admin.php`

- Replaced `!==` with `hash_equals()` for token comparison
- Prevents timing-based attacks to guess admin token

```php
// Before: if ($token !== $adminToken)
// After:  if (!hash_equals($adminToken, $token))
```

**Impact:** Admin token cannot be guessed through timing analysis.

#### 3. Debug Information Removed
**File:** `api/endpoints/admin.php`

- Removed all debug information from authentication failures
- No longer exposes token length, partial tokens, or request details
- Generic "Unauthorized" message for all auth failures

**Impact:** Attackers get no hints about why authentication failed.

#### 4. Admin Token in Authorization Header Only
**Files:** `api/endpoints/admin.php`, `web/endpoints/admin-proxy.php`

- Admin token no longer accepted via URL query parameters
- Only accepted via `Authorization: Bearer <token>` header
- Prevents token exposure in:
  - Server access logs
  - Browser history
  - Referrer headers
  - Proxy logs

**Impact:** Admin token never logged or exposed in URLs.

#### 5. Input Validation
**File:** `api/endpoints/log.php`

- Added UTF-8 validation for log content
- Prevents binary/malformed data injection

```php
if (!mb_check_encoding($content, 'UTF-8')) {
    $out->error = "Content must be valid UTF-8 text.";
    echo json_encode($out);
    exit;
}
```

#### 6. Security Headers
**File:** `core/config/security-headers.php`

New security headers added:
- `X-Frame-Options: DENY` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - Legacy XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer information
- `Content-Security-Policy` - Restricts resource loading

#### 7. Session Security
**File:** `docker/php-fpm/security.ini`

Session cookies now have:
- `httponly=1` - Not accessible via JavaScript
- `secure=1` - Only sent over HTTPS
- `samesite=Strict` - CSRF protection
- `use_strict_mode=1` - Rejects uninitialized session IDs

#### 8. PHP Version Hiding
**File:** `docker/php-fpm/security.ini`

- `expose_php = Off` - Hides PHP version from headers

---

## Existing Security Features

### Client-Side Encryption (Password Protection)
**Files:** `web/public/js/mclogs.js`, `web/frontend/logview.php`

- End-to-end encryption for password-protected logs
- Server never sees plaintext or password
- Uses AES-256-GCM encryption
- PBKDF2 key derivation (100,000 iterations)
- Random salt and IV per log

**Security Properties:**
- Zero-knowledge: Server cannot decrypt logs
- Forward secrecy: Compromising server doesn't expose historical logs
- Strong cryptography: Industry-standard algorithms

### Authentication

#### Admin Authentication
**Files:** `web/frontend/admin.php`, `web/endpoints/admin-proxy.php`

- Session-based authentication
- Token never exposed to frontend JavaScript
- Proxy pattern keeps token server-side
- No token in URLs (Authorization header only)
- Timing-safe comparison

#### Session Management
- PHP sessions with secure cookies
- Session regeneration on login
- Proper logout with session destruction
- Session cookie cleanup

### Data Validation

#### MongoDB
- Parameterized queries (no string concatenation)
- ID validation before queries
- Uses MongoDB PHP driver's built-in escaping

#### Input Sanitization
- HTML output uses `htmlspecialchars()` or `escapeHtml()`
- XSS prevention in user-generated content
- UTF-8 validation on log submission

### Access Control

#### File Operations
- Logs stored in dedicated directory
- No directory traversal vulnerabilities (validated paths)
- Proper permissions on storage directories

#### API Endpoints
- CORS headers configured
- Authentication required for admin endpoints
- Method validation (GET/POST/DELETE)

---

## Remaining Recommendations

### High Priority

#### 1. CSRF Protection
**Status:** Not implemented  
**Recommendation:** Add CSRF tokens to all POST/DELETE requests

```php
// Generate token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate token
function validateCSRF() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}
```

#### 2. Rate Limiting
**Status:** Not implemented  
**Recommendation:** Add rate limiting for:
- Admin login attempts
- Log uploads
- API requests

```php
function checkRateLimit($identifier, $maxAttempts = 5, $period = 300) {
    $key = 'rate_limit:' . $identifier;
    \Client\RedisClient::Connect();
    $redis = \Client\RedisClient::$connection;
    
    $attempts = $redis->incr($key);
    if ($attempts === 1) {
        $redis->expire($key, $period);
    }
    
    if ($attempts > $maxAttempts) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
        exit;
    }
}
```

#### 3. Restrict CORS
**Status:** Wide open (`Access-Control-Allow-Origin: *`)  
**Recommendation:** Restrict to specific domains

```php
$allowedOrigins = explode(',', getenv('ALLOWED_ORIGINS') ?? '');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
```

### Medium Priority

#### 4. Path Traversal Prevention
Add comprehensive ID validation:

```php
function validateLogId($id) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
        throw new InvalidArgumentException('Invalid log ID format');
    }
    if (strlen($id) > 20) {
        throw new InvalidArgumentException('Log ID too long');
    }
    return $id;
}
```

#### 5. Audit Logging
Log security-relevant events:
- Admin login attempts (success/failure)
- Log deletions
- Settings changes
- Authentication failures

---

## Security Checklist

### Deployment

- [ ] Ensure `.env` file is not publicly accessible
- [ ] Set strong `ADMIN_TOKEN` (32+ random characters)
- [ ] Enable HTTPS
- [ ] Configure firewall rules
- [ ] Set up log monitoring
- [ ] Configure backup system
- [ ] Review file permissions
- [ ] Disable unnecessary PHP modules

### Monitoring

- [ ] Monitor `/var/log/php-fpm/error.log`
- [ ] Set up alerts for repeated auth failures
- [ ] Monitor disk usage for DoS attacks
- [ ] Check for unusual API patterns

### Regular Maintenance

- [ ] Update PHP and dependencies regularly
- [ ] Review and rotate admin tokens
- [ ] Audit log file sizes
- [ ] Review security headers
- [ ] Test backup restoration

---

## Reporting Security Issues

If you discover a security vulnerability, please report it via:
- GitHub Issues (for this project)
- Email the maintainer directly

**Please do not publicly disclose security issues until they are resolved.**

---

## Security Contact

For security concerns, contact the project maintainer through GitHub.

---

Last Updated: 2025-10-08
