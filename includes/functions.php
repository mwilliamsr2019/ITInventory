<?php
/**
 * Utility Functions Library
 *
 * Provides comprehensive utility functions for security, validation, file handling,
 * date manipulation, and error handling with modern PHP practices.
 *
 * @package ITInventory
 * @author IT Inventory Team
 * @version 2.0.0
 */

declare(strict_types=1);

/**
 * 🔐 Security Functions
 */

/**
 * Sanitize user input with context-aware filtering
 *
 * @param mixed $input Input to sanitize
 * @param string $context Context for sanitization (html, sql, url, email)
 * @return mixed Sanitized input
 */
function sanitizeInput($input, string $context = 'html') {
    if (is_array($input)) {
        return array_map(function($item) use ($context) {
            return sanitizeInput($item, $context);
        }, $input);
    }
    
    if (!is_string($input)) {
        return $input;
    }
    
    $input = trim($input);
    
    switch ($context) {
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
        case 'sql':
            // For SQL contexts, use prepared statements instead
            return $input;
            
        case 'url':
            return urlencode($input);
            
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
            
        case 'alphanumeric':
            return preg_replace('/[^a-zA-Z0-9]/', '', $input);
            
        case 'filename':
            return sanitizeFilename($input);
            
        default:
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Validate email address with DNS check
 *
 * @param string $email Email to validate
 * @param bool $checkDns Check if domain has MX records
 * @return bool
 */
function validateEmail(string $email, bool $checkDns = false): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    if ($checkDns) {
        $domain = substr(strrchr($email, "@"), 1);
        return checkdnsrr($domain, "MX");
    }
    
    return true;
}

/**
 * Validate password strength with customizable rules
 *
 * @param string $password Password to validate
 * @param array $rules Validation rules
 * @return array ['valid' => bool, 'errors' => string[]]
 */
function validatePassword(string $password, array $rules = []): array {
    $defaultRules = [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'max_length' => 128
    ];
    
    $rules = array_merge($defaultRules, $rules);
    $errors = [];
    
    // Length validation
    if (strlen($password) < $rules['min_length']) {
        $errors[] = "Password must be at least {$rules['min_length']} characters long";
    }
    
    if (strlen($password) > $rules['max_length']) {
        $errors[] = "Password must not exceed {$rules['max_length']} characters";
    }
    
    // Character requirements
    if ($rules['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if ($rules['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if ($rules['require_number'] && !preg_match('/\d/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($rules['require_special'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Generate cryptographically secure CSRF token
 *
 * @return string CSRF token
 * @throws Exception If random bytes generation fails
 */
function generateCSRFToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        throw new RuntimeException("Session must be started before generating CSRF token");
    }
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        try {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            throw new RuntimeException("Failed to generate secure CSRF token: " . $e->getMessage());
        }
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token with timing attack protection
 *
 * @param string $token Token to validate
 * @return bool
 */
function validateCSRFToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Secure redirect with validation
 *
 * @param string $url URL to redirect to
 * @param int $status HTTP status code
 * @param bool $exit Whether to exit after redirect
 */
function redirect(string $url, int $status = 302, bool $exit = true): void {
    // Validate URL to prevent open redirect vulnerabilities
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        // Fallback to relative path validation
        if (!preg_match('/^\/[a-zA-Z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]*$/', $url)) {
            throw new InvalidArgumentException("Invalid redirect URL");
        }
    }
    
    // Prevent header injection
    $url = str_replace(["\r", "\n"], '', $url);
    
    header("Location: $url", true, $status);
    
    if ($exit) {
        exit();
    }
}

/**
 * Check if request is AJAX with multiple methods
 *
 * @return bool
 */
function isAjaxRequest(): bool {
    // Check X-Requested-With header
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    
    // Check Accept header for JSON
    if (isset($_SERVER['HTTP_ACCEPT']) &&
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    
    // Check for API key in headers
    if (isset($_SERVER['HTTP_AUTHORIZATION']) ||
        isset($_SERVER['HTTP_X_API_KEY'])) {
        return true;
    }
    
    return false;
}

/**
 * 📊 Database Helper Functions
 */

/**
 * Log audit trail with enhanced metadata
 *
 * @param string $tableName Database table name
 * @param int|string $recordId Record identifier
 * @param string $action Action performed (insert, update, delete)
 * @param array|null $oldValues Previous values
 * @param array|null $newValues New values
 * @param int|null $userId User ID (auto-detected if null)
 * @return bool Success status
 */
function logAudit(string $tableName, $recordId, string $action, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): bool {
    try {
        $db = Database::getInstance();
        
        // Auto-detect user ID if not provided
        if ($userId === null) {
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        }
        
        // Get client information
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Sanitize and encode values
        $oldValuesJson = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $newValuesJson = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        
        // Validate action
        $validActions = ['insert', 'update', 'delete', 'login', 'logout', 'import', 'export'];
        if (!in_array(strtolower($action), $validActions)) {
            throw new InvalidArgumentException("Invalid audit action: $action");
        }
        
        $stmt = $db->prepare("
            INSERT INTO audit_log (
                table_name, record_id, action, old_values, new_values,
                user_id, ip_address, user_agent, request_method, request_uri
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $tableName,
            $recordId,
            strtolower($action),
            $oldValuesJson,
            $newValuesJson,
            $userId,
            $ipAddress,
            $userAgent,
            $requestMethod,
            $requestUri
        ]);
        
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate cryptographically secure unique identifier
 *
 * @param string $prefix Optional prefix
 * @param int $entropyBytes Number of random bytes
 * @return string Unique identifier
 * @throws Exception If random generation fails
 */
function generateUniqueId(string $prefix = '', int $entropyBytes = 16): string {
    try {
        $randomBytes = random_bytes($entropyBytes);
        $timestamp = microtime(true);
        $hostname = gethostname() ?: 'unknown';
        
        return $prefix .
               base64_encode($randomBytes) .
               dechex((int)($timestamp * 1000000)) .
               substr(md5($hostname), 0, 8);
    } catch (Exception $e) {
        throw new RuntimeException("Failed to generate unique ID: " . $e->getMessage());
    }
}

/**
 * 📁 File Handling Functions
 */

/**
 * Enhanced file upload validation with comprehensive checks
 *
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int|null $maxSize Maximum file size in bytes
 * @param array $options Additional validation options
 * @return array Validation result
 */
function validateFileUpload(array $file, array $allowedTypes = [], ?int $maxSize = null, array $options = []): array {
    $defaultOptions = [
        'check_mime_type' => true,
        'check_extension' => true,
        'virus_scan' => false,
        'max_filename_length' => 255
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // Basic validation
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload structure'];
    }
    
    // Check upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file was uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File size exceeds server limit'];
        case UPLOAD_ERR_PARTIAL:
            return ['success' => false, 'message' => 'File was only partially uploaded'];
        case UPLOAD_ERR_NO_TMP_DIR:
            return ['success' => false, 'message' => 'Missing temporary folder'];
        case UPLOAD_ERR_CANT_WRITE:
            return ['success' => false, 'message' => 'Failed to write file to disk'];
        case UPLOAD_ERR_EXTENSION:
            return ['success' => false, 'message' => 'File upload stopped by extension'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }
    
    // Verify file was actually uploaded via HTTP POST
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File was not uploaded via HTTP POST'];
    }
    
    // Size validation
    if ($maxSize !== null && $file['size'] > $maxSize) {
        return [
            'success' => false,
            'message' => sprintf('File size (%s) exceeds maximum allowed size (%s)',
                formatBytes($file['size']),
                formatBytes($maxSize))
        ];
    }
    
    // MIME type validation
    if ($options['check_mime_type'] && !empty($allowedTypes)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => "File type '$mimeType' is not allowed. Allowed types: " . implode(', ', $allowedTypes)
            ];
        }
    }
    
    // Filename validation
    if (strlen($file['name']) > $options['max_filename_length']) {
        return ['success' => false, 'message' => 'Filename too long'];
    }
    
    // Check for PHP file upload attacks
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'exe', 'bat', 'cmd', 'com', 'scr'];
    
    if (in_array($extension, $dangerousExtensions)) {
        return ['success' => false, 'message' => 'Potentially dangerous file type'];
    }
    
    // Virus scanning placeholder (implement based on available antivirus software)
    if ($options['virus_scan']) {
        // This is a placeholder - implement based on your antivirus solution
        // Example: clamscan, sophos, mcafee, etc.
        error_log("Virus scanning requested but not implemented for file: " . ($file['name'] ?? 'unknown'));
    }
    
    return ['success' => true, 'message' => 'File validation successful'];
}

/**
 * Format bytes to human readable format
 *
 * @param int $size Size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted size
 */
function formatBytes(int $size, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $base = log($size, 1024);
    
    if ($size <= 0) {
        return '0 B';
    }
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

/**
 * 📅 Date and Time Functions
 */

/**
 * Format date with timezone support
 *
 * @param string|int $date Date string or timestamp
 * @param string $format Date format
 * @param string|null $timezone Timezone (default: UTC)
 * @return string Formatted date
 */
function formatDate($date, string $format = 'Y-m-d', ?string $timezone = null): string {
    if (empty($date)) {
        return '';
    }
    
    try {
        $timezone = $timezone ?? 'UTC';
        $dateTime = new DateTime($date, new DateTimeZone($timezone));
        return $dateTime->format($format);
    } catch (Exception $e) {
        error_log("Date formatting error: " . $e->getMessage());
        return '';
    }
}

/**
 * Format datetime with timezone support
 *
 * @param string|int $datetime DateTime string or timestamp
 * @param string $format DateTime format
 * @param string|null $timezone Timezone (default: UTC)
 * @return string Formatted datetime
 */
function formatDateTime($datetime, string $format = 'Y-m-d H:i:s', ?string $timezone = null): string {
    return formatDate($datetime, $format, $timezone);
}

/**
 * Validate date with timezone support
 *
 * @param string $date Date string to validate
 * @param string $format Expected format
 * @param string|null $timezone Timezone (default: UTC)
 * @return bool
 */
function isValidDate(string $date, string $format = 'Y-m-d', ?string $timezone = null): bool {
    try {
        $timezone = $timezone ?? 'UTC';
        $dateTime = DateTime::createFromFormat($format, $date, new DateTimeZone($timezone));
        
        if ($dateTime === false) {
            return false;
        }
        
        // Verify the parsed date matches the input exactly
        return $dateTime->format($format) === $date;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calculate time difference in human readable format
 *
 * @param string $fromDate Start date
 * @param string|null $toDate End date (default: now)
 * @return string Time difference
 */
function timeAgo(string $fromDate, ?string $toDate = null): string {
    try {
        $from = new DateTime($fromDate);
        $to = $toDate ? new DateTime($toDate) : new DateTime();
        
        $diff = $from->diff($to);
        
        $units = [
            'year' => $diff->y,
            'month' => $diff->m,
            'day' => $diff->d,
            'hour' => $diff->h,
            'minute' => $diff->i,
            'second' => $diff->s
        ];
        
        foreach ($units as $unit => $value) {
            if ($value > 0) {
                $plural = $value > 1 ? 's' : '';
                return $diff->invert ? "in $value $unit$plural" : "$value $unit$plural ago";
            }
        }
        
        return 'just now';
    } catch (Exception $e) {
        return 'unknown';
    }
}

/**
 * 📊 CSV Functions
 */

/**
 * Convert array to CSV with proper escaping and encoding
 *
 * @param array $data Data to convert
 * @param string $delimiter Field delimiter
 * @param string $enclosure Field enclosure
 * @param string $encoding Output encoding
 * @return string CSV content
 */
function arrayToCsv(array $data, string $delimiter = ',', string $enclosure = '"', string $encoding = 'UTF-8'): string {
    if (empty($data)) {
        return '';
    }
    
    $output = fopen('php://temp', 'r+');
    
    if ($output === false) {
        throw new RuntimeException("Failed to create temporary stream for CSV generation");
    }
    
    try {
        // Set locale for proper CSV formatting
        $originalLocale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.UTF-8');
        
        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers, $delimiter, $enclosure);
        
        // Write data
        foreach ($data as $row) {
            // Ensure all values are properly encoded
            $encodedRow = array_map(function($value) use ($encoding) {
                if (is_string($value)) {
                    return mb_convert_encoding($value, $encoding, 'UTF-8');
                }
                return $value;
            }, $row);
            
            fputcsv($output, $encodedRow, $delimiter, $enclosure);
        }
        
        // Restore original locale
        setlocale(LC_ALL, $originalLocale);
        
        rewind($output);
        $csv = stream_get_contents($output);
        
        if ($csv === false) {
            throw new RuntimeException("Failed to read CSV content from stream");
        }
        
        return $csv;
        
    } finally {
        fclose($output);
    }
}

/**
 * Parse CSV file with robust error handling
 *
 * @param string $filename CSV file path
 * @param string $delimiter Field delimiter
 * @param string $enclosure Field enclosure
 * @param string $encoding File encoding
 * @return array Parsed data
 * @throws RuntimeException If file cannot be read or parsed
 */
function csvToArray(string $filename, string $delimiter = ',', string $enclosure = '"', string $encoding = 'UTF-8'): array {
    if (!file_exists($filename)) {
        throw new RuntimeException("CSV file not found: $filename");
    }
    
    if (!is_readable($filename)) {
        throw new RuntimeException("CSV file is not readable: $filename");
    }
    
    $handle = fopen($filename, 'r');
    if ($handle === false) {
        throw new RuntimeException("Failed to open CSV file: $filename");
    }
    
    try {
        $header = null;
        $data = [];
        $rowNumber = 0;
        
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            if ($header === null) {
                $header = array_map(function($value) {
                    return trim($value);
                }, $row);
                
                // Validate header uniqueness
                if (count($header) !== count(array_unique($header))) {
                    throw new RuntimeException("Duplicate column headers found in CSV file");
                }
                
                continue;
            }
            
            // Validate row has correct number of columns
            if (count($row) !== count($header)) {
                error_log("CSV row $rowNumber has mismatched column count: expected " . count($header) . ", got " . count($row));
                continue;
            }
            
            $rowData = array_combine($header, $row);
            
            if ($rowData === false) {
                throw new RuntimeException("Failed to combine header with row data at row $rowNumber");
            }
            
            $data[] = $rowData;
        }
        
        return $data;
        
    } finally {
        fclose($handle);
    }
}

/**
 * ✅ Validation Functions
 */

/**
 * Validate serial number with custom rules
 *
 * @param string $serialNumber Serial number to validate
 * @param int $maxLength Maximum length
 * @param string $pattern Optional regex pattern
 * @return array Validation result
 */
function validateSerialNumber(string $serialNumber, int $maxLength = 100, ?string $pattern = null): array {
    $errors = [];
    
    if (empty($serialNumber)) {
        $errors[] = "Serial number is required";
    } elseif (strlen($serialNumber) > $maxLength) {
        $errors[] = "Serial number must not exceed $maxLength characters";
    }
    
    if ($pattern !== null && !preg_match($pattern, $serialNumber)) {
        $errors[] = "Serial number does not match required format";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate property number with custom rules
 *
 * @param string $propertyNumber Property number to validate
 * @param int $maxLength Maximum length
 * @param string $pattern Optional regex pattern
 * @return array Validation result
 */
function validatePropertyNumber(string $propertyNumber, int $maxLength = 100, ?string $pattern = null): array {
    $errors = [];
    
    if (empty($propertyNumber)) {
        $errors[] = "Property number is required";
    } elseif (strlen($propertyNumber) > $maxLength) {
        $errors[] = "Property number must not exceed $maxLength characters";
    }
    
    if ($pattern !== null && !preg_match($pattern, $propertyNumber)) {
        $errors[] = "Property number does not match required format";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate use case against allowed values
 *
 * @param string $useCase Use case to validate
 * @return array Validation result
 */
function validateUseCase(string $useCase): array {
    $validUseCases = ['Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development'];
    
    if (!in_array($useCase, $validUseCases)) {
        return [
            'valid' => false,
            'errors' => ["Invalid use case. Allowed: " . implode(', ', $validUseCases)]
        ];
    }
    
    return ['valid' => true, 'errors' => []];
}

/**
 * Sanitize filename for safe filesystem storage
 *
 * @param string $filename Original filename
 * @param int $maxLength Maximum filename length
 * @return string Sanitized filename
 */
function sanitizeFilename(string $filename, int $maxLength = 255): string {
    // Remove path information
    $filename = basename($filename);
    
    // Remove control characters and invalid filesystem characters
    $filename = preg_replace('/[\x00-\x1F\x7F\/\\:*?"<>|]/', '_', $filename);
    
    // Replace spaces and special characters with underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    // Remove leading/trailing underscores and dots
    $filename = trim($filename, '._');
    
    // Ensure filename is not empty
    if (empty($filename)) {
        $filename = 'unnamed_file';
    }
    
    // Truncate if too long (preserve extension)
    if (strlen($filename) > $maxLength) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $maxNameLength = $maxLength - strlen($extension) - 1;
        
        if ($maxNameLength > 0) {
            $filename = substr($nameWithoutExt, 0, $maxNameLength) . '.' . $extension;
        } else {
            $filename = substr($filename, 0, $maxLength);
        }
    }
    
    return $filename;
}

/**
 * 🔒 Security Headers
 */

/**
 * Set comprehensive security headers
 *
 * @param array $options Header options
 */
function setSecurityHeaders(array $options = []): void {
    $defaultOptions = [
        'x_content_type_options' => 'nosniff',
        'x_frame_options' => 'DENY',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains; preload',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; font-src 'self' cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self';",
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
        'x_content_type_options' => 'nosniff',
        'x_download_options' => 'noopen',
        'x_permitted_cross_domain_policies' => 'none'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // Set security headers
    foreach ($options as $header => $value) {
        if ($value !== null && $value !== '') {
            $headerName = str_replace('_', '-', ucwords($header, '_'));
            header("$headerName: $value");
        }
    }
    
    // Remove potentially dangerous headers
    header_remove('X-Powered-By');
    header_remove('Server');
}

/**
 * ⚠️ Error Handling Functions
 */

/**
 * Handle errors with comprehensive logging and user feedback
 *
 * @param string $message User-friendly error message
 * @param string|null $logMessage Detailed log message
 * @param int $logLevel Error level (LOG_ERR, LOG_WARNING, etc.)
 * @param array $context Additional context data
 */
function handleError(string $message, ?string $logMessage = null, int $logLevel = LOG_ERR, array $context = []): void {
    $detailedMessage = $logMessage ?: $message;
    $contextData = json_encode($context);
    
    // Log the error with context
    error_log("[$logLevel] $detailedMessage | Context: $contextData");
    
    // Store in session for display
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['error'] = [
            'message' => $message,
            'timestamp' => time(),
            'type' => 'error'
        ];
    }
    
    // Handle AJAX requests
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(400);
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error' => [
                'type' => 'application_error',
                'timestamp' => date('c')
            ]
        ]);
        exit();
    }
}

/**
 * Handle success responses with structured data
 *
 * @param string $message Success message
 * @param mixed $data Additional data to return
 * @param array $metadata Response metadata
 */
function handleSuccess(string $message, $data = null, array $metadata = []): void {
    $response = [
        'success' => true,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if (!empty($metadata)) {
        $response['metadata'] = $metadata;
    }
    
    // Handle AJAX requests
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Handle regular requests
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['success'] = [
            'message' => $message,
            'timestamp' => time(),
            'type' => 'success'
        ];
    }
}

/**
 * 📄 Pagination Functions
 */

/**
 * Calculate pagination parameters with validation
 *
 * @param int $totalItems Total number of items
 * @param int $itemsPerPage Items per page
 * @param int $currentPage Current page number
 * @param int $maxPageLinks Maximum page links to show
 * @return array Pagination data
 */
function getPagination(int $totalItems, int $itemsPerPage, int $currentPage, int $maxPageLinks = 10): array {
    // Validate inputs
    $totalItems = max(0, $totalItems);
    $itemsPerPage = max(1, min($itemsPerPage, 100)); // Limit to 100 items per page
    $currentPage = max(1, $currentPage);
    
    $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $itemsPerPage) : 1;
    $currentPage = min($currentPage, $totalPages);
    
    $offset = ($currentPage - 1) * $itemsPerPage;
    $limit = $itemsPerPage;
    
    // Calculate page range for links
    $startPage = max(1, $currentPage - (int)floor($maxPageLinks / 2));
    $endPage = min($totalPages, $startPage + $maxPageLinks - 1);
    
    // Adjust start page if we're at the end
    if ($endPage - $startPage + 1 < $maxPageLinks) {
        $startPage = max(1, $endPage - $maxPageLinks + 1);
    }
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'limit' => $limit,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
        'first_page' => 1,
        'last_page' => $totalPages,
        'page_range' => [
            'start' => $startPage,
            'end' => $endPage
        ]
    ];
}

/**
 * 🔄 Cache Control Functions
 */

/**
 * Set comprehensive cache control headers
 *
 * @param int $maxAge Maximum age in seconds
 * @param bool $public Whether cache is public
 * @param bool $mustRevalidate Whether to force revalidation
 */
function setCacheHeaders(int $maxAge = 3600, bool $public = false, bool $mustRevalidate = true): void {
    $cacheControl = [];
    
    if ($public) {
        $cacheControl[] = 'public';
    } else {
        $cacheControl[] = 'private';
    }
    
    $cacheControl[] = "max-age=$maxAge";
    
    if ($mustRevalidate) {
        $cacheControl[] = 'must-revalidate';
    }
    
    header('Cache-Control: ' . implode(', ', $cacheControl));
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    
    if ($maxAge > 0) {
        header('Pragma: cache');
    } else {
        header('Pragma: no-cache');
    }
}

/**
 * Set no-cache headers for sensitive content
 */
function setNoCacheHeaders(): void {
    header('Cache-Control: no-cache, no-store, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

/**
 * 🛠️ Utility Functions
 */

/**
 * Generate a secure random token
 *
 * @param int $length Token length
 * @param string $characters Allowed characters
 * @return string Random token
 * @throws Exception If random generation fails
 */
function generateSecureToken(int $length = 32, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
    if ($length < 1) {
        throw new InvalidArgumentException("Token length must be positive");
    }
    
    try {
        $token = '';
        $maxIndex = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, $maxIndex)];
        }
        
        return $token;
    } catch (Exception $e) {
        throw new RuntimeException("Failed to generate secure token: " . $e->getMessage());
    }
}

/**
 * Clean and validate integer input
 *
 * @param mixed $input Input to clean
 * @param int|null $min Minimum value
 * @param int|null $max Maximum value
 * @param int $default Default value if invalid
 * @return int Cleaned integer
 */
function cleanInt($input, ?int $min = null, ?int $max = null, int $default = 0): int {
    if (!is_numeric($input)) {
        return $default;
    }
    
    $value = (int)$input;
    
    if ($min !== null && $value < $min) {
        return $min;
    }
    
    if ($max !== null && $value > $max) {
        return $max;
    }
    
    return $value;
}

/**
 * Get client IP address with proxy support
 *
 * @param bool $trustProxyHeaders Whether to trust proxy headers
 * @return string Client IP address
 */
function getClientIp(bool $trustProxyHeaders = false): string {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    if ($trustProxyHeaders) {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    }
    
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 🧪 Debug and Development Functions
 */

/**
 * Debug function with formatted output
 *
 * @param mixed $data Data to debug
 * @param string $label Label for the debug output
 * @param bool $die Whether to die after output
 */
function debug($data, string $label = '', bool $die = false): void {
    if (!(defined('DEBUG_MODE') && constant('DEBUG_MODE') === true)) {
        return;
    }
    
    $output = [];
    
    if ($label) {
        $output[] = "=== $label ===";
    }
    
    $output[] = print_r($data, true);
    $output[] = "File: " . debug_backtrace()[0]['file'] . ":" . debug_backtrace()[0]['line'];
    $output[] = str_repeat('-', 50);
    
    $output = implode("\n", $output);
    
    if (PHP_SAPI === 'cli') {
        echo $output . "\n";
    } else {
        echo '<pre style="background: #f4f4f4; padding: 10px; margin: 10px; border: 1px solid #ddd; font-size: 12px;">' .
             htmlspecialchars($output) . '</pre>';
    }
    
    if ($die) {
        die();
    }
}

/**
 * Log debug information
 *
 * @param mixed $data Data to log
 * @param string $level Log level
 */
function debugLog($data, string $level = 'INFO'): void {
    if (!(defined('DEBUG_MODE') && constant('DEBUG_MODE') === true)) {
        return;
    }
    
    $message = is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT);
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $file = $backtrace[0]['file'] ?? 'unknown';
    $line = $backtrace[0]['line'] ?? 0;
    
    error_log("[$level] $message | File: $file:$line");
}
?>