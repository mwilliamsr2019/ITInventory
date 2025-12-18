<?php
// Validation script for IT Inventory Management System
// Tests code structure and functions without database connectivity

echo "IT Inventory Management System - Code Validation\n";
echo "================================================\n\n";

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

// Test 1: File Structure
echo "1. File Structure Tests\n";
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

// Test 2: Configuration File
echo "2. Configuration Tests\n";
echo "----------------------\n";
if (file_exists('config/database.php')) {
    include 'config/database.php';
    
    test("DB_HOST defined", defined('DB_HOST') && !empty(DB_HOST));
    test("DB_NAME defined", defined('DB_NAME') && !empty(DB_NAME));
    test("DB_USER defined", defined('DB_USER') && !empty(DB_USER));
    test("SESSION_TIMEOUT defined", defined('SESSION_TIMEOUT') && SESSION_TIMEOUT > 0);
    test("PASSWORD_MIN_LENGTH defined", defined('PASSWORD_MIN_LENGTH') && PASSWORD_MIN_LENGTH > 0);
    test("UPLOAD_MAX_SIZE defined", defined('UPLOAD_MAX_SIZE') && UPLOAD_MAX_SIZE > 0);
    test("CSV_MAX_ROWS defined", defined('CSV_MAX_ROWS') && CSV_MAX_ROWS > 0);
} else {
    test("Configuration file exists", false);
}
echo "\n";

// Test 3: PHP Syntax Validation
echo "3. PHP Syntax Tests\n";
echo "-------------------\n";
$phpFiles = [
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
    'setup.php'
];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        $syntaxOk = strpos($output, 'No syntax errors') !== false;
        test("PHP syntax in '$file'", $syntaxOk);
        if (!$syntaxOk) {
            echo "  Error: $output\n";
        }
    } else {
        test("PHP file '$file' exists", false);
    }
}
echo "\n";

// Test 4: Function Definitions
echo "4. Function Definition Tests\n";
echo "----------------------------\n";
if (file_exists('includes/functions.php')) {
    include 'config/database.php';
    include 'includes/functions.php';
    
    test("sanitizeInput function defined", function_exists('sanitizeInput'));
    test("validateEmail function defined", function_exists('validateEmail'));
    test("generateCSRFToken function defined", function_exists('generateCSRFToken'));
    test("validateCSRFToken function defined", function_exists('validateCSRFToken'));
    test("arrayToCsv function defined", function_exists('arrayToCsv'));
    test("csvToArray function defined", function_exists('csvToArray'));
    test("validateSerialNumber function defined", function_exists('validateSerialNumber'));
    test("validatePropertyNumber function defined", function_exists('validatePropertyNumber'));
    test("validateUseCase function defined", function_exists('validateUseCase'));
    test("logAudit function defined", function_exists('logAudit'));
    test("getPagination function defined", function_exists('getPagination'));
}
echo "\n";

// Test 5: Class Definitions
echo "5. Class Definition Tests\n";
echo "-------------------------\n";
if (file_exists('classes/User.php') && file_exists('includes/db_connect.php')) {
    include 'config/database.php';
    include 'includes/db_connect.php';
    include 'classes/User.php';
    
    test("User class defined", class_exists('User'));
    test("User::authenticate method exists", method_exists('User', 'authenticate'));
    test("User::logout method exists", method_exists('User', 'logout'));
    test("User::isLoggedIn method exists", method_exists('User', 'isLoggedIn'));
    test("User::requireLogin method exists", method_exists('User', 'requireLogin'));
    test("User::requireRole method exists", method_exists('User', 'requireRole'));
    test("User::changePassword method exists", method_exists('User', 'changePassword'));
}

if (file_exists('classes/Inventory.php')) {
    include 'classes/Inventory.php';
    
    test("Inventory class defined", class_exists('Inventory'));
    test("Inventory::addItem method exists", method_exists('Inventory', 'addItem'));
    test("Inventory::updateItem method exists", method_exists('Inventory', 'updateItem'));
    test("Inventory::deleteItem method exists", method_exists('Inventory', 'deleteItem'));
    test("Inventory::searchItems method exists", method_exists('Inventory', 'searchItems'));
    test("Inventory::getItemById method exists", method_exists('Inventory', 'getItemById'));
    test("Inventory::importFromCsv method exists", method_exists('Inventory', 'importFromCsv'));
    test("Inventory::getItemsForCsv method exists", method_exists('Inventory', 'getItemsForCsv'));
}
echo "\n";

// Test 6: Function Logic Tests
echo "6. Function Logic Tests\n";
echo "-----------------------\n";
if (function_exists('sanitizeInput')) {
    $testInput = '<script>alert("xss")</script>';
    $sanitized = sanitizeInput($testInput);
    test("Input sanitization", $sanitized !== $testInput && strpos($sanitized, '<') === false);
}

if (function_exists('validateEmail')) {
    test("Valid email validation", validateEmail('test@example.com'));
    test("Invalid email rejection", !validateEmail('invalid-email'));
}

if (function_exists('validateUseCase')) {
    test("Valid use case validation", validateUseCase('Desktop'));
    test("Invalid use case rejection", !validateUseCase('Invalid'));
}

if (function_exists('arrayToCsv')) {
    $testData = [['Name' => 'Test', 'Value' => '123']];
    $csv = arrayToCsv($testData);
    test("CSV generation", strpos($csv, 'Name,Value') !== false);
}
echo "\n";

// Test 7: SQL Schema Validation
echo "7. SQL Schema Tests\n";
echo "-------------------\n";
if (file_exists('sql/schema.sql')) {
    $schema = file_get_contents('sql/schema.sql');
    test("Schema file readable", $schema !== false);
    test("Schema contains CREATE TABLE", strpos($schema, 'CREATE TABLE') !== false);
    test("Schema contains users table", strpos($schema, 'users') !== false);
    test("Schema contains inventory_items table", strpos($schema, 'inventory_items') !== false);
    test("Schema contains audit_log table", strpos($schema, 'audit_log') !== false);
}
echo "\n";

// Test 8: CSS File Validation
echo "8. CSS File Tests\n";
echo "-----------------\n";
if (file_exists('assets/css/style.css')) {
    $css = file_get_contents('assets/css/style.css');
    test("CSS file readable", $css !== false);
    test("CSS contains styles", strlen($css) > 100);
    test("CSS contains custom properties", strpos($css, ':root') !== false);
}
echo "\n";

// Test 9: Configuration Values
echo "9. Configuration Value Tests\n";
echo "----------------------------\n";
if (defined('SESSION_TIMEOUT')) {
    test("Session timeout > 0", SESSION_TIMEOUT > 0);
    test("Session timeout <= 86400", SESSION_TIMEOUT <= 86400);
}

if (defined('PASSWORD_MIN_LENGTH')) {
    test("Password min length >= 6", PASSWORD_MIN_LENGTH >= 6);
    test("Password min length <= 20", PASSWORD_MIN_LENGTH <= 20);
}

if (defined('MAX_LOGIN_ATTEMPTS')) {
    test("Max login attempts > 0", MAX_LOGIN_ATTEMPTS > 0);
    test("Max login attempts <= 10", MAX_LOGIN_ATTEMPTS <= 10);
}

if (defined('UPLOAD_MAX_SIZE')) {
    test("Upload max size > 0", UPLOAD_MAX_SIZE > 0);
    test("Upload max size <= 50MB", UPLOAD_MAX_SIZE <= 50 * 1024 * 1024);
}
echo "\n";

// Summary
echo "Validation Summary\n";
echo "==================\n";
echo "Tests passed: $testsPassed/$testsTotal\n";
echo "Success rate: " . round(($testsPassed / $testsTotal) * 100, 1) . "%\n";

if ($testsPassed === $testsTotal) {
    echo "\n🎉 All validation tests passed!\n";
    echo "\nThe system appears to be properly structured and ready for deployment.\n";
    echo "Next steps:\n";
    echo "1. Set up your MariaDB/MySQL database\n";
    echo "2. Update config/database.php with your database credentials\n";
    echo "3. Run 'php setup.php' to create database tables\n";
    echo "4. Configure your web server to point to this directory\n";
    echo "5. Access the application at http://your-domain/login.php\n";
} else {
    echo "\n⚠️  Some validation tests failed. Please review the errors above.\n";
    echo "Common issues:\n";
    echo "- Missing files: Ensure all files were uploaded correctly\n";
    echo "- PHP syntax errors: Check for syntax errors in PHP files\n";
    echo "- Missing functions: Ensure all required functions are defined\n";
    echo "- Configuration issues: Check config/database.php settings\n";
}
?>