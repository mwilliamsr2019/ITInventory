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

// Get locations for dropdown
$db = Database::getInstance();
$locationsStmt = $db->prepare("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name");
$locationsStmt->execute();
$locations = $locationsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token';
    } else {
        // Sanitize input data
        $data = [
            'make' => sanitizeInput($_POST['make'] ?? ''),
            'model' => sanitizeInput($_POST['model'] ?? ''),
            'serial_number' => sanitizeInput($_POST['serial_number'] ?? ''),
            'property_number' => sanitizeInput($_POST['property_number'] ?? ''),
            'warranty_end_date' => sanitizeInput($_POST['warranty_end_date'] ?? ''),
            'excess_date' => sanitizeInput($_POST['excess_date'] ?? ''),
            'use_case' => sanitizeInput($_POST['use_case'] ?? ''),
            'location_id' => (int)($_POST['location_id'] ?? 0),
            'on_site' => isset($_POST['on_site']),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'assigned_to' => sanitizeInput($_POST['assigned_to'] ?? ''),
            'purchase_date' => sanitizeInput($_POST['purchase_date'] ?? ''),
            'purchase_cost' => sanitizeInput($_POST['purchase_cost'] ?? ''),
            'vendor' => sanitizeInput($_POST['vendor'] ?? ''),
            'status' => sanitizeInput($_POST['status'] ?? 'active')
        ];
        
        // Add the item
        $result = $inventory->addItem($data);
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form data
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}

$useCases = $inventory->getUseCases();
$statuses = $inventory->getStatuses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - IT Inventory Management</title>
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
                    <h1 class="h2">Add Inventory Item</h1>
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
                        <?php echo sanitizeInput($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow">
                    <div class="card-body">
                        <form method="POST" id="addItemForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title mb-3">Basic Information</h5>
                                    
                                    <div class="mb-3">
                                        <label for="make" class="form-label">Make <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="make" name="make" 
                                               value="<?php echo sanitizeInput($_POST['make'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="model" name="model" 
                                               value="<?php echo sanitizeInput($_POST['model'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                               value="<?php echo sanitizeInput($_POST['serial_number'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="property_number" class="form-label">Property Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="property_number" name="property_number" 
                                               value="<?php echo sanitizeInput($_POST['property_number'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="use_case" class="form-label">Use Case <span class="text-danger">*</span></label>
                                        <select class="form-select" id="use_case" name="use_case" required>
                                            <option value="">Select Use Case</option>
                                            <?php foreach ($useCases as $useCase): ?>
                                                <option value="<?php echo $useCase; ?>" 
                                                        <?php echo (($_POST['use_case'] ?? '') === $useCase) ? 'selected' : ''; ?>>
                                                    <?php echo $useCase; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?php echo $status; ?>" 
                                                        <?php echo (($_POST['status'] ?? 'active') === $status) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="card-title mb-3">Additional Information</h5>
                                    
                                    <div class="mb-3">
                                        <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                                        <select class="form-select" id="location_id" name="location_id" required>
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo $location['id']; ?>" 
                                                        <?php echo (($_POST['location_id'] ?? '') == $location['id']) ? 'selected' : ''; ?>>
                                                    <?php echo sanitizeInput($location['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="on_site" name="on_site" 
                                                   <?php echo isset($_POST['on_site']) ? 'checked' : 'checked'; ?>>
                                            <label class="form-check-label" for="on_site">
                                                On Site
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="assigned_to" class="form-label">Assigned To</label>
                                        <input type="text" class="form-control" id="assigned_to" name="assigned_to" 
                                               value="<?php echo sanitizeInput($_POST['assigned_to'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="warranty_end_date" class="form-label">Warranty End Date</label>
                                        <input type="date" class="form-control" id="warranty_end_date" name="warranty_end_date" 
                                               value="<?php echo sanitizeInput($_POST['warranty_end_date'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="excess_date" class="form-label">Excess Date</label>
                                        <input type="date" class="form-control" id="excess_date" name="excess_date" 
                                               value="<?php echo sanitizeInput($_POST['excess_date'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="purchase_date" class="form-label">Purchase Date</label>
                                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                               value="<?php echo sanitizeInput($_POST['purchase_date'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="purchase_cost" class="form-label">Purchase Cost</label>
                                        <input type="number" step="0.01" class="form-control" id="purchase_cost" name="purchase_cost" 
                                               value="<?php echo sanitizeInput($_POST['purchase_cost'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="vendor" class="form-label">Vendor</label>
                                        <input type="text" class="form-control" id="vendor" name="vendor" 
                                               value="<?php echo sanitizeInput($_POST['vendor'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo sanitizeInput($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Add Item
                                    </button>
                                    <a href="inventory.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('addItemForm').addEventListener('submit', function(e) {
            const requiredFields = ['make', 'model', 'serial_number', 'property_number', 'use_case', 'location_id'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // Remove invalid class on input
        document.querySelectorAll('.form-control, .form-select').forEach(element => {
            element.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>