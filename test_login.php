<?php
// Test script to validate login and create inventory entry

// Database connection
$host = 'localhost';
$dbname = 'it_inventory';
$username = 'it_inventory_user';
$password = 'SecurePass123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful.\n";
    
    // Test login
    $testUsername = 'admin';
    $testPassword = 'admin123';
    
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($testPassword, $user['password_hash'])) {
        echo "✅ Login test successful for user: admin\n";
        
        // Create the requested inventory entry
        $inventoryData = [
            'make' => 'Dell',
            'model' => 'T5500',
            'serial_number' => '123456',
            'property_number' => 'PT12345',
            'warranty_end_date' => '2027-01-11',
            'excess_date' => '2030-01-11',
            'use_case' => 'Desktop',
            'location_id' => 1, // Default location
            'on_site' => 'On Site',
            'description' => 'Test entry for validation',
            'created_by' => $user['id']
        ];
        
        // Check for duplicates
        $checkSerial = $pdo->prepare("SELECT id FROM inventory WHERE serial_number = ?");
        $checkSerial->execute([$inventoryData['serial_number']]);
        
        $checkProperty = $pdo->prepare("SELECT id FROM inventory WHERE property_number = ?");
        $checkProperty->execute([$inventoryData['property_number']]);
        
        if ($checkSerial->rowCount() > 0 || $checkProperty->rowCount() > 0) {
            echo "⚠️  Entry already exists with these serial/property numbers.\n";
        } else {
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
            
            echo "✅ Inventory entry created successfully:\n";
            echo "   Make: {$inventoryData['make']}\n";
            echo "   Model: {$inventoryData['model']}\n";
            echo "   Serial: {$inventoryData['serial_number']}\n";
            echo "   Property: {$inventoryData['property_number']}\n";
            echo "   Warranty: {$inventoryData['warranty_end_date']}\n";
            echo "   Excess: {$inventoryData['excess_date']}\n";
            echo "   Use Case: {$inventoryData['use_case']}\n";
            
            // Verify the entry was created
            $verifyStmt = $pdo->prepare("SELECT * FROM inventory WHERE serial_number = ?");
            $verifyStmt->execute([$inventoryData['serial_number']]);
            $createdEntry = $verifyStmt->fetch();
            
            if ($createdEntry) {
                echo "✅ Entry verified in database with ID: {$createdEntry['id']}\n";
            }
        }
        
    } else {
        echo "❌ Login test failed - invalid credentials or user not found\n";
        
        // Create admin user if it doesn't exist
        $createAdmin = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, auth_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $createAdmin->execute(['admin', 'admin@localhost', $passwordHash, 'admin', 'local']);
        
        echo "✅ Admin user created with credentials: admin / admin123\n";
        echo "Please run test again.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please run setup.sh to initialize the database.\n";
}
?>