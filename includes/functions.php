<?php
// Security functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Database helper functions
function logAudit($tableName, $recordId, $action, $oldValues = null, $newValues = null) {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, user_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;
    
    $stmt->execute([$tableName, $recordId, $action, $oldValuesJson, $newValuesJson, $userId, $ipAddress, $userAgent]);
}

function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(8));
}

// File handling functions
function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File size exceeds limit'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }
    
    if ($maxSize && $file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    if (!empty($allowedTypes)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
    }
    
    return ['success' => true, 'message' => 'File validation successful'];
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

// Date and time functions
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// CSV functions
function arrayToCsv($data, $delimiter = ',', $enclosure = '"') {
    $output = fopen('php://temp', 'r+');
    
    // Write headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]), $delimiter, $enclosure);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row, $delimiter, $enclosure);
        }
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
}

function csvToArray($filename, $delimiter = ',', $enclosure = '"') {
    if (!file_exists($filename) || !is_readable($filename)) {
        return false;
    }
    
    $header = null;
    $data = [];
    
    if (($handle = fopen($filename, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== false) {
            if (!$header) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }
    
    return $data;
}

// Validation functions
function validateSerialNumber($serialNumber) {
    return !empty($serialNumber) && strlen($serialNumber) <= 100;
}

function validatePropertyNumber($propertyNumber) {
    return !empty($propertyNumber) && strlen($propertyNumber) <= 100;
}

function validateUseCase($useCase) {
    $validUseCases = ['Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development'];
    return in_array($useCase, $validUseCases);
}

function sanitizeFilename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Replace spaces and special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    return $filename;
}

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');
}

// Error handling
function handleError($message, $logMessage = null) {
    error_log($logMessage ?: $message);
    
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['error'] = $message;
    }
}

function handleSuccess($message, $data = null) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        $response = ['success' => true, 'message' => $message];
        if ($data) {
            $response['data'] = $data;
        }
        echo json_encode($response);
    } else {
        $_SESSION['success'] = $message;
    }
}

// Pagination
function getPagination($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'limit' => $itemsPerPage
    ];
}

// Cache control
function setNoCacheHeaders() {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
?>