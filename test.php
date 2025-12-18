<?php
// Basic functionality test script for IT Inventory Management System

require_once 'config/database.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/Inventory.php';

echo "IT Inventory Management System - Functionality Test\n";
echo "===================================================\n\n";

$testsPassed = 0;
$testsTotal = 0;

function test($description, $condition) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    
    if ($condition) {
        echo "✓ $description\n";
        $testsPassed++;
    } else {
        echo "✗ $description\n";
    }
}

function testException($description, $callback) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    
    try {
        $callback();
        echo "✗ $description (Expected exception)\n";
    } catch (Exception $e) {
        echo "✓ $description (Exception caught as expected)\n";
        $testsPassed++;
    }
}

// Test 1: Database Connection
echo "1. Database Connection Tests\n";
echo "----------------------------\n";
try {
    $db = Database::getInstance();
    test("Database connection established", $db->getConnection() instanceof PDO);
    
    $stmt = $db->prepare("SELECT 1");
    $result = $stmt->execute();
    test("Database query execution", $result === true);
} catch (Exception $e) {
    test("Database connection", false);
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Configuration Constants
echo "2. Configuration Tests\n";
echo "----------------------\n";
test("Database host configured", defined('DB_HOST') && !empty(DB_HOST));
test("Database name configured", defined('DB_NAME') && !empty(DB_NAME));
test("Session timeout configured", defined('SESSION_TIMEOUT') && SESSION_TIMEOUT > 0);
test("Password minimum length configured", defined('PASSWORD_MIN_LENGTH') && PASSWORD_MIN_LENGTH > 0);
echo "\n";

// Test 3: Utility Functions
echo "3. Utility Function Tests\n";
echo "-------------------------\n";
test("sanitizeInput function exists", function_exists('sanitizeInput'));
test("validateEmail function exists", function_exists('validateEmail'));
test("generateCSRFToken function exists", function_exists('generateCSRFToken'));
test("arrayToCsv function exists", function_exists('arrayToCsv'));

// Test sanitizeInput
$testInput = '<script>alert("xss")</script>';
$sanitized = sanitizeInput($testInput);
test("Input sanitization", $sanitized !== $testInput && strpos($sanitized, '<') === false);

// Test email validation
test("Valid email validation", validateEmail('test@example.com'));
test("Invalid email rejection", !validateEmail('invalid-email'));

// Test CSV generation
$testData = [['Name' => 'Test', 'Value' => '123']];
$csv = arrayToCsv($testData);
test("CSV generation", strpos($csv, 'Name,Value') !== false);
echo "\n";

// Test 4: User Class
echo "4. User Class Tests\n";
echo "-------------------\n";
test("User class exists", class_exists('User'));
test("User authentication method exists", method_exists('User', 'authenticate'));
test("User logout method exists", method_exists('User', 'logout'));
test("isLoggedIn method exists", method_exists('User', 'isLoggedIn'));
echo "\n";

// Test 5: Inventory Class
echo "5. Inventory Class Tests\n";
echo "------------------------\n";
test("Inventory class exists", class_exists('Inventory'));
test("addItem method exists", method_exists('Inventory', 'addItem'));
test("searchItems method exists", method_exists('Inventory', 'searchItems'));
test("importFromCsv method exists", method_exists('Inventory', 'importFromCsv'));
test("getItemsForCsv method exists", method_exists('Inventory', 'getItemsForCsv'));
echo "\n";

// Test 6: Database Schema
echo "6. Database Schema Tests\n";
echo "------------------------\n";
try {
    $db = Database::getInstance();
    
    // Check if tables exist
    $tables = ['users', 'groups', 'user_groups', 'locations', 'inventory_items', 'audit_log', 'user_sessions'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        test("Table '$table' exists", $stmt->fetch() !== false);
    }
    
    // Check if default data exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    test("Default admin user exists", $adminExists);
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM locations");
    $stmt->execute();
    $locationCount = $stmt->fetchColumn();
    test("Default locations exist", $locationCount > 0);
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM groups");
    $stmt->execute();
    $groupCount = $stmt->fetchColumn();
    test("Default groups exist", $groupCount > 0);
    
} catch (Exception $e) {
    test("Database schema check", false);
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Security Features
echo "7. Security Tests\n";
echo "-----------------\n";
test("CSRF token generation", strlen(generateCSRFToken()) > 0);
test("CSRF token validation", validateCSRFToken($_SESSION[CSRF_TOKEN_NAME] ?? ''));
test("Password validation function exists", function_exists('validatePassword'));

// Test password validation
$weakPassword = '123';
$strongPassword = 'StrongP@ssw0rd!';
test("Weak password rejection", !validatePassword($weakPassword));
test("Strong password acceptance", validatePassword($strongPassword));
echo "\n";

// Test 8: File Structure
echo "8. File Structure Tests\n";
echo "-----------------------\n";
$requiredFiles = [
    'config/database.php',
    'includes/db_connect.php',
    'includes/functions.php',
    'classes/User.php',
    'classes/Inventory.php',
    'login.php',
    'dashboard.php',
    'inventory.php',
    'add_item.php',
    'import.php',
    'export.php',
    'users.php',
    'profile.php',
    'logout.php',
    'setup.php',
    'sql/schema.sql',
    'assets/css/style.css',
    'README.md'
];

foreach ($requiredFiles as $file) {
    test("File '$file' exists", file_exists($file));
}
echo "\n";

// Test 9: PHP Version and Extensions
echo "9. PHP Environment Tests\n";
echo "------------------------\n";
test("PHP version >= 7.4", PHP_VERSION_ID >= 70400);
test("PDO extension loaded", extension_loaded('pdo'));
test("PDO MySQL extension loaded", extension_loaded('pdo_mysql'));
test("JSON extension loaded", extension_loaded('json'));
test("Session extension loaded", extension_loaded('session'));
echo "\n";

// Summary
echo "Test Summary\n";
echo "============\n";
echo "Tests passed: $testsPassed/$testsTotal\n";
echo "Success rate: " . round(($testsPassed / $testsTotal) * 100, 1) . "%\n";

if ($testsPassed === $testsTotal) {
    echo "\n🎉 All tests passed! The system appears to be properly configured.\n";
    echo "\nNext steps:\n";
    echo "1. Run 'php setup.php' to set up the database\n";
    echo "2. Configure your web server to point to this directory\n";
    echo "3. Access the application at http://your-domain/login.php\n";
    echo "4. Login with username: admin, password: admin123\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
    echo "Common issues:\n";
    echo "- Database connection: Check credentials in config/database.php\n";
    echo "- Missing files: Ensure all files were uploaded correctly\n";
    echo "- PHP extensions: Install required PHP extensions\n";
    echo "- Database schema: Run 'php setup.php' to create database tables\n";
}
?>