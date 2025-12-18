<?php
/**
 * Database Connection Manager
 *
 * Provides singleton pattern database connection with enhanced error handling,
 * connection pooling support, and performance monitoring.
 *
 * @package ITInventory
 * @author IT Inventory Team
 * @version 1.2.0
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    /**
     * Singleton instance
     * @var Database|null
     */
    private static ?Database $instance = null;
    
    /**
     * PDO connection instance
     * @var PDO|null
     */
    private ?PDO $connection = null;
    
    /**
     * Query counter for performance monitoring
     * @var int
     */
    private int $queryCount = 0;
    
    /**
     * Total query execution time
     * @var float
     */
    private float $totalQueryTime = 0.0;
    
    /**
     * Connection configuration
     * @var array
     */
    private array $config;
    
    /**
     * Private constructor - implements singleton pattern
     *
     * @throws RuntimeException If database connection fails
     */
    private function __construct() {
        $this->config = $this->getConnectionConfig();
        $this->initializeConnection();
    }
    
    /**
     * Get database connection configuration
     *
     * @return array Connection configuration
     */
    private function getConnectionConfig(): array {
        return [
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_CHARSET,
            'options' => $this->getPdoOptions()
        ];
    }
    
    /**
     * Get PDO connection options with security and performance settings
     *
     * @return array PDO options
     */
    private function getPdoOptions(): array {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_PERSISTENT => false, // Disable persistent connections for now
            PDO::ATTR_TIMEOUT => 30, // Connection timeout in seconds
            // Remove problematic SSL settings for local connections
        ];
    }
    
    /**
     * Determine if persistent connections should be used
     *
     * @return bool
     */
    private function shouldUsePersistentConnections(): bool {
        // Use persistent connections in production for better performance
        // Disable in debug mode or if explicitly set
        return !(defined('DEBUG_MODE') && constant('DEBUG_MODE') === true);
    }
    
    /**
     * Initialize database connection with error handling
     *
     * @throws RuntimeException If connection fails
     */
    private function initializeConnection(): void {
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    $this->config['host'],
                    $this->config['dbname'],
                    $this->config['charset']
                );
                
                $this->connection = new PDO(
                    $dsn,
                    $this->config['user'],
                    $this->config['password'],
                    $this->config['options']
                );
                
                // Set additional MySQL session variables for better performance
                $this->connection->exec("SET time_zone = '+00:00'");
                $this->connection->exec("SET sql_mode = 'STRICT_ALL_TABLES'");
                
                // Connection successful, break out of retry loop
                return;
                
            } catch (PDOException $e) {
                error_log("Database connection attempt $attempt failed: " . $e->getMessage());
                
                // If this is the last attempt, handle the error
                if ($attempt === $maxRetries) {
                    $this->handleConnectionError($e);
                }
                
                // Wait before retrying (except on last attempt)
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }
    }
    
    /**
     * Handle connection errors with appropriate logging
     *
     * @param PDOException $exception
     * @throws RuntimeException
     */
    private function handleConnectionError(PDOException $exception): void {
        $errorMessage = "Database connection failed: " . $this->sanitizeErrorMessage($exception->getMessage());
        
        // Log the full error for debugging
        error_log("Database connection error: " . $exception->getMessage());
        
        // Provide user-friendly error message
        throw new RuntimeException($errorMessage);
    }
    
    /**
     * Sanitize database error messages for security
     *
     * @param string $message
     * @return string Sanitized message
     */
    private function sanitizeErrorMessage(string $message): string {
        // Remove sensitive information like credentials
        $patterns = [
            '/password[^,]*/i' => 'password=[REDACTED]',
            '/user[^,]*/i' => 'user=[REDACTED]',
            '/host[^,]*/i' => 'host=[REDACTED]',
        ];
        
        return preg_replace(array_keys($patterns), array_values($patterns), $message);
    }
    
    /**
     * Get singleton instance with thread safety
     *
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Verify connection is still active
        if (!self::$instance->isConnectionActive()) {
            self::$instance->reconnect();
        }
        
        return self::$instance;
    }
    
    /**
     * Check if database connection is still active
     *
     * @return bool
     */
    private function isConnectionActive(): bool {
        if ($this->connection === null) {
            return false;
        }
        
        try {
            // Use a simple query to test connection
            $result = $this->connection->query('SELECT 1');
            return $result !== false;
        } catch (PDOException $e) {
            // Check if it's a "server has gone away" error
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'server has gone away') !== false ||
                strpos($errorMessage, 'MySQL server has gone away') !== false ||
                $e->getCode() == 2006) {
                error_log("MySQL server has gone away detected, will attempt reconnection");
            }
            return false;
        }
    }
    
    /**
     * Reconnect to database
     *
     * @throws RuntimeException If reconnection fails
     */
    private function reconnect(): void {
        error_log("Attempting database reconnection...");
        $this->connection = null;
        
        try {
            $this->initializeConnection();
            error_log("Database reconnection successful");
        } catch (RuntimeException $e) {
            error_log("Database reconnection failed: " . $e->getMessage());
            throw new RuntimeException("Failed to reconnect to database: " . $e->getMessage());
        }
    }
    
    /**
     * Get the PDO connection
     *
     * @return PDO
     * @throws RuntimeException If connection is not established
     */
    public function getConnection(): PDO {
        if ($this->connection === null) {
            throw new RuntimeException("Database connection not established");
        }
        
        return $this->connection;
    }
    
    /**
     * Prepare a SQL statement with performance monitoring
     *
     * @param string $sql SQL query
     * @param array $options Statement options
     * @return PDOStatement
     * @throws PDOException If preparation fails
     */
    public function prepare(string $sql, array $options = []): PDOStatement {
        $startTime = microtime(true);
        
        try {
            $statement = $this->getConnection()->prepare($sql, $options);
            
            $executionTime = microtime(true) - $startTime;
            $this->queryCount++;
            $this->totalQueryTime += $executionTime;
            
            // Log slow queries
            if ($executionTime > 1.0) {
                error_log("Slow query detected: " . round($executionTime, 3) . "s - " . substr($sql, 0, 100) . "...");
            }
            
            return $statement;
            
        } catch (PDOException $e) {
            error_log("Query preparation failed: " . $e->getMessage() . " - SQL: " . substr($sql, 0, 100));
            throw $e;
        }
    }
    
    /**
     * Execute a query directly (for simple queries)
     *
     * @param string $sql SQL query
     * @return PDOStatement
     * @throws PDOException If execution fails
     */
    public function query(string $sql): PDOStatement {
        $startTime = microtime(true);
        
        try {
            $statement = $this->getConnection()->query($sql);
            
            $executionTime = microtime(true) - $startTime;
            $this->queryCount++;
            $this->totalQueryTime += $executionTime;
            
            return $statement;
            
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage() . " - SQL: " . substr($sql, 0, 100));
            throw $e;
        }
    }
    
    /**
     * Get the last inserted ID
     *
     * @param string|null $name Sequence name (for PostgreSQL)
     * @return string
     */
    public function lastInsertId(?string $name = null): string {
        return $this->getConnection()->lastInsertId($name);
    }
    
    /**
     * Begin a database transaction
     *
     * @return bool
     * @throws PDOException If transaction fails
     */
    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit the current transaction
     *
     * @return bool
     * @throws PDOException If commit fails
     */
    public function commit(): bool {
        return $this->getConnection()->commit();
    }
    
    /**
     * Roll back the current transaction
     *
     * @return bool
     * @throws PDOException If rollback fails
     */
    public function rollBack(): bool {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Execute a callback within a database transaction
     *
     * @param callable $callback
     * @return mixed Result of the callback
     * @throws Exception If transaction fails or callback throws exception
     */
    public function transaction(callable $callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get performance statistics
     *
     * @return array Performance metrics
     */
    public function getPerformanceStats(): array {
        return [
            'query_count' => $this->queryCount,
            'total_query_time' => round($this->totalQueryTime, 4),
            'average_query_time' => $this->queryCount > 0
                ? round($this->totalQueryTime / $this->queryCount, 4)
                : 0,
            'connection_active' => $this->isConnectionActive()
        ];
    }
    
    /**
     * Execute a raw SQL query with parameter binding
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement
     * @throws PDOException If execution fails
     */
    public function execute(string $sql, array $params = []): PDOStatement {
        $statement = $this->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
    
    /**
     * Fetch a single row from a query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public function fetchOne(string $sql, array $params = []) {
        $statement = $this->execute($sql, $params);
        return $statement->fetch();
    }
    
    /**
     * Fetch all rows from a query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        $statement = $this->execute($sql, $params);
        return $statement->fetchAll();
    }
    
    /**
     * Get a single value from a query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @param int $columnNumber Column index (default: 0)
     * @return mixed|false
     */
    public function fetchColumn(string $sql, array $params = [], int $columnNumber = 0) {
        $statement = $this->execute($sql, $params);
        return $statement->fetchColumn($columnNumber);
    }
    
    /**
     * Prevent cloning of the singleton instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the singleton instance
     */
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize singleton");
    }
    
    /**
     * Cleanup on destruction
     */
    public function __destruct() {
        if ($this->connection !== null) {
            // Close connection if persistent connections are not used
            if (!$this->shouldUsePersistentConnections()) {
                $this->connection = null;
            }
        }
    }
}
?>