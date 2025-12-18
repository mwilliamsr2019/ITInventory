<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/Inventory.php';
require_once 'classes/User.php';

// Check if user is logged in
User::requireLogin();

setSecurityHeaders();
setNoCacheHeaders();

$inventory = new Inventory();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token';
    } elseif (!isset($_FILES['csv_file'])) {
        $error = 'No file uploaded';
    } else {
        $result = $inventory->importFromCsv($_FILES['csv_file']);
        
        if ($result['success']) {
            $success = $result['message'];
            if (!empty($result['errors'])) {
                $error = 'Import completed with errors:<br>' . implode('<br>', $result['errors']);
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Get sample CSV data for download
$sampleData = [
    [
        'Make' => 'Dell',
        'Model' => 'OptiPlex 7090',
        'Serial Number' => 'ABC123456789',
        'Property Number' => 'PROP-001234',
        'Warranty End Date' => '2025-12-31',
        'Excess Date' => '',
        'Use Case' => 'Desktop',
        'Location' => 'Main Office',
        'On Site' => 'Yes',
        'Description' => 'Standard desktop computer',
        'Assigned To' => 'John Doe',
        'Purchase Date' => '2023-01-15',
        'Purchase Cost' => '899.99',
        'Vendor' => 'Dell Technologies',
        'Status' => 'active'
    ]
];

$sampleCsv = arrayToCsv($sampleData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV - IT Inventory Management</title>
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
                    <h1 class="h2">Import CSV</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="inventory.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>
                
                <!-- Alert messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitizeInput($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Upload CSV File</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="importForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Select CSV File</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                               accept=".csv,.txt" required>
                                        <div class="form-text">
                                            Maximum file size: <?php echo formatBytes(UPLOAD_MAX_SIZE); ?>. 
                                            Maximum rows: <?php echo number_format(CSV_MAX_ROWS); ?>.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                                            <label class="form-check-label" for="skip_duplicates">
                                                Skip duplicate entries (based on Serial Number and Property Number)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> Import CSV
                                    </button>
                                    <a href="inventory.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Instructions</h5>
                            </div>
                            <div class="card-body">
                                <h6>CSV Format Requirements:</h6>
                                <ul class="small">
                                    <li>First row must contain column headers</li>
                                    <li>Required fields: Make, Model, Serial Number, Property Number, Use Case, Location</li>
                                    <li>Use Case must be one of: <?php echo implode(', ', $useCases); ?></li>
                                    <li>Status must be one of: <?php echo implode(', ', $statuses); ?></li>
                                    <li>On Site should be "Yes" or "No"</li>
                                    <li>Dates should be in YYYY-MM-DD format</li>
                                </ul>
                                
                                <h6 class="mt-3">Sample CSV:</h6>
                                <textarea class="form-control" rows="8" readonly><?php echo $sampleCsv; ?></textarea>
                                
                                <a href="data:text/csv;charset=utf-8,<?php echo urlencode($sampleCsv); ?>" 
                                   download="sample_inventory.csv" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-download"></i> Download Sample
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Import History -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Import History</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $auditStmt = $db->prepare("
                            SELECT a.*, u.username 
                            FROM audit_log a
                            JOIN users u ON a.user_id = u.id
                            WHERE a.table_name = 'inventory_items' AND a.action = 'insert'
                            ORDER BY a.timestamp DESC
                            LIMIT 10
                        ");
                        $auditStmt->execute();
                        $imports = $auditStmt->fetchAll();
                        ?>
                        
                        <?php if (empty($imports)): ?>
                            <p class="text-muted">No import history available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>User</th>
                                            <th>Items Imported</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($imports as $import): ?>
                                            <?php
                                            $newValues = json_decode($import['new_values'], true);
                                            $itemCount = is_array($newValues) ? count($newValues) : 1;
                                            ?>
                                            <tr>
                                                <td><?php echo formatDateTime($import['timestamp']); ?></td>
                                                <td><?php echo sanitizeInput($import['username']); ?></td>
                                                <td><?php echo $itemCount; ?></td>
                                                <td><?php echo sanitizeInput($import['ip_address']); ?></td>
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
        // File validation
        document.getElementById('csv_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = <?php echo UPLOAD_MAX_SIZE; ?>;
                if (file.size > maxSize) {
                    alert('File size exceeds maximum allowed size of <?php echo formatBytes(UPLOAD_MAX_SIZE); ?>');
                    e.target.value = '';
                    return;
                }
                
                // Check file extension
                const allowedExtensions = ['csv', 'txt'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('Please select a CSV file (.csv or .txt)');
                    e.target.value = '';
                    return;
                }
            }
        });
        
        // Form submission
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a CSV file to import.');
                return;
            }
            
            // Show loading indicator
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Importing...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>