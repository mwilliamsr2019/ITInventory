# IT Inventory Management System

A comprehensive web-based inventory management system for IT systems built with PHP, MariaDB, and Bootstrap.

## Features

- **Complete Inventory Management**: Track make, model, serial numbers, property numbers, warranty dates, and more
- **User Authentication**: Local database and SSSD/AD integration support
- **User Management**: Add, edit, and remove users with role-based access control
- **CSV Import/Export**: Bulk data operations with validation
- **Search & Filtering**: Comprehensive search across all fields
- **Location Management**: Dynamic location creation and assignment
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Security**: Input validation, SQL injection prevention, XSS protection

## Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0+ with PDO MySQL extension
- **Database**: MariaDB 10.5+ or MySQL 8.0+
- **Browser**: Modern web browser with JavaScript enabled

## Installation

### Quick Setup

1. **Clone or extract the application** to your web server directory
2. **Run the setup script**:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```
3. **Edit configuration**:
   ```bash
   cp .env.example .env
   nano .env  # Edit database credentials
   ```
4. **Access the application**:
   - URL: http://your-server/
   - Default login: admin / admin123

### Manual Setup

1. **Install dependencies**:
   ```bash
   # Ubuntu/Debian
   sudo apt install apache2 mariadb-server php php-mysql php-pdo
   
   # CentOS/RHEL
   sudo yum install httpd mariadb-server php php-mysql php-pdo
   ```

2. **Create database and user**:
   ```sql
   CREATE DATABASE it_inventory;
   CREATE USER 'it_inventory_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON it_inventory.* TO 'it_inventory_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Import database schema**:
   ```bash
   mysql -u it_inventory_user -p it_inventory < database/schema.sql
   ```

4. **Configure web server**:
   - Point document root to the application directory
   - Enable `.htaccess` if using Apache

## Configuration

### Database Configuration
Edit the `.env` file:
```bash
DB_HOST=localhost
DB_NAME=it_inventory
DB_USER=it_inventory_user
DB_PASS=your_secure_password
```

### SSSD/AD Integration
For Active Directory authentication:
1. Install and configure SSSD on your server
2. Update `.env` with LDAP settings
3. Set user authentication type to 'sssd' or 'ad'

## Usage

### Adding Inventory Items
1. Login to the dashboard
2. Click "Add Item"
3. Fill in all required fields (marked with *)
4. Save the item

### CSV Operations
**Export**:
- Click "Export CSV" to download all inventory data

**Import**:
1. Prepare CSV with required columns: Make, Model, Serial Number, Property Number, Use Case, Location
2. Click "Import CSV"
3. Select your CSV file
4. Review any errors and confirm import

### User Management
- **Admin users**: Full system access
- **Manager users**: Can add/edit inventory, view reports
- **Regular users**: Can view inventory, update assigned items

## Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: HTML entities encoding and input sanitization
- **Password Security**: Bcrypt hashing for passwords
- **Session Management**: Secure token-based authentication
- **Input Validation**: Server-side validation for all inputs
- **File Upload Security**: Strict file type validation for CSV imports

## API Endpoints

### Authentication
- `POST /api/auth.php` - User login

### Inventory Management
- `GET /api/inventory.php` - Get all inventory items
- `POST /api/inventory.php` - Create new inventory item
- `PUT /api/inventory.php` - Update inventory item
- `DELETE /api/inventory.php` - Delete inventory item

### CSV Operations
- `GET /api/csv_handler.php` - Export inventory as CSV
- `POST /api/csv_handler.php` - Import inventory from CSV

### Locations
- `GET /api/locations.php` - Get all locations
- `POST /api/locations.php` - Create new location

## Database Schema

### Core Tables
- **users**: User accounts and authentication
- **inventory**: IT equipment inventory
- **locations**: Physical locations
- **groups**: User groups for SSSD/AD integration
- **user_groups**: User-group mapping
- **audit_log**: Change tracking

## Troubleshooting

### Common Issues

**Database Connection Error**:
- Verify database credentials in `.env`
- Check if MariaDB service is running
- Ensure user has proper permissions

**Permission Denied**:
- Set proper file permissions: `chmod -R 755 .`
- Ensure web server has write access to uploads directory

**CSV Import Fails**:
- Check CSV format matches requirements
- Verify file encoding (UTF-8 recommended)
- Check for duplicate serial/property numbers

## Security Checklist

- [ ] Change default admin password
- [ ] Use HTTPS in production
- [ ] Enable database SSL connections
- [ ] Configure proper firewall rules
- [ ] Set secure file permissions
- [ ] Enable PHP security modules
- [ ] Regular security updates
- [ ] Backup database regularly

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review server error logs
3. Verify all requirements are met
4. Ensure database connectivity

## License

This project is provided as-is for educational and internal use. Please ensure compliance with your organization's security policies.
