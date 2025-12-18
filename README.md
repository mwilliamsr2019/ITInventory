# 🖥️ IT Inventory Management System

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net/)
[![MariaDB Version](https://img.shields.io/badge/MariaDB-10.3%2B-green.svg)](https://mariadb.org/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A comprehensive, secure, and scalable web-based inventory management system for IT assets built with modern PHP practices, MariaDB, and Bootstrap. Features include multi-method authentication (local and LDAP/SSSD), advanced inventory tracking, CSV import/export, comprehensive reporting, and role-based access control.

## 📋 Table of Contents

- [Features](#features)
- [System Requirements](#requirements)
- [Installation Guide](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Security](#security-considerations)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### 🏗️ Core Inventory Management
- **📊 Asset Tracking**: Comprehensive management of make, model, serial number, property number, warranty dates, and locations
- **🏷️ Use Case Categories**: Desktop, Laptop, Server, Network Equipment, Storage System, Development
- **📍 Location Management**: Flexible location system with on-site/remote designation and hierarchical support
- **📈 Status Tracking**: Active, Retired, Excess, Repair statuses with automated workflows
- **👤 Assignment Tracking**: Assign items to users with full assignment history and audit trail

### 🔐 User Authentication & Management
- **🔑 Multiple Authentication Methods**:
  - Local database authentication with bcrypt hashing
  - LDAP/Active Directory integration
  - SSSD (System Security Services Daemon) support
- **🛡️ Role-Based Access Control (RBAC)**:
  - Admin: Full system access
  - Manager: Department-level management
  - User: Basic inventory access
- **👥 Group Management**: Create and manage user groups with granular permissions
- **🔒 Password Management**:
  - Secure password policies (minimum length, complexity requirements)
  - Password reset functionality with secure tokens
  - Password history to prevent reuse
- **⏰ Session Management**:
  - Secure session handling with configurable timeout protection
  - Database-backed session storage
  - IP address and user agent validation
- **🚫 Account Lockout**: Protection against brute force attacks with progressive delays

### 📤 Data Import/Export
- **📥 CSV Import**:
  - Bulk import inventory items with comprehensive validation
  - Duplicate detection based on serial numbers and property numbers
  - Row-by-row error reporting with detailed feedback
  - Support for custom field mapping
- **📤 CSV Export**:
  - Export filtered data with customizable field selection
  - Automatic filename generation with timestamps
  - Memory-efficient processing for large datasets
- **🔍 Data Validation**:
  - Comprehensive validation for all imported data
  - Real-time validation feedback
  - Custom validation rules support

### 🔍 Search & Reporting
- **🔎 Advanced Search**:
  - Filter by any field with multiple criteria
  - Boolean search operators (AND, OR, NOT)
  - Saved search functionality
- **⚡ Real-time Search**: Instant search results as you type with debouncing
- **📊 Dashboard Analytics**:
  - Visual charts and statistics using Chart.js
  - Customizable dashboard widgets
  - Export reports in multiple formats
- **📋 Audit Logging**:
  - Complete audit trail for all changes
  - User activity tracking
  - Compliance reporting support
- **🗂️ Export History**: Track all import/export operations with user attribution

### 🔒 Security Features
- **🛡️ CSRF Protection**: All forms protected against cross-site request forgery with token validation
- **🧹 Input Sanitization**: All user input properly sanitized and validated using context-aware filters
- **🔐 SQL Injection Prevention**: Prepared statements and parameterized queries throughout the application
- **🌐 XSS Protection**: Output encoding for all displayed data with context-aware escaping
- **🔒 Secure Headers**: Comprehensive security headers implemented
- **📁 File Upload Security**:
  - Secure file upload handling with MIME type validation
  - File size limits and extension filtering
  - Virus scanning integration support

## 📋 System Requirements

### 🖥️ Server Requirements
- **Web Server**:
  - Apache 2.4+ with mod_rewrite enabled
  - Nginx 1.18+ with PHP-FPM
- **PHP**: 7.4+ or 8.0+ (8.1+ recommended for better performance)
- **Database**:
  - MariaDB 10.3+ (recommended)
  - MySQL 5.7+ (with JSON support)
- **Operating System**: Linux (Ubuntu 20.04+, CentOS 8+, Debian 10+)

### 📦 Required PHP Extensions
```bash
# Core extensions
php-mysqlnd      # MySQL native driver
php-pdo          # PDO database abstraction
php-mbstring     # Multi-byte string support
php-json         # JSON processing
php-curl         # HTTP requests
php-ldap         # LDAP authentication (optional)
php-fileinfo     # File type detection
php-gd           # Image processing (for QR codes)
php-openssl      # Cryptographic functions
php-session      # Session management
php-filter       # Input filtering
```

### 🌐 Client Requirements
- **Modern Browsers**:
  - Chrome 90+
  - Firefox 88+
  - Safari 14+
  - Edge 90+
- **JavaScript**: Enabled (required for dynamic features)
- **Cookies**: Enabled (required for session management)

### 🔧 Recommended Server Configuration
```bash
# PHP Settings (php.ini)
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300
session.gc_maxlifetime = 3600

# MySQL/MariaDB Settings (my.cnf)
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M

## 🚀 Installation Guide

### 📥 1. Download and Extract
```bash
# Download the latest release
wget https://github.com/your-repo/it-inventory/releases/latest/download/it-inventory.zip

# Extract the archive
unzip it-inventory.zip
cd it-inventory

# Set proper ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data .
```

### 🗄️ 2. Database Setup

#### Create Database and User
```bash
# Connect to MySQL/MariaDB as root
sudo mysql -u root -p

# Create database with proper charset
CREATE DATABASE it_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create dedicated database user with limited privileges
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON it_inventory.* TO 'inventory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Import Database Schema
```bash
# Import the database schema
mysql -u inventory_user -p it_inventory < sql/schema.sql

# Verify installation
mysql -u inventory_user -p it_inventory -e "SHOW TABLES;"
```

### ⚙️ 3. Configuration

#### Database Configuration
Copy the example configuration file and customize:
```bash
cp config/database.php.example config/database.php
```

Edit `config/database.php`:
```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_inventory');
define('DB_USER', 'inventory_user');
define('DB_PASS', 'your_secure_password_here'); // ⚠️ CHANGE THIS!
define('DB_CHARSET', 'utf8mb4');

// Security settings (customize these!)
define('CSRF_TOKEN_NAME', 'csrf_token_' . substr(md5(__FILE__), 0, 8));
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// Application settings
define('APP_NAME', 'IT Inventory Management System');
define('APP_VERSION', '1.0.0');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
?>
```

#### Environment-Specific Configuration
For production environments, consider using environment variables:
```php
// More secure approach using environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'it_inventory');
define('DB_USER', $_ENV['DB_USER'] ?? 'inventory_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
```

### 🌐 4. Web Server Configuration

#### Apache Configuration
Create `/etc/apache2/sites-available/it-inventory.conf`:
```apache
<VirtualHost *:80>
    ServerName inventory.yourdomain.com
    DocumentRoot /var/www/it-inventory
    
    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; font-src 'self' cdn.jsdelivr.net;"
    
    # Hide server information
    ServerTokens Prod
    ServerSignature Off
    
    # Directory settings
    <Directory /var/www/it-inventory>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Deny access to sensitive files
        <Files ~ "^\.">
            Require all denied
        </Files>
        
        <Files ~ "\.(sql|md|txt|log)$">
            Require all denied
        </Files>
    </Directory>
    
    # PHP settings
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/it-inventory-error.log
    CustomLog ${APACHE_LOG_DIR}/it-inventory-access.log combined
</VirtualHost>
```

Enable the site and required modules:
```bash
sudo a2ensite it-inventory
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

#### Nginx Configuration
Create `/etc/nginx/sites-available/it-inventory`:
```nginx
server {
    listen 80;
    server_name inventory.yourdomain.com;
    root /var/www/it-inventory;
    index index.php index.html;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; font-src 'self' cdn.jsdelivr.net;" always;
    
    # Hide nginx version
    server_tokens off;
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~* \.(sql|md|txt|log)$ {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security settings
        fastcgi_param HTTP_PROXY "";
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_read_timeout 300;
    }
    
    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|doc|docx|xls|xlsx)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Logging
    access_log /var/log/nginx/it-inventory-access.log;
    error_log /var/log/nginx/it-inventory-error.log;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/it-inventory /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 🔒 5. File Permissions and Security

#### Set Secure File Permissions
```bash
# Set ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data /var/www/it-inventory

# Set directory permissions
find /var/www/it-inventory -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/it-inventory -type f -exec chmod 644 {} \;

# Make setup script executable (only during installation)
chmod +x /var/www/it-inventory/setup.php

# Secure configuration files
chmod 600 /var/www/it-inventory/config/database.php

# Create upload directory with proper permissions
mkdir -p /var/www/it-inventory/uploads
chown www-data:www-data /var/www/it-inventory/uploads
chmod 755 /var/www/it-inventory/uploads
```

#### Remove Installation Files (After Setup)
```bash
# Remove setup files after successful installation
rm /var/www/it-inventory/setup.php
rm -rf /var/www/it-inventory/sql/

# Remove this README from web-accessible directory
mv /var/www/it-inventory/README.md /var/www/it-inventory-README.md
```

### 🔐 6. SSL/TLS Configuration (Production)

#### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain SSL certificate
sudo certbot --apache -d inventory.yourdomain.com  # For Apache
sudo certbot --nginx -d inventory.yourdomain.com   # For Nginx

# Auto-renewal is configured automatically
```

#### Manual SSL Configuration
Update your web server configuration to include SSL settings and redirect HTTP to HTTPS.

## 📖 Usage Guide

### 🔑 First Login and Security Setup

#### Initial Access
1. Navigate to your installation URL: `https://inventory.yourdomain.com/login.php`
2. **⚠️ CRITICAL**: Login with default credentials:
   - Username: `admin`
   - Password: `admin123`
3. **🚨 IMMEDIATE ACTION REQUIRED**: Change the admin password immediately after first login

#### Security Checklist for New Installations
```bash
# 1. Change default admin password
# 2. Create additional admin users
# 3. Disable default admin account or change username
# 4. Configure LDAP/SSSD if using external authentication
# 5. Set up regular backups
# 6. Review user permissions and groups
```

### 📝 Adding Inventory Items

#### Step-by-Step Process
1. **Navigate**: Click "Add Item" in the main navigation sidebar
2. **Required Fields**: Fill in all fields marked with * (asterisk):
   - Make (manufacturer)
   - Model
   - Serial Number (must be unique)
   - Property Number (must be unique)
   - Use Case (Desktop, Laptop, Server, etc.)
   - Location
3. **Optional Information**:
   - Warranty End Date (YYYY-MM-DD format)
   - Excess Date (for disposal planning)
   - Purchase details (date, cost, vendor)
   - Assignment to users
   - Additional description
4. **Validation**: System automatically validates:
   - Duplicate serial/property numbers
   - Date format correctness
   - Required field completion
5. **Save**: Click "Add Item" to save with automatic audit logging

#### Bulk Import Best Practices
```csv
# Prepare your CSV with these exact headers:
Make,Model,Serial Number,Property Number,Warranty End Date,Use Case,Location,On Site,Description,Assigned To,Purchase Date,Purchase Cost,Vendor,Status

# Example row:
Dell,OptiPlex 7090,ABC123456789,PROP-001234,2025-12-31,Desktop,Main Office,Yes,Standard desktop computer,John Doe,2023-01-15,899.99,Dell Technologies,active
```

### 📊 Importing CSV Data

#### Pre-Import Validation
1. **Download Sample**: Get the latest CSV template from the import page
2. **Data Preparation**:
   - Ensure all required fields are populated
   - Validate date formats (YYYY-MM-DD)
   - Check for duplicate serial/property numbers in your data
   - Verify location names exist in the system
3. **File Size**: Maximum 10MB or 10,000 rows per import

#### Import Process
1. **Navigate**: Go to Tools → Import CSV
2. **Upload**: Select your prepared CSV file
3. **Preview**: Review the first 10 rows for validation
4. **Import**: Click "Import Data" to process
5. **Results**: Review import summary and error reports
6. **Audit**: All imports are logged with user attribution

#### Common Import Issues
- **Duplicate Detection**: System prevents duplicate serial/property numbers
- **Invalid Dates**: Ensure YYYY-MM-DD format for all date fields
- **Missing Locations**: Create locations before importing
- **File Encoding**: Use UTF-8 encoding for special characters

### 👥 User Management (Admin Only)

#### Creating Users
1. **Navigate**: Administration → Users → Add User
2. **User Details**:
   - Username (unique, alphanumeric)
   - Email address (validated format)
   - First and Last name
   - Authentication type (Local/LDAP/SSSD)
3. **Role Assignment**:
   - **Admin**: Full system access
   - **Manager**: Department management
   - **User**: Basic inventory access
4. **Group Membership**: Assign to appropriate groups for permissions
5. **Password Generation**:
   - Local users: Auto-generated secure passwords sent via email
   - LDAP/SSSD users: Managed by external system

#### Security Best Practices
```bash
# Regular user maintenance
- Review user activity logs monthly
- Disable inactive accounts after 90 days
- Implement password expiration policies
- Use strong, unique passwords for local accounts
- Enable two-factor authentication where possible
```

### 📤 Exporting Data

#### Export Options
1. **Navigate**: Tools → Export CSV
2. **Filter Selection**:
   - Use case categories
   - Locations
   - Status (active, retired, excess, repair)
   - Date ranges (purchase, warranty)
   - Assignment status
3. **Field Selection**: Choose which columns to include
4. **Preview**: Review first 10 rows before export
5. **Download**: Files include timestamp in filename

#### Automated Exports
```bash
# Set up cron job for regular exports
0 2 * * * /usr/bin/php /var/www/it-inventory/export.php --type=inventory --format=csv --email=admin@yourdomain.com
```

### 🔍 Advanced Search and Filtering

#### Search Capabilities
- **Quick Search**: Global search across all text fields
- **Advanced Filters**: Combine multiple criteria with AND/OR logic
- **Saved Searches**: Save frequently used filter combinations
- **Export Results**: Export filtered results directly to CSV

#### Search Examples
```
# Find all Dell laptops in Main Office
Make: Dell AND Use Case: Laptop AND Location: Main Office

# Find items with expiring warranties
Warranty End Date: Next 30 days AND Status: active

# Find assigned items for specific user
Assigned To: john.doe AND Status: active
```

## CSV Format

### Required Fields
- Make
- Model
- Serial Number
- Property Number
- Use Case (must be: Desktop, Laptop, Server, Network Equipment, Storage System, Development)
- Location

### Optional Fields
- Warranty End Date (YYYY-MM-DD format)
- Excess Date (YYYY-MM-DD format)
- On Site (Yes/No)
- Description
- Assigned To
- Purchase Date (YYYY-MM-DD format)
- Purchase Cost (numeric)
- Vendor
- Status (active, retired, excess, repair)

### Sample CSV
```csv
Make,Model,Serial Number,Property Number,Warranty End Date,Excess Date,Use Case,Location,On Site,Description,Assigned To,Purchase Date,Purchase Cost,Vendor,Status
Dell,OptiPlex 7090,ABC123456789,PROP-001234,2025-12-31,,Desktop,Main Office,Yes,Standard desktop computer,John Doe,2023-01-15,899.99,Dell Technologies,active
HP,ProBook 450,XYZ987654321,PROP-001235,2025-06-30,,Laptop,Remote Office 1,No,Standard laptop,Jane Smith,2023-03-20,1299.99,HP Inc,active
```

## LDAP/SSSD Configuration

### Enable LDAP Authentication
Edit `config/database.php`:
```php
define('LDAP_ENABLED', true);
define('LDAP_HOST', 'ldap://your-domain-controller');
define('LDAP_PORT', 389);
define('LDAP_BASE_DN', 'dc=yourdomain,dc=com');
define('LDAP_BIND_DN', 'cn=admin,dc=yourdomain,dc=com');
define('LDAP_BIND_PASS', 'admin_password');
```

### LDAP Group Mapping
LDAP groups are automatically mapped to local groups based on the `ldap_group_dn` field in the groups table.

## 🔒 Security Considerations

### 🛡️ Production Security Checklist

#### Network Security
1. **🔐 HTTPS Enforcement**:
   - Force HTTPS redirects for all traffic
   - Use valid SSL certificates (Let's Encrypt recommended)
   - Implement HSTS with appropriate max-age
   - Disable weak cipher suites and protocols

2. **🌐 Firewall Configuration**:
   ```bash
   # UFW example for Ubuntu
   sudo ufw allow 22/tcp    # SSH
   sudo ufw allow 80/tcp    # HTTP (redirect only)
   sudo ufw allow 443/tcp   # HTTPS
   sudo ufw enable
   ```

3. **🚫 Access Restrictions**:
   - Limit admin access to specific IP ranges
   - Use VPN for administrative access
   - Implement fail2ban for intrusion prevention

#### Application Security
1. **🔑 Authentication Security**:
   - Enforce complex password policies
   - Implement account lockout mechanisms
   - Use secure session management
   - Enable CSRF protection on all forms

2. **🛡️ Input Validation**:
   - Server-side validation for all inputs
   - SQL injection prevention with prepared statements
   - XSS protection with context-aware output encoding
   - File upload restrictions and validation

3. **🔒 Data Protection**:
   - Encrypt sensitive data at rest
   - Use secure communication channels
   - Implement proper error handling (no information disclosure)
   - Regular security audits and penetration testing

#### Database Security
1. **👤 User Privileges**:
   ```sql
   -- Create minimal privilege user
   CREATE USER 'inventory_app'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON it_inventory.* TO 'inventory_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **🔐 Connection Security**:
   - Use SSL/TLS for database connections
   - Implement connection pooling with limits
   - Monitor database access logs
   - Regular database backups with encryption

#### Server Hardening
1. **🖥️ Operating System**:
   - Keep OS and packages updated
   - Disable unnecessary services
   - Use intrusion detection systems (IDS)
   - Implement log monitoring and alerting

2. **⚙️ PHP Configuration**:
   ```ini
   # php.ini security settings
   expose_php = Off
   display_errors = Off
   log_errors = On
   error_log = /var/log/php_errors.log
   allow_url_fopen = Off
   allow_url_include = Off
   session.cookie_httponly = On
   session.cookie_secure = On
   session.use_strict_mode = On
   ```

### 🔍 Security Headers Implementation
The application implements comprehensive security headers:

```php
// Security headers configured in the application
header('X-Content-Type-Options: nosniff');                    // Prevent MIME sniffing
header('X-Frame-Options: DENY');                             // Prevent clickjacking
header('X-XSS-Protection: 1; mode=block');                   // XSS protection
header('Strict-Transport-Security: max-age=31536000');       // HSTS
header('Content-Security-Policy: default-src \'self\'');     // CSP
header('Referrer-Policy: strict-origin-when-cross-origin');  // Referrer policy
header('Permissions-Policy: geolocation=(), microphone=()'); // Feature policy
```

### 🚨 Incident Response Plan
1. **Detection**: Monitor logs for suspicious activity
2. **Containment**: Isolate affected systems
3. **Investigation**: Analyze breach scope and impact
4. **Recovery**: Restore from clean backups
5. **Lessons Learned**: Update security measures

### 🔄 Regular Security Maintenance
```bash
# Weekly security tasks
- Review access logs for anomalies
- Check for failed login attempts
- Verify backup integrity
- Update security patches

# Monthly security tasks
- Review user accounts and permissions
- Update password policies
- Conduct security awareness training
- Perform vulnerability scans

# Quarterly security tasks
- Full security audit
- Penetration testing
- Disaster recovery testing
- Security policy review
```

## 🔧 Troubleshooting Guide

### 🚨 Common Issues and Solutions

#### 🗄️ Database Connection Issues

**Symptom**: "Connection failed" or "Database error" messages

**Diagnostic Steps**:
```bash
# 1. Test database connectivity
mysql -u inventory_user -p -h localhost it_inventory

# 2. Check MySQL/MariaDB status
sudo systemctl status mysql
sudo systemctl status mariadb

# 3. Verify database credentials
grep -n "DB_" config/database.php

# 4. Check error logs
sudo tail -f /var/log/mysql/error.log
sudo tail -f /var/log/apache2/error.log
```

**Common Solutions**:
1. **Incorrect Credentials**: Update `config/database.php` with correct information
2. **Database Service Down**: Restart MySQL/MariaDB service
3. **User Permissions**: Re-grant database privileges
4. **Network Issues**: Check firewall rules and port 3306

#### 🔐 Authentication and Login Problems

**Symptom**: Cannot login, "Invalid credentials", or account lockout messages

**Diagnostic Steps**:
```sql
-- Check user status in database
SELECT username, is_active, failed_login_attempts, locked_until
FROM users
WHERE username = 'problem_user';

-- Check recent login attempts
SELECT * FROM audit_log
WHERE table_name = 'users' AND action = 'login_failed'
ORDER BY timestamp DESC LIMIT 10;
```

**Solutions**:
1. **Account Locked**: Wait for lockout duration or manually unlock:
   ```sql
   UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE username = 'username';
   ```
2. **LDAP Issues**: Test LDAP connectivity:
   ```bash
   ldapsearch -x -H ldap://your-domain-controller -b "dc=yourdomain,dc=com" "(uid=testuser)"
   ```
3. **Password Reset**: Use the password reset functionality or manually reset via database

#### 📊 CSV Import Failures

**Symptom**: Import process fails with errors or partial data

**Diagnostic Steps**:
1. **Check File Format**:
   ```bash
   # Check file encoding
   file -i your_file.csv
   
   # Check CSV structure
   head -n 5 your_file.csv
   ```
2. **Validate CSV Content**:
   ```bash
   # Check for special characters
   grep -n $'\t' your_file.csv  # Look for tabs
   grep -n $'\r' your_file.csv  # Look for Windows line endings
   ```

**Common Issues and Fixes**:
1. **Wrong Date Format**: Convert dates to YYYY-MM-DD format
2. **Duplicate Numbers**: Check for existing serial/property numbers
3. **Invalid Characters**: Remove special characters or encode properly
4. **File Too Large**: Split large files into smaller chunks (< 10MB)

#### 📁 File Upload Problems

**Symptom**: Cannot upload files, "File too large", or upload errors

**Diagnostic Steps**:
```bash
# Check PHP upload settings
php -i | grep -E "upload_max_filesize|post_max_size|max_file_uploads"

# Check file permissions
ls -la uploads/
ls -la tmp/

# Monitor upload directory
sudo tail -f /var/log/apache2/error.log | grep -i upload
```

**Solutions**:
1. **Increase Upload Limits**: Update PHP configuration
2. **Fix Permissions**: Set proper ownership and permissions
3. **Check Disk Space**: Ensure sufficient storage available
4. **MIME Type Issues**: Verify file type detection is working

### 🐛 Debug Mode and Logging

#### Enable Development Debugging
```php
// Add to config/database.php (development only!)
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

// Enable database query logging
define('DB_DEBUG', true);
```

#### Application Logging
```bash
# View application logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php_errors.log

# Database query logging (if enabled)
sudo tail -f /var/log/mysql/general.log
```

#### Performance Debugging
```sql
-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Check table sizes and optimization needs
SELECT table_name,
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
       table_rows
FROM information_schema.tables
WHERE table_schema = 'it_inventory'
ORDER BY (data_length + index_length) DESC;
```

### 📞 Getting Help

#### System Information Collection
Before requesting help, collect this information:
```bash
# System information
php -v
mysql --version
apache2 -v  # or nginx -v
uname -a

# Application information
grep "APP_VERSION" config/database.php
ls -la config/
ls -la includes/

# Recent errors
sudo tail -n 50 /var/log/apache2/error.log
sudo tail -n 50 /var/log/mysql/error.log
```

#### Support Channels
1. **GitHub Issues**: Report bugs and request features
2. **Documentation**: Check the wiki for additional guides
3. **Community Forums**: Join discussions with other users
4. **Professional Support**: Contact for enterprise support options

#### Emergency Procedures
```bash
# System compromised? Immediate actions:
1. Take system offline
2. Change all passwords
3. Check audit logs for unauthorized access
4. Restore from clean backup
5. Update all security patches
6. Conduct security audit
```

## 🔌 API Documentation

### 📋 Overview
The IT Inventory Management System provides a comprehensive REST API for integration with external systems. All API endpoints require authentication and return JSON responses.

### 🔐 Authentication

#### API Key Authentication (Recommended for Integrations)
```http
GET /api/items
Authorization: Bearer your_api_key_here
Content-Type: application/json
```

#### Session-Based Authentication (For Web Applications)
```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "api_user",
  "password": "secure_password"
}
```

#### Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "timestamp": "2023-12-18T10:30:00Z"
}
```

### 📦 Inventory Items API

#### List Items with Pagination and Filtering
```http
GET /api/items?page=1&per_page=20&use_case=Laptop&status=active
Authorization: Bearer your_api_key_here
```

**Query Parameters**:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `use_case`: Filter by use case
- `status`: Filter by status (active, retired, excess, repair)
- `location_id`: Filter by location
- `search`: Search across make, model, serial_number, property_number

**Response**:
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 123,
        "make": "Dell",
        "model": "OptiPlex 7090",
        "serial_number": "ABC123456789",
        "property_number": "PROP-001234",
        "use_case": "Desktop",
        "status": "active",
        "location": {
          "id": 1,
          "name": "Main Office"
        },
        "warranty_end_date": "2025-12-31",
        "assigned_to": "john.doe",
        "created_at": "2023-01-15T08:30:00Z",
        "updated_at": "2023-12-01T14:20:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 98,
      "per_page": 20
    }
  }
}
```

#### Create New Item
```http
POST /api/items
Authorization: Bearer your_api_key_here
Content-Type: application/json

{
  "make": "HP",
  "model": "ProBook 450",
  "serial_number": "XYZ987654321",
  "property_number": "PROP-001235",
  "use_case": "Laptop",
  "location_id": 1,
  "status": "active",
  "warranty_end_date": "2025-06-30",
  "assigned_to": "jane.smith",
  "purchase_date": "2023-03-20",
  "purchase_cost": 1299.99,
  "vendor": "HP Inc",
  "description": "Standard business laptop"
}
```

#### Update Item
```http
PUT /api/items/123
Authorization: Bearer your_api_key_here
Content-Type: application/json

{
  "status": "repair",
  "assigned_to": "tech.department",
  "description": "Sent for warranty repair"
}
```

#### Delete Item
```http
DELETE /api/items/123
Authorization: Bearer your_api_key_here
```

### 👥 Users API (Admin Only)

#### List Users
```http
GET /api/users?role=admin&active=true
Authorization: Bearer your_api_key_here
```

#### Create User
```http
POST /api/users
Authorization: Bearer your_api_key_here
Content-Type: application/json

{
  "username": "newuser",
  "email": "newuser@company.com",
  "first_name": "John",
  "last_name": "Doe",
  "role": "user",
  "auth_type": "local",
  "groups": [1, 2]
}
```

### 📊 Reports and Analytics API

#### Dashboard Statistics
```http
GET /api/dashboard/stats
Authorization: Bearer your_api_key_here
```

**Response**:
```json
{
  "success": true,
  "data": {
    "total_items": 1250,
    "items_by_use_case": {
      "Desktop": 450,
      "Laptop": 320,
      "Server": 180,
      "Network Equipment": 200,
      "Storage System": 80,
      "Development": 20
    },
    "warranty_expiring_30_days": 45,
    "recent_items": 15,
    "active_users": 25
  }
}
```

#### Warranty Report
```http
GET /api/reports/warranty?days=30&location_id=1
Authorization: Bearer your_api_key_here
```

### 🔍 Search API

#### Global Search
```http
GET /api/search?q=dell laptop&filters=status:active,use_case:laptop
Authorization: Bearer your_api_key_here
```

#### Advanced Search
```http
POST /api/search/advanced
Authorization: Bearer your_api_key_here
Content-Type: application/json

{
  "query": "dell",
  "filters": {
    "use_case": ["Laptop", "Desktop"],
    "status": "active",
    "location_id": [1, 2],
    "warranty_expiring_days": 90
  },
  "sort_by": "purchase_date",
  "sort_order": "desc"
}
```

### 📝 Error Handling

#### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "serial_number": ["Serial number already exists"],
      "property_number": ["Property number already exists"]
    }
  },
  "timestamp": "2023-12-18T10:30:00Z"
}
```

#### Common Error Codes
- `400` Bad Request: Invalid input data
- `401` Unauthorized: Authentication required
- `403` Forbidden: Insufficient permissions
- `404` Not Found: Resource not found
- `409` Conflict: Duplicate data
- `422` Unprocessable Entity: Validation errors
- `429` Too Many Requests: Rate limit exceeded
- `500` Internal Server Error: Server error

### ⚡ Rate Limiting
- **Standard**: 100 requests per minute per API key
- **Authenticated Users**: 500 requests per minute
- **Admin Users**: 1000 requests per minute

Rate limit headers are included in all responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640444400
```

### 🔧 SDK and Libraries

#### PHP SDK Example
```php
<?php
require_once 'vendor/autoload.php';

use ITInventory\API\Client;

$client = new Client([
    'api_key' => 'your_api_key_here',
    'base_url' => 'https://inventory.yourdomain.com/api'
]);

try {
    // List items
    $items = $client->items->list([
        'use_case' => 'Laptop',
        'status' => 'active'
    ]);
    
    // Create new item
    $newItem = $client->items->create([
        'make' => 'Dell',
        'model' => 'Latitude 5520',
        'serial_number' => 'SN123456789',
        'property_number' => 'PROP-999999',
        'use_case' => 'Laptop'
    ]);
    
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
}
?>
```

#### Python SDK Example
```python
import requests

class ITInventoryAPI:
    def __init__(self, api_key, base_url):
        self.api_key = api_key
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json'
        }
    
    def list_items(self, **filters):
        response = requests.get(
            f"{self.base_url}/items",
            headers=self.headers,
            params=filters
        )
        return response.json()
    
    def create_item(self, item_data):
        response = requests.post(
            f"{self.base_url}/items",
            headers=self.headers,
            json=item_data
        )
        return response.json()

# Usage
api = ITInventoryAPI('your_api_key_here', 'https://inventory.yourdomain.com/api')
items = api.list_items(use_case='Laptop', status='active')
```

### 🧪 API Testing

#### Using cURL
```bash
# Test authentication
curl -X POST https://inventory.yourdomain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "api_user", "password": "secure_password"}'

# List items
curl -X GET "https://inventory.yourdomain.com/api/items?page=1&per_page=10" \
  -H "Authorization: Bearer your_api_key_here"

# Create item
curl -X POST https://inventory.yourdomain.com/api/items \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "make": "Test",
    "model": "Device",
    "serial_number": "TEST123456",
    "property_number": "PROP-TEST001",
    "use_case": "Desktop"
  }'
```

#### Using Postman
Import the [Postman collection](docs/postman_collection.json) for comprehensive API testing.

## ⚡ Performance Optimization

### 🚀 Database Performance

#### Index Optimization
```sql
-- Ensure these indexes exist for optimal performance
CREATE INDEX idx_inventory_composite ON inventory_items(status, use_case, location_id);
CREATE INDEX idx_inventory_dates ON inventory_items(warranty_end_date, purchase_date);
CREATE INDEX idx_audit_timestamp ON audit_log(timestamp, user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
```

#### Query Optimization
```sql
-- Use EXPLAIN to analyze slow queries
EXPLAIN SELECT i.*, l.name as location_name
FROM inventory_items i
JOIN locations l ON i.location_id = l.id
WHERE i.status = 'active' AND i.use_case = 'Laptop';
```

#### Database Maintenance
```bash
# Regular maintenance tasks
mysql -u root -p it_inventory << EOF
-- Optimize tables monthly
OPTIMIZE TABLE inventory_items, audit_log, user_sessions;

-- Update statistics
ANALYZE TABLE inventory_items, locations, users;

-- Check for corruption
CHECK TABLE inventory_items, locations, users;
EOF
```

### 🏎️ Application-Level Optimization

#### Caching Strategy
```php
// Implement Redis caching for frequently accessed data
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Cache dashboard statistics for 5 minutes
$stats = $redis->get('dashboard_stats');
if (!$stats) {
    $stats = calculateDashboardStats();
    $redis->setex('dashboard_stats', 300, json_encode($stats));
}
```

#### Query Result Caching
```php
// Cache expensive queries
public function getItemsByUseCase($useCase) {
    $cacheKey = "items_usecase_" . md5($useCase);
    $cached = $this->cache->get($cacheKey);
    
    if ($cached) {
        return json_decode($cached, true);
    }
    
    $result = $this->performExpensiveQuery($useCase);
    $this->cache->set($cacheKey, json_encode($result), 600); // 10 minutes
    
    return $result;
}
```

### 📊 Performance Monitoring

#### Application Metrics
```php
// Add timing to critical operations
$startTime = microtime(true);
// ... database operation ...
$executionTime = microtime(true) - $startTime;

if ($executionTime > 1.0) {
    error_log("Slow query detected: " . $executionTime . " seconds");
}
```

#### Database Performance Monitoring
```sql
-- Monitor slow queries
SELECT * FROM mysql.slow_log
WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY query_time DESC LIMIT 10;

-- Check connection usage
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Max_used_connections';
```

### 🔄 Scaling Considerations

#### Horizontal Scaling
```nginx
# Load balancing configuration
upstream inventory_backend {
    server 192.168.1.10:80 weight=3;
    server 192.168.1.11:80 weight=2;
    server 192.168.1.12:80 weight=1;
    
    keepalive 32;
}

server {
    location / {
        proxy_pass http://inventory_backend;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
```

#### Database Scaling
```sql
-- Read replica configuration for reporting
-- Primary server for writes
CREATE USER 'repl_user'@'%' IDENTIFIED BY 'password';
GRANT REPLICATION SLAVE ON *.* TO 'repl_user'@'%';

-- Configure read replicas for analytics and reporting
```

### 🛠️ Performance Tuning Checklist

#### Server-Level Optimizations
```bash
# PHP-FPM optimization
# /etc/php/8.0/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

# OPcache settings
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

#### Database Configuration
```ini
# MySQL/MariaDB optimization for inventory system
[mysqld]
# Memory allocation
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_log_buffer_size = 16M

# Connection handling
max_connections = 200
max_user_connections = 50
wait_timeout = 28800
interactive_timeout = 28800

# Query cache (for MySQL 5.7)
query_cache_size = 64M
query_cache_limit = 2M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### 📝 Development Setup
```bash
# 1. Fork and clone the repository
git clone https://github.com/your-username/it-inventory.git
cd it-inventory

# 2. Install development dependencies
composer install --dev

# 3. Set up development environment
cp config/database.php.example config/database.php
# Edit with your development database credentials

# 4. Run tests
./vendor/bin/phpunit tests/
```

### 🔄 Contribution Workflow
1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes with clear messages (`git commit -m 'Add: amazing feature'`)
4. **Push** to your branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request with detailed description

### ✅ Code Standards
- Follow PSR-12 coding standards
- Write unit tests for new features
- Update documentation for API changes
- Ensure all tests pass before submitting

### 🧪 Testing
```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:integration

# Code coverage
composer coverage
```

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2023 IT Inventory Management System

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## 🆘 Support and Community

### 📚 Documentation Resources
- **[Wiki](https://github.com/your-repo/it-inventory/wiki)**: Comprehensive guides and tutorials
- **[API Documentation](https://inventory.yourdomain.com/api/docs)**: Interactive API explorer
- **[Video Tutorials](https://youtube.com/playlist?list=...)**: Step-by-step video guides

### 💬 Community Support
- **GitHub Issues**: [Report bugs and request features](https://github.com/your-repo/it-inventory/issues)
- **Discussions**: [Community forum](https://github.com/your-repo/it-inventory/discussions)
- **Stack Overflow**: Tag questions with `it-inventory`

### 🏢 Professional Support
- **Enterprise Support**: Contact sales@yourcompany.com
- **Custom Development**: Available for specialized requirements
- **Training Services**: On-site and remote training options

### 📞 Emergency Support
For critical security issues or system outages:
- **Security Issues**: security@yourcompany.com
- **Critical Bugs**: urgent@yourcompany.com
- **Phone Support**: +1-XXX-XXX-XXXX (24/7 for enterprise customers)

## 📈 Changelog and Roadmap

### 🏷️ Version History

#### Version 1.2.0 (Current) - Security & Performance Update
- 🔒 Enhanced CSRF protection with double-submit cookies
- ⚡ Improved database query performance with optimized indexes
- 🛡️ Added rate limiting for API endpoints
- 📊 Enhanced dashboard analytics with real-time updates
- 🔧 Fixed LDAP authentication edge cases
- 📱 Improved mobile responsiveness

#### Version 1.1.0 - API & Integration Update
- 🔌 Added comprehensive REST API with 20+ endpoints
- 📤 Enhanced CSV import/export with field mapping
- 🔍 Advanced search with boolean operators
- 📱 Mobile-optimized interface
- 🔐 Two-factor authentication support
- 📊 Custom reporting dashboard

#### Version 1.0.0 - Initial Release
- ✅ Basic inventory management with CRUD operations
- 🔐 Multi-method authentication (local, LDAP, SSSD)
- 📊 Dashboard with basic analytics
- 📤 CSV import/export functionality
- 🔍 Search and filtering capabilities
- 👥 User management with role-based access
- 📝 Complete audit logging system

### 🚀 Roadmap - Upcoming Features

#### Version 1.3.0 (Q1 2024)
- 📱 Native mobile applications (iOS/Android)
- 🔗 Integration with popular ITSM tools
- 📈 Advanced analytics and forecasting
- 🏷️ QR code generation and scanning
- 🔄 Automated workflow engine
- 🌍 Multi-language support

#### Version 2.0.0 (Q3 2024)
- 🏗️ Microservices architecture
- 🔄 Real-time synchronization
- 🤖 AI-powered predictive analytics
- 📱 Progressive Web App (PWA)
- 🔗 IoT device integration
- ☁️ Cloud-native deployment options

---

## ⚠️ Important Notes

### 🔒 Security Disclaimer
This system handles sensitive IT asset information. Always:
- Use strong, unique passwords
- Keep software updated
- Implement proper network security
- Regular security audits
- Compliance with relevant regulations (GDPR, HIPAA, etc.)

### 🧪 Testing Recommendation
**Always test in a development environment before deploying to production.** Use sample data that doesn't contain sensitive information.

### 📋 Compliance
This system is designed to help with:
- **IT Asset Management**: Track and manage IT equipment
- **Audit Compliance**: Complete audit trail for regulatory requirements
- **Lifecycle Management**: Manage asset lifecycles from procurement to disposal
- **Financial Reporting**: Track asset values and depreciation

---

**⭐ If you find this project useful, please consider giving it a star on GitHub!**

For more information, visit our [website](https://your-website.com) or contact us at info@yourcompany.com.

---
*Last updated: December 2023*