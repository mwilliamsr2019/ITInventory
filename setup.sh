#!/bin/bash

# IT Inventory Management System Setup Script

echo "Setting up IT Inventory Management System..."

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo "This script should not be run as root for security reasons"
   exit 1
fi

# Check if MariaDB/MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "MariaDB/MySQL is not installed. Please install it first."
    echo "On Ubuntu/Debian: sudo apt install mariadb-server mariadb-client"
    echo "On CentOS/RHEL: sudo yum install mariadb-server mariadb"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install it first."
    echo "On Ubuntu/Debian: sudo apt install php php-mysql php-pdo"
    echo "On CentOS/RHEL: sudo yum install php php-mysql php-pdo"
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    echo "Please edit .env file with your database credentials"
fi

# Create database user and database
echo "Creating database and user..."
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS it_inventory;
CREATE USER IF NOT EXISTS 'it_inventory_user'@'localhost' IDENTIFIED BY 'SecurePass123!';
GRANT ALL PRIVILEGES ON it_inventory.* TO 'it_inventory_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import database schema
echo "Importing database schema..."
mysql -u it_inventory_user -p it_inventory < database/schema.sql

# Set proper permissions
echo "Setting file permissions..."
chmod 755 *.php
chmod 755 api/*.php
chmod 755 classes/*.php
chmod 755 config/*.php
chmod 755 js/*.js
chmod 644 .env

# Create uploads directory for CSV imports
mkdir -p uploads
chmod 755 uploads

echo "Setup completed successfully!"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your database credentials"
echo "2. Open http://localhost in your browser"
echo "3. Login with username: admin, password: admin123"
echo "4. Change the admin password immediately"
echo ""
echo "For production deployment:"
echo "1. Change all default passwords"
echo "2. Configure SSL/HTTPS"
echo "3. Set up proper firewall rules"
echo "4. Configure SSSD/AD integration if needed"
