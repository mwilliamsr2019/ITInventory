<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/Inventory.php';
require_once 'classes/User.php';

// Check if user is logged in
User::requireLogin();

setSecurityHeaders();

$inventory = new Inventory();

// Get filter parameters
$filters = [
    'make' => sanitizeInput($_GET['make'] ?? ''),
    'model' => sanitizeInput($_GET['model'] ?? ''),
    'serial_number' => sanitizeInput($_GET['serial_number'] ?? ''),
    'property_number' => sanitizeInput($_GET['property_number'] ?? ''),
    'use_case' => sanitizeInput($_GET['use_case'] ?? ''),
    'location_id' => sanitizeInput($_GET['location_id'] ?? ''),
    'status' => sanitizeInput($_GET['status'] ?? ''),
    'assigned_to' => sanitizeInput($_GET['assigned_to'] ?? '')
];

// Ensure filter values are strings for form inputs
foreach ($filters as $key => $value) {
    if (is_array($value)) {
        $filters[$key] = '';
    }
}

// Check if this is a download request
if (isset($_GET['download'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_GET['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request token';
        redirect('export.php');
    }
    
    // Get data for export
    $exportData = $inventory->getItemsForCsv($filters);
    
    if (empty($exportData)) {
        $_SESSION['error'] = 'No data to export';
        redirect('export.php');
    }
    
    // Create CSV content
    $csvContent = arrayToCsv($exportData);
    
    // Generate filename with timestamp
    $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Set headers for file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV content
    echo $csvContent;
    exit();
}

setNoCacheHeaders();

// Get locations for filter dropdown
$db = Database::getInstance();
$locationsStmt = $db->prepare("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name");
$locationsStmt->execute();
$locations = $locationsStmt->fetchAll();

$useCases = $inventory->getUseCases();
$statuses = $inventory->getStatuses();

// Get preview data (limited to 10 rows)
$previewFilters = array_merge($filters, ['limit' => 10]);
$previewData = $inventory->searchItems($filters, 1, 10);
$previewItems = $previewData['items'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export CSV - IT Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Export CSV</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="inventory.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>
                
                <!-- Alert messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitizeInput($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo sanitizeInput($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Export Options</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" id="exportForm">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="make" class="form-label">Make</label>
                                            <input type="text" class="form-control" id="make" name="make"
                                                   value="<?php echo is_array($filters['make']) ? '' : $filters['make']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="model" class="form-label">Model</label>
                                            <input type="text" class="form-control" id="model" name="model"
                                                   value="<?php echo is_array($filters['model']) ? '' : $filters['model']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="serial_number" class="form-label">Serial Number</label>
                                            <input type="text" class="form-control" id="serial_number" name="serial_number"
                                                   value="<?php echo is_array($filters['serial_number']) ? '' : $filters['serial_number']; ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <label for="property_number" class="form-label">Property Number</label>
                                            <input type="text" class="form-control" id="property_number" name="property_number"
                                                   value="<?php echo is_array($filters['property_number']) ? '' : $filters['property_number']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="use_case" class="form-label">Use Case</label>
                                            <select class="form-select" id="use_case" name="use_case">
                                                <option value="">All Use Cases</option>
                                                <?php foreach ($useCases as $useCase): ?>
                                                    <option value="<?php echo $useCase; ?>" 
                                                            <?php echo $filters['use_case'] === $useCase ? 'selected' : ''; ?>>
                                                        <?php echo $useCase; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="location_id" class="form-label">Location</label>
                                            <select class="form-select" id="location_id" name="location_id">
                                                <option value="">All Locations</option>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?php echo $location['id']; ?>" 
                                                            <?php echo $filters['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                                        <?php echo sanitizeInput($location['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="">All Statuses</option>
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?php echo $status; ?>" 
                                                            <?php echo $filters['status'] === $status ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="assigned_to" class="form-label">Assigned To</label>
                                            <input type="text" class="form-control" id="assigned_to" name="assigned_to"
                                                   value="<?php echo is_array($filters['assigned_to']) ? '' : $filters['assigned_to']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-search"></i> Preview
                                                </button>
                                                <button type="button" class="btn btn-success" onclick="downloadCsv()">
                                                    <i class="bi bi-download"></i> Download CSV
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Export Information</h5>
                            </div>
                            <div class="card-body">
                                <h6>CSV Format:</h6>
                                <ul class="small">
                                    <li>Make</li>
                                    <li>Model</li>
                                    <li>Serial Number</li>
                                    <li>Property Number</li>
                                    <li>Warranty End Date</li>
                                    <li>Excess Date</li>
                                    <li>Use Case</li>
                                    <li>Location</li>
                                    <li>On Site</li>
                                    <li>Description</li>
                                    <li>Assigned To</li>
                                    <li>Purchase Date</li>
                                    <li>Purchase Cost</li>
                                    <li>Vendor</li>
                                    <li>Status</li>
                                    <li>Created Date</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        The export will include all items matching your filter criteria.
                                        Leave all filters empty to export all items.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Preview (First 10 rows)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($previewItems)): ?>
                            <p class="text-muted">No items match the current filter criteria.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Make</th>
                                            <th>Model</th>
                                            <th>Serial Number</th>
                                            <th>Property Number</th>
                                            <th>Use Case</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($previewItems as $item): ?>
                                            <tr>
                                                <td><?php echo sanitizeInput($item['make']); ?></td>
                                                <td><?php echo sanitizeInput($item['model']); ?></td>
                                                <td><?php echo sanitizeInput($item['serial_number']); ?></td>
                                                <td><?php echo sanitizeInput($item['property_number']); ?></td>
                                                <td><?php echo sanitizeInput($item['use_case']); ?></td>
                                                <td><?php echo sanitizeInput($item['location_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 
                                                                           ($item['status'] === 'repair' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($item['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (count($previewItems) == 10): ?>
                                <div class="alert alert-info mt-3">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        Showing first 10 rows. The actual export will contain all matching items.
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Export History -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Export History</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $auditStmt = $db->prepare("
                            SELECT a.*, u.username 
                            FROM audit_log a
                            JOIN users u ON a.user_id = u.id
                            WHERE a.table_name = 'inventory_items' AND a.action = 'export'
                            ORDER BY a.timestamp DESC
                            LIMIT 10
                        ");
                        $auditStmt->execute();
                        $exports = $auditStmt->fetchAll();
                        ?>
                        
                        <?php if (empty($exports)): ?>
                            <p class="text-muted">No export history available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>User</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exports as $export): ?>
                                            <tr>
                                                <td><?php echo formatDateTime($export['timestamp']); ?></td>
                                                <td><?php echo sanitizeInput($export['username']); ?></td>
                                                <td><?php echo sanitizeInput($export['ip_address']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadCsv() {
            // Validate that there are items to export
            <?php if (empty($previewItems)): ?>
                alert('No items match the current filter criteria. Nothing to export.');
                return;
            <?php endif; ?>
            
            // Add CSRF token and download parameter
            const form = document.getElementById('exportForm');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo generateCSRFToken(); ?>';
            
            const downloadInput = document.createElement('input');
            downloadInput.type = 'hidden';
            downloadInput.name = 'download';
            downloadInput.value = '1';
            
            form.appendChild(csrfInput);
            form.appendChild(downloadInput);
            
            // Submit form
            form.submit();
            
            // Remove the added inputs
            form.removeChild(csrfInput);
            form.removeChild(downloadInput);
        }
    </script>
</body>
</html>