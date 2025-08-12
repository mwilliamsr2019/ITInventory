<?php
// System validation script for IT Inventory Management

echo "=== IT Inventory Management System Validation ===\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'it_inventory';
$username = 'it_inventory_user';
$password = 'SecurePass123!';

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Database connection successful\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "   ✅ Database '$dbname' created/exists\n";
    
    // Use the database
    $pdo->exec("USE $dbname");
    
    // Import schema
    echo "2. Setting up database schema...\n";
    $schema = file_get_contents('database/schema.sql');
    $pdo->exec($schema);
    echo "   ✅ Database schema imported\n";
    
    // Test admin user
    echo "3. Testing admin user credentials...\n";
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "   ✅ Admin user exists with ID: {$admin['id']}\n";
        echo "   ✅ Username: {$admin['username']}\n";
        echo "   ✅ Role: {$admin['role']}\n";
    } else {
        echo "   ❌ Admin user not found\n";
    }
    
    // Test inventory creation
    echo "4. Creating test inventory entry...\n";
    $inventoryData = [
        'make' => 'Dell',
        'model' => 'T5500',
        'serial_number' => '123456',
        'property_number' => 'PT12345',
        'warranty_end_date' => '2027-01-11',
        'excess_date' => '2030-01-11',
        'use_case' => 'Desktop',
        'location_id' => 1,
        'on_site' => 'On Site',
        'description' => 'Test Dell T5500 workstation',
        'created_by' => $admin['id'] ?? 1
    ];
    
    // Check for duplicates
    $checkSerial = $pdo->prepare("SELECT id FROM inventory WHERE serial_number = ?");
    $checkSerial->execute([$inventoryData['serial_number']]);
    
    $checkProperty = $pdo->prepare("SELECT id FROM inventory WHERE property_number = ?");
    $checkProperty->execute([$inventoryData['property_number']]);
    
    if ($checkSerial->rowCount() === 0 && $checkProperty->rowCount() === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO inventory 
            (make, model, serial_number, property_number, warranty_end_date, excess_date, use_case, location_id, on_site, description, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $inventoryData['make'],
            $inventoryData['model'],
            $inventoryData['serial_number'],
            $inventoryData['property_number'],
            $inventoryData['warranty_end_date'],
            $inventoryData['excess_date'],
            $inventoryData['use_case'],
            $inventoryData['location_id'],
            $inventoryData['on_site'],
            $inventoryData['description'],
            $inventoryData['created_by']
        ]);
        
        $entryId = $pdo->lastInsertId();
        echo "   ✅ Test entry created with ID: $entryId\n";
        echo "   ✅ Make: {$inventoryData['make']}\n";
        echo "   ✅ Model: {$inventoryData['model']}\n";
        echo "   ✅ Serial: {$inventoryData['serial_number']}\n";
        echo "   ✅ Property: {$inventoryData['property_number']}\n";
        echo "   ✅ Warranty: {$inventoryData['warranty_end_date']}\n";
        echo "   ✅ Excess: {$inventoryData['excess_date']}\n";
        echo "   ✅ Use Case: {$inventoryData['use_case']}\n";
    } else {
        echo "   ⚠️  Test entry already exists (duplicate serial/property)\n";
    }
    
    // Verify entry exists
    $verifyStmt = $pdo->prepare("
        SELECT i.*, l.location_name, u.username as created_by_user 
        FROM inventory i 
        LEFT JOIN locations l ON i.location_id = l.id 
        LEFT JOIN users u ON i.created_by = u.id 
        WHERE i.serial_number = ?
    ");
    $verifyStmt->execute([$inventoryData['serial_number']]);
    $createdEntry = $verifyStmt->fetch();
    
    if ($createdEntry) {
        echo "\n5. ✅ Entry verified in database:\n";
        echo "   ID: {$createdEntry['id']}\n";
        echo "   Make: {$createdEntry['make']}\n";
        echo "   Model: {$createdEntry['model']}\n";
        echo "   Serial: {$createdEntry['serial_number']}\n";
        echo "   Property: {$createdEntry['property_number']}\n";
        echo "   Location: {$createdEntry['location_name']}\n";
        echo "   Created by: {$createdEntry['created_by_user']}\n";
    }
    
    // Test login API
    echo "\n6. Testing login API...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/auth.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'admin123']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "   ✅ Login API working correctly\n";
    } else {
        echo "   ❌ Login API returned HTTP code: $httpCode\n";
    }
    
    echo "\n=== Validation Complete ===\n";
    echo "System is ready for use at: http://localhost/\n";
    echo "Login credentials: admin / admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Please ensure MariaDB/MySQL is running and create the database/user:\n";
    echo "CREATE DATABASE it_inventory;\n";
    echo "CREATE USER 'it_inventory_user'@'localhost' IDENTIFIED BY 'secure_password';\n";
    echo "GRANT ALL PRIVILEGES ON it_inventory.* TO 'it_inventory