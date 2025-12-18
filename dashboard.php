<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/Inventory.php';

// Check if user is logged in
User::requireLogin();

// Set headers before any output
setSecurityHeaders();
setNoCacheHeaders();

$user = new User();
$inventory = new Inventory();

// Get dashboard statistics
$db = Database::getInstance();

// Total items
$totalItems = $db->prepare("SELECT COUNT(*) as total FROM inventory_items WHERE status = 'active'");
$totalItems->execute();
$totalItems = $totalItems->fetch()['total'];

// Items by use case
$itemsByUseCase = $db->prepare("
    SELECT use_case, COUNT(*) as count 
    FROM inventory_items 
    WHERE status = 'active' 
    GROUP BY use_case 
    ORDER BY count DESC
");
$itemsByUseCase->execute();
$itemsByUseCase = $itemsByUseCase->fetchAll();

// Items by location
$itemsByLocation = $db->prepare("
    SELECT l.name as location, COUNT(*) as count 
    FROM inventory_items i
    JOIN locations l ON i.location_id = l.id
    WHERE i.status = 'active'
    GROUP BY l.name 
    ORDER BY count DESC
");
$itemsByLocation->execute();
$itemsByLocation = $itemsByLocation->fetchAll();

// Warranty expiring soon (next 30 days)
$warrantyExpiring = $db->prepare("
    SELECT COUNT(*) as count 
    FROM inventory_items 
    WHERE warranty_end_date IS NOT NULL 
    AND warranty_end_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
    AND status = 'active'
");
$warrantyExpiring->execute();
$warrantyExpiring = $warrantyExpiring->fetch()['count'];

// Recent items
$recentItems = $inventory->searchItems([], 1, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Inventory Management</title>
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add_item.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus"></i> Add Item
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
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalItems; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Warranty Expiring (30 days)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $warrantyExpiring; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Items by Use Case</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-bar">
                                    <canvas id="useCaseChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Items by Location</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-bar">
                                    <canvas id="locationChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Items -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Items</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Make</th>
                                        <th>Model</th>
                                        <th>Serial Number</th>
                                        <th>Property Number</th>
                                        <th>Use Case</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentItems['items'] as $item): ?>
                                        <tr>
                                            <td><?php echo sanitizeInput($item['make']); ?></td>
                                            <td><?php echo sanitizeInput($item['model']); ?></td>
                                            <td><?php echo sanitizeInput($item['serial_number']); ?></td>
                                            <td><?php echo sanitizeInput($item['property_number']); ?></td>
                                            <td><?php echo sanitizeInput($item['use_case']); ?></td>
                                            <td><?php echo sanitizeInput($item['location_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : ($item['status'] === 'repair' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo sanitizeInput($item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Use Case Chart
        const useCaseCtx = document.getElementById('useCaseChart').getContext('2d');
        new Chart(useCaseCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($itemsByUseCase, 'use_case')); ?>,
                datasets: [{
                    label: 'Items',
                    data: <?php echo json_encode(array_column($itemsByUseCase, 'count')); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.8)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Location Chart
        const locationCtx = document.getElementById('locationChart').getContext('2d');
        new Chart(locationCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($itemsByLocation, 'location')); ?>,
                datasets: [{
                    label: 'Items',
                    data: <?php echo json_encode(array_column($itemsByLocation, 'count')); ?>,
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>