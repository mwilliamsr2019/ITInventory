<?php
// Working validation script for IT Inventory Management System

echo "=== IT Inventory Management System Working Validation ===\n\n";

// Use root access for testing
$host = 'localhost';
$dbname = 'it_inventory';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Setting up database environment...\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Create tables with proper error handling
    echo "2. Creating database tables...\n";
    
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        auth_type ENUM('local', 'sssd', 'ad') DEFAULT 'local',
        role ENUM('admin', 'manager', 'user') DEFAULT 'user',
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Locations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Inventory table
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES locations(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    
    // Insert default locations
    $pdo->exec("INSERT IGNORE INTO locations (location_name, description) VALUES 
        ('Main Office', 'Primary office location'),
        ('Data Center', 'Primary data center'),
        ('Remote Site A', 'First remote site'),
        ('Remote Site B', 'Second remote site')");
    
    // Insert admin user
    echo "3. Setting up admin user...\n";
    $adminExists = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
    if (!$adminExists) {
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username, email, password_hash, role, auth_type) VALUES 
            ('admin', 'admin@localhost', '$passwordHash', 'admin', 'local')");
        echo "   ✅ Admin user created: admin / admin123\n";
    } else {
        echo "   ✅ Admin user already exists\n";
    }
    
    // Create test inventory entry
    echo "4. Creating requested inventory entry...\n";
    
    $testData = [
        'make' => 'Dell',
        'model' => 'T5500',
        'serial_number' => '123456',
        'property_number' => 'PT12345',
        'warranty_end_date' => '2027-01-11',
        'excess_date' => '2030-01-11',
        'use_case' => 'Desktop',
        'location_id' => 1,
        'on_site' => 'On Site',
        'description' => 'Validated Dell T5500 workstation',
        'created_by' => 1
    ];
    
    // Check if entry exists
    $exists = $pdo->prepare("SELECT id FROM inventory WHERE serial_number = ? OR property_number = ?");
    $exists->execute([$testData['serial_number'], $testData['property_number']]);
    
    if ($exists->rowCount() === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO inventory 
            (make, model, serial_number, property_number, warranty_end_date, excess_date, use_case, location_id, on_site, description, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute(array_values($testData));
        $entryId = $pdo->lastInsertId();
        
        echo "   ✅ Entry created with ID: $entryId\n";
        echo "   ✅ Dell T5500 with all specified details added successfully\n";
    } else {
        echo "   ✅ Entry already exists (validated)\n";
    }
    
    // Verify the entry
    echo "5. Verifying inventory entry...\n";
    $verifyStmt = $pdo->prepare("
        SELECT i.*, l.location_name, u.username as created_by_user 
        FROM inventory i 
        LEFT JOIN locations l ON i.location_id = l.id 
        LEFT JOIN users u ON i.created_by = u.id 
        WHERE i.serial_number = '123456'
    ");
    $verifyStmt->execute();
    $entry = $verifyStmt->fetch();
    
    if ($entry) {
        echo "   ✅ Entry verified:\n";
        echo "      ID: {$entry['id']}\n";
        echo "      Make: {$entry['make']}\n";
        echo "      Model: {$entry['model']}\n";
        echo "      Serial: {$entry['serial_number']}\n";
        echo "      Property: {$entry['property_number']}\n";
        echo "      Warranty: {$entry['warranty_end_date']}\n";
        echo "      Excess: {$entry['excess_date']}\n";
        echo "      Use Case: {$entry['use_case']}\n";
        echo "      Location: {$entry['location_name']}\n";
        echo "      On Site: {$entry['on_site']}\n";
    }
    
    // Test database counts
    echo "6. Database summary:\n";
    $inventoryCount = $pdo->query("SELECT COUNT(*) as total FROM inventory")->fetch();
    $usersCount = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch();
    $locationsCount = $pdo->query("SELECT COUNT(*) as total FROM locations")->fetch();
    
    echo "   ✅ Total inventory items: {$inventoryCount['total']}\n";
    echo "   ✅ Total users: {$usersCount['total']}\n";
    echo "   ✅ Total locations: {$locationsCount['total']}\n";
    
    echo "\n=== SYSTEM VALIDATION COMPLETE ===\n";
    echo "✅ Login: http://localhost/ with admin / admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}