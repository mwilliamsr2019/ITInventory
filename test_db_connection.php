<?php
/**
 * Database Connection Test Script
 * 
 * This script helps diagnose database connection issues
 */

echo "=== Database Connection Diagnostic Tool ===\n\n";

// Test 1: Check if MySQL is running
echo "1. Checking MySQL service status...\n";
exec("pgrep mysql", $output, $return_var);
if ($return_var === 0) {
    echo "   ✓ MySQL process is running\n";
} else {
    echo "   ✗ MySQL process not found\n";
}

// Test 2: Check PHP MySQL extensions
echo "\n2. Checking PHP MySQL extensions...\n";
$extensions = ['pdo_mysql', 'mysqli', 'mysqlnd'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext is loaded\n";
    } else {
        echo "   ✗ $ext is NOT loaded\n";
    }
}

// Test 3: Test basic connection parameters
echo "\n3. Testing connection parameters...\n";
require_once __DIR__ . '/config/database.php';

echo "   Host: " . DB_HOST . "\n";
echo "   Database: " . DB_NAME . "\n";
echo "   User: " . DB_USER . "\n";
echo "   Charset: " . DB_CHARSET . "\n";

// Test 4: Attempt direct PDO connection
echo "\n4. Testing direct PDO connection...\n";
try {
    $dsn = sprintf(
        "mysql:host=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
    ]);
    
    echo "   ✓ Direct PDO connection successful!\n";
    
    // Test 5: Check MySQL server variables
    echo "\n5. Checking MySQL server variables...\n";
    $vars = $pdo->query("SHOW VARIABLES LIKE 'wait_timeout'")->fetch();
    echo "   wait_timeout: " . $vars['Value'] . " seconds\n";
    
    $vars = $pdo->query("SHOW VARIABLES LIKE 'interactive_timeout'")->fetch();
    echo "   interactive_timeout: " . $vars['Value'] . " seconds\n";
    
    $vars = $pdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch();
    echo "   max_allowed_packet: " . $vars['Value'] . " bytes\n";
    
    $pdo = null;
    
} catch (PDOException $e) {
    echo "   ✗ Direct PDO connection failed: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getCode() . "\n";
}

// Test 6: Test with our Database class
echo "\n6. Testing Database class connection...\n";
try {
    require_once __DIR__ . '/includes/db_connect.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "   ✓ Database class connection successful!\n";
    
    // Test query
    $result = $conn->query("SELECT 1 as test");
    $row = $result->fetch();
    echo "   ✓ Test query executed successfully: " . $row['test'] . "\n";
    
} catch (Exception $e) {
    echo "   ✗ Database class connection failed: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getCode() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
?>