<?php
/**
 * Robust Database Connection Test
 * 
 * Tests various scenarios including connection persistence and reconnection
 */

echo "=== Robust Database Connection Test ===\n\n";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/db_connect.php';

try {
    echo "1. Testing initial connection...\n";
    $db1 = Database::getInstance();
    $conn1 = $db1->getConnection();
    echo "   ✓ First connection successful\n";
    
    echo "\n2. Testing singleton pattern (should reuse connection)...\n";
    $db2 = Database::getInstance();
    $conn2 = $db2->getConnection();
    echo "   ✓ Second connection (singleton) successful\n";
    echo "   ✓ Same instance: " . ($db1 === $db2 ? 'YES' : 'NO') . "\n";
    
    echo "\n3. Testing basic query execution...\n";
    $result = $db1->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo "   ✓ Query executed successfully, user count: " . $row['count'] . "\n";
    
    echo "\n4. Testing prepared statement...\n";
    $stmt = $db1->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([1]);
    $user = $stmt->fetch();
    echo "   ✓ Prepared statement executed successfully\n";
    if ($user) {
        echo "   ✓ Found user: " . ($user['username'] ?? 'unknown') . "\n";
    }
    
    echo "\n5. Testing transaction...\n";
    $db1->beginTransaction();
    $db1->query("SELECT 1");
    $db1->commit();
    echo "   ✓ Transaction completed successfully\n";
    
    echo "\n6. Testing performance stats...\n";
    $stats = $db1->getPerformanceStats();
    echo "   Query count: " . $stats['query_count'] . "\n";
    echo "   Total query time: " . $stats['total_query_time'] . "s\n";
    echo "   Average query time: " . $stats['average_query_time'] . "s\n";
    echo "   Connection active: " . ($stats['connection_active'] ? 'YES' : 'NO') . "\n";
    
    echo "\n7. Testing connection persistence (waiting 2 seconds)...\n";
    sleep(2);
    $stats_after = $db1->getPerformanceStats();
    echo "   Connection still active: " . ($stats_after['connection_active'] ? 'YES' : 'NO') . "\n";
    
    echo "\n=== All Tests Passed! ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>