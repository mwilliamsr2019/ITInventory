<?php
// Security Configuration
class Security {
    // Security headers
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src https://cdnjs.cloudflare.com;');
    }
    
    // Input sanitization
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Validate date
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    // Password strength validation
    public static function validatePassword($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password) && 
               preg_match('/[^A-Za-z0-9]/', $password);
    }
    
    // CSRF token generation
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // CSRF token validation
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Rate limiting using file-based storage
    public static function checkRateLimit($identifier, $maxAttempts = 5, $window = 300) {
        $rateLimitDir = sys_get_temp_dir() . '/it_inventory_rate_limit';
        if (!is_dir($rateLimitDir)) {
            mkdir($rateLimitDir, 0700, true);
        }
        
        $key = $rateLimitDir . '/' . md5($identifier);
        $now = time();
        
        if (file_exists($key)) {
            $data = json_decode(file_get_contents($key), true);
            if ($now - $data['first_attempt'] > $window) {
                // Reset counter after window
                unlink($key);
                return true;
            }
            
            if ($data['attempts'] >= $maxAttempts) {
                return false;
            }
            
            $data['attempts']++;
            file_put_contents($key, json_encode($data));
        } else {
            $data = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            file_put_contents($key, json_encode($data));
        }
        
        return true;
    }
    
    // Session security
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    // File upload validation
    public static function validateFileUpload($file, $allowedTypes = ['text/csv']) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        return in_array($mimeType, $allowedTypes);
    }
}
?>