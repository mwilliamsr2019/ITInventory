# IT Inventory Management System

A comprehensive web-based inventory management system for IT assets built with PHP, MariaDB, and Bootstrap. Features include user authentication (local and LDAP/SSSD), inventory tracking, CSV import/export, and comprehensive reporting.

## Features

### Core Inventory Management
- **Asset Tracking**: Manage make, model, serial number, property number, warranty dates, and locations
- **Use Case Categories**: Desktop, Laptop, Server, Network Equipment, Storage System, Development
- **Location Management**: Flexible location system with on-site/remote designation
- **Status Tracking**: Active, Retired, Excess, Repair statuses
- **Assignment Tracking**: Assign items to users with full history

### User Authentication & Management
- **Multiple Authentication Methods**: Local database, LDAP, and SSSD support
- **Role-Based Access Control**: Admin, Manager, and User roles
- **Group Management**: Create and manage user groups with permissions
- **Password Management**: Secure password policies and reset functionality
- **Session Management**: Secure session handling with timeout protection
- **Account Lockout**: Protection against brute force attacks

### Data Import/Export
- **CSV Import**: Bulk import inventory items with validation
- **CSV Export**: Export filtered data with customizable fields
- **Duplicate Detection**: Prevent duplicate entries based on serial numbers and property numbers
- **Data Validation**: Comprehensive validation for all imported data

### Search & Reporting
- **Advanced Search**: Filter by any field with multiple criteria
- **Real-time Search**: Instant search results as you type
- **Dashboard Analytics**: Visual charts and statistics
- **Audit Logging**: Complete audit trail for all changes
- **Export History**: Track all import/export operations

### Security Features
- **CSRF Protection**: All forms protected against cross-site request forgery
- **Input Sanitization**: All user input properly sanitized and validated
- **SQL Injection Prevention**: Prepared statements throughout the application
- **XSS Protection**: Output encoding for all displayed data
- **Secure Headers**: Security headers implemented
- **File Upload Security**: Secure file upload handling

## Requirements

- **Web Server**: Apache/Nginx with PHP 7.4+ or PHP 8.0+
- **Database**: MariaDB 10.3+ or MySQL 5.7+
- **PHP Extensions**: PDO, PDO_MySQL, LDAP (optional), Fileinfo
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)

## Installation

### 1. Download and Extract
```bash
wget https://github.com/your-repo/it-inventory/archive/main.zip
unzip main.zip
cd it-inventory
```

### 2. Database Setup
```bash
# Create database and user in MariaDB/MySQL
mysql -u root -p
CREATE DATABASE it_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON it_inventory.* TO 'inventory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run the setup script
php setup.php
```

### 3. Configuration
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_inventory');
define('DB_USER', 'inventory_user');
define('DB_PASS', 'your_secure_password');
```

### 4. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/it-inventory;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 5. File Permissions
```bash
# Set appropriate permissions
chmod 755 -R .
chmod 644 *.php
chmod 644 config/*.php
chmod 644 includes/*.php
chmod 644 classes/*.php
chmod 644 sql/*.sql
chmod 755 assets/
chmod 755 assets/css/
chmod 755 assets/js/
```

## Usage

### First Login
1. Navigate to `http://your-domain/login.php`
2. Login with default credentials:
   - Username: `admin`
   - Password: `admin123`
3. **IMPORTANT**: Change the admin password immediately after first login

### Adding Inventory Items
1. Click "Add Item" in the sidebar
2. Fill in required fields (marked with *)
3. Select appropriate use case and location
4. Add optional information like warranty dates, purchase details
5. Click "Add Item" to save

### Importing CSV Data
1. Navigate to Import CSV
2. Download the sample CSV file to see the required format
3. Prepare your CSV file with the correct headers
4. Upload the file and review the import results
5. Check for any errors and fix them if necessary

### User Management (Admin Only)
1. Navigate to Users in the Administration section
2. Click "Add User" to create new users
3. Assign appropriate roles and groups
4. For local users, passwords are auto-generated
5. Users can be edited, deactivated, or deleted

### Exporting Data
1. Navigate to Export CSV
2. Apply filters to select specific data
3. Preview the first 10 rows
4. Click "Download CSV" to export the full dataset
5. Files are downloaded with timestamp in filename

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

## Security Considerations

### Production Deployment
1. **HTTPS Only**: Always use HTTPS in production
2. **Strong Passwords**: Enforce strong password policies
3. **Regular Updates**: Keep PHP, MariaDB, and all dependencies updated
4. **Database Security**: Use strong database passwords and limit access
5. **File Permissions**: Ensure proper file permissions are set
6. **Backup Strategy**: Implement regular database and file backups

### Security Headers
The application includes security headers:
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Strict-Transport-Security (HTTPS only)
- Content-Security-Policy

## Troubleshooting

### Common Issues

#### Database Connection Failed
- Check database credentials in `config/database.php`
- Ensure MariaDB/MySQL is running
- Verify database user has proper permissions

#### Login Issues
- Check if account is locked (too many failed attempts)
- Verify username and password are correct
- For LDAP users, check LDAP configuration and connectivity

#### CSV Import Fails
- Verify CSV format matches the required structure
- Check file size limits (default: 10MB)
- Ensure dates are in YYYY-MM-DD format
- Check for duplicate serial/property numbers

#### File Upload Issues
- Check PHP upload_max_filesize and post_max_size settings
- Verify file permissions on upload directories
- Check for proper file extensions

### Debug Mode
For development, you can enable error reporting:
```php
// Add to config/database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## API Documentation

The system includes REST API endpoints for integration:

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/check` - Check authentication status

### Inventory Items
- `GET /api/items` - List items (with filters)
- `POST /api/items` - Create new item
- `GET /api/items/{id}` - Get item details
- `PUT /api/items/{id}` - Update item
- `DELETE /api/items/{id}` - Delete item

### Users
- `GET /api/users` - List users (admin only)
- `POST /api/users` - Create user (admin only)
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user (admin only)

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Check the wiki for additional documentation
- Review the troubleshooting section above

## Changelog

### Version 1.0.0 (Initial Release)
- Basic inventory management
- User authentication (local and LDAP)
- CSV import/export
- Search and filtering
- Dashboard analytics
- User management
- Audit logging

---

**Note**: This is a comprehensive inventory management system designed for IT departments. Always test in a development environment before deploying to production.