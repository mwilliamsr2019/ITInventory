-- IT Inventory Management System Database Schema
-- MariaDB Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS it_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_inventory;

-- Users table for local authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    auth_type ENUM('local', 'ldap', 'sssd') DEFAULT 'local',
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_auth_type (auth_type)
);

-- Groups table for role-based access control
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    ldap_group_dn VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User-Group relationships
CREATE TABLE IF NOT EXISTS user_groups (
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location_name (name)
);

-- Inventory items table
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    property_number VARCHAR(100) UNIQUE NOT NULL,
    warranty_end_date DATE NULL,
    excess_date DATE NULL,
    use_case ENUM('Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development') NOT NULL,
    location_id INT NOT NULL,
    on_site BOOLEAN DEFAULT TRUE,
    description TEXT,
    assigned_to VARCHAR(100) NULL,
    purchase_date DATE NULL,
    purchase_cost DECIMAL(10,2) NULL,
    vendor VARCHAR(100) NULL,
    status ENUM('active', 'retired', 'excess', 'repair') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_serial_number (serial_number),
    INDEX idx_property_number (property_number),
    INDEX idx_use_case (use_case),
    INDEX idx_location (location_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
);

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('insert', 'update', 'delete') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    user_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_audit_table_record (table_name, record_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_timestamp (timestamp)
);

-- Session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_user (user_id),
    INDEX idx_session_expires (expires_at)
);

-- Insert default locations
INSERT INTO locations (name, description) VALUES
('Main Office', 'Primary office location'),
('Data Center', 'Primary data center'),
('Remote Office 1', 'First remote office location'),
('Remote Office 2', 'Second remote office location'),
('Storage Facility', 'Equipment storage facility');

-- Insert default groups
INSERT INTO groups (name, description) VALUES
('Administrators', 'System administrators with full access'),
('IT Managers', 'IT managers with elevated privileges'),
('IT Staff', 'Regular IT staff members'),
('Viewers', 'Read-only access users');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, email, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System', 'Administrator', 'admin');

-- Insert admin into administrators group
INSERT INTO user_groups (user_id, group_id) 
SELECT u.id, g.id FROM users u, groups g WHERE u.username = 'admin' AND g.name = 'Administrators';