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

// Get current page
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20);

// Get locations for filter dropdown
$db = Database::getInstance();
$locationsStmt = $db->prepare("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name");
$locationsStmt->execute();
$locations = $locationsStmt->fetchAll();

// Search items
$searchResults = $inventory->searchItems($filters, $page, $perPage);
$items = $searchResults['items'];
$total = $searchResults['total'];
$totalPages = $searchResults['total_pages'];

$useCases = $inventory->getUseCases();
$statuses = $inventory->getStatuses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - IT Inventory Management</title>
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
                    <h1 class="h2">Inventory</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add_item.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus"></i> Add Item
                            </a>
                        </div>
                        <div class="btn-group me-2">
                            <a href="export.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </div>
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
                
                <!-- Search Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Search Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="searchForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="make" class="form-label">Make</label>
                                    <input type="text" class="form-control" id="make" name="make" 
                                           value="<?php echo $filters['make']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="model" name="model" 
                                           value="<?php echo $filters['model']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                           value="<?php echo $filters['serial_number']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="property_number" class="form-label">Property Number</label>
                                    <input type="text" class="form-control" id="property_number" name="property_number" 
                                           value="<?php echo $filters['property_number']; ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
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
                                <div class="col-md-3">
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
                                <div class="col-md-3">
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
                                <div class="col-md-3">
                                    <label for="assigned_to" class="form-label">Assigned To</label>
                                    <input type="text" class="form-control" id="assigned_to" name="assigned_to" 
                                           value="<?php echo $filters['assigned_to']; ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <a href="inventory.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Results -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Inventory Items (<?php echo number_format($total); ?> total)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Make</th>
                                        <th>Model</th>
                                        <th>Serial Number</th>
                                        <th>Property Number</th>
                                        <th>Use Case</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">No items found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td><?php echo sanitizeInput($item['make']); ?></td>
                                                <td><?php echo sanitizeInput($item['model']); ?></td>
                                                <td><?php echo sanitizeInput($item['serial_number']); ?></td>
                                                <td><?php echo sanitizeInput($item['property_number']); ?></td>
                                                <td><?php echo sanitizeInput($item['use_case']); ?></td>
                                                <td><?php echo sanitizeInput($item['location_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 
                                                                           ($item['status'] === 'repair' ? 'warning' : 
                                                                           ($item['status'] === 'excess' ? 'info' : 'secondary')); ?>">
                                                        <?php echo ucfirst($item['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo sanitizeInput($item['assigned_to'] ?? '-'); ?></td>
                                                <td><?php echo formatDateTime($item['created_at'], 'Y-m-d'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_item.php?id=<?php echo $item['id']; ?>" class="btn btn-info" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-warning" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-danger" title="Delete" 
                                                                onclick="confirmDelete(<?php echo $item['id']; ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $page - 1])); ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $page + 1])); ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_item.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo generateCSRFToken(); ?>';
                
                form.appendChild(input);
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>