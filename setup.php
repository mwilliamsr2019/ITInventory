<?php
// Database setup script for IT Inventory Management System
// This script should be run once to set up the database and initial data

require_once 'config/database.php';
require_once 'includes/db_connect.php';

echo "IT Inventory Management System - Database Setup\n";
echo "================================================\n\n";

try {
    // Create database connection without selecting database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    echo "Creating database '" . DB_NAME . "'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully.\n\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Read and execute schema file
    echo "Creating database schema...\n";
    $schema = file_get_contents('sql/schema.sql');
    
    // Remove comments and split into individual statements
    $schema = preg_replace('/--.*$/m', '', $schema);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database schema created successfully.\n\n";
    
    // Verify setup
    echo "Verifying database setup...\n";
    
    // Check tables
    $tables = ['users', 'groups', 'user_groups', 'locations', 'inventory_items', 'audit_log', 'user_sessions'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' missing\n";
        }
    }
    
    echo "\n";
    
    // Check default data
    echo "Checking default data...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if ($adminExists) {
        echo "✓ Default admin user exists (username: admin, password: admin123)\n";
    } else {
        echo "✗ Default admin user missing\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM locations");
    $locationCount = $stmt->fetchColumn();
    echo "✓ $locationCount default locations created\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM groups");
    $groupCount = $stmt->fetchColumn();
    echo "✓ $groupCount default groups created\n";
    
    echo "\n";
    echo "Database setup completed successfully!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Configure your web server to point to this directory\n";
    echo "2. Update config/database.php with your database credentials\n";
    echo "3. Configure LDAP/SSSD settings in config/database.php if needed\n";
    echo "4. Access the application at http://your-domain/login.php\n";
    echo "5. Login with username: admin, password: admin123\n";
    echo "6. Change the admin password immediately after first login\n";
    
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check:\n";
    echo "1. Database server is running\n";
    echo "2. Database credentials in config/database.php are correct\n";
    echo "3. Database user has sufficient privileges\n";
    exit(1);
}
?>