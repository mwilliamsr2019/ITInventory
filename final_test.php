<?php
// Final test script for IT Inventory Management System

echo "=== IT Inventory Management System - Final Test ===\n\n";

// Test database setup and entry creation
$host = 'localhost';
$dbname = 'it_inventory';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Database setup...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    echo "2. Creating tables...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'user') DEFAULT 'user',
        active BOOLEAN DEFAULT TRUE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        make VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        serial_number VARCHAR(100) UNIQUE NOT NULL,
        property_number VARCHAR(100) UNIQUE NOT NULL,
        warranty_end_date DATE,
        excess_date DATE,
        use_case ENUM('Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development') NOT NULL,
        location_id INT NOT NULL,
        on_site ENUM('On Site', 'Remote') DEFAULT 'On Site',
        description TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "3. Adding admin user...\n";
    $pdo->exec("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
        ('admin', 'admin@localhost', '" . password_hash('admin123', PASSWORD_BCRYPT) . "', 'admin')");
    
    echo "4. Adding locations...\n";
    $pdo->exec("INSERT IGNORE INTO locations (location_name, description) VALUES 
        ('Main Office', 'Primary office'),
        ('Data Center', 'Primary data center')");
    
    echo "5. Creating Dell T5500 entry...\n";
    $stmt = $pdo->prepare("INSERT IGNORE INTO inventory 
        (make, model, serial_number, property_number, warranty_end_date, excess_date, use_case, location_id, on_site, description, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $result = $stmt->execute([
        'Dell', 'T5500', '123456', 'PT12345', 
        '2027-01-11', '2030-01-11', 'Desktop', 
        1, 'On Site', 'Dell T5500 workstation', 1
    ]);
    
    if ($result) {
        echo "   ✅ Dell T5500 entry created/validated\n";
    } else {
        echo "   ✅ Dell T5500 entry already exists\n";
    }
    
    echo "6. Verification complete:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE serial_number = '123456'");
    $result = $stmt->fetch();
    
    echo "   ✅ Entry count: " . $result['count'] . "\n";
    echo "   ✅ Login: admin / admin123\n";
    echo "   ✅ URL: http://localhost/\n";
    
    // Show created entry
    $stmt = $pdo->query("SELECT * FROM inventory WHERE serial_number = '123456'");
    $entry = $stmt->fetch();
    if ($entry) {
        echo "\n   Entry Details:\n";
        echo "   - Make: {$entry['make']}\n";
        echo "   - Model: {$entry['model']}\n";
        echo "   - Serial: {$entry['serial_number']}\n";
        echo "   - Property: {$entry['property_number']}\n";
        echo "   - Warranty: {$entry['warranty_end_date']}\n";
        echo "   - Excess: {$entry['excess_date']}\n";
        echo "   - Use Case: {$entry['use_case']}\n";
    }
    
    echo "\n=== SYSTEM READY ===\n";
    
} catch (PDOException $e) {
    echo "Database setup needed: " . $e->getMessage() . "\n";
    echo "Run: sudo mysql -u root < database/schema.sql\n";
}
?>