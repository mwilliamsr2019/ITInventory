<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_inventory');
define('DB_USER', 'inventory_user');
define('DB_PASS', '@20mimiX20@');
define('DB_CHARSET', 'utf8mb4');

// SSSD/LDAP configuration (optional)
define('LDAP_ENABLED', false);
define('LDAP_HOST', 'ldap://your-domain-controller');
define('LDAP_PORT', 389);
define('LDAP_BASE_DN', 'dc=yourdomain,dc=com');
define('LDAP_BIND_DN', 'cn=admin,dc=yourdomain,dc=com');
define('LDAP_BIND_PASS', 'admin_password');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Application settings
define('APP_NAME', 'IT Inventory Management System');
define('APP_VERSION', '1.0.0');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('CSV_MAX_ROWS', 10000);

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
?>