<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
            color: #ffffff;
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: #ffffff;
        }
        .main-content {
            padding: 2rem;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h5 class="text-white text-center mb-4">IT Inventory</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" onclick="showSection('inventory')">
                                <i class="fas fa-list me-2"></i>Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('users')">
                                <i class="fas fa-users me-2"></i>User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('profile')">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="logout()">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">IT Inventory Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportCSV()">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload"></i> Import CSV
                            </button>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Section -->
                <div id="inventorySection">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search inventory...">
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary" onclick="searchInventory()">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <button class="btn btn-secondary" onclick="clearSearch()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Make</th>
                                    <th>Model</th>
                                    <th>Serial Number</th>
                                    <th>Property Number</th>
                                    <th>Warranty End</th>
                                    <th>Excess Date</th>
                                    <th>Use Case</th>
                                    <th>Location</th>
                                    <th>On Site</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Management Section -->
                <div id="usersSection" style="display: none;">
                    <div class="d-flex justify-content-between mb-3">
                        <h3>User Management</h3>
                        <button class="btn btn-primary" onclick="showAddUserModal()">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Auth Type</th>
                                    <th>Active</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Profile Section -->
                <div id="profileSection" style="display: none;">
                    <h3>User Profile</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <form id="profileForm">
                                <div class="mb-3">
                                    <label for="profileUsername" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="profileUsername" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="profileEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="profileEmail">
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword">
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirmPassword">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="inventoryForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="make" class="form-label">Make *</label>
                                    <input type="text" class="form-control" id="make" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="model" class="form-label">Model *</label>
                                    <input type="text" class="form-control" id="model" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serialNumber" class="form-label">Serial Number *</label>
                                    <input type="text" class="form-control" id="serialNumber" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="propertyNumber" class="form-label">Property Number *</label>
                                    <input type="text" class="form-control" id="propertyNumber" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="warrantyEndDate" class="form-label">Warranty End Date</label>
                                    <input type="date" class="form-control" id="warrantyEndDate">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="excessDate" class="form-label">Excess Date</label>
                                    <input type="date" class="form-control" id="excessDate">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="useCase" class="form-label">Use Case *</label>
                                    <select class="form-select" id="useCase" required>
                                        <option value="">Select Use Case</option>
                                        <option value="Desktop">Desktop</option>
                                        <option value="Laptop">Laptop</option>
                                        <option value="Server">Server</option>
                                        <option value="Network Equipment">Network Equipment</option>
                                        <option value="Storage System">Storage System</option>
                                        <option value="Development">Development</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <select class="form-select" id="location" required>
                                        <option value="">Select Location</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="onSite" class="form-label">On Site/Remote *</label>
                                    <select class="form-select" id="onSite" required>
                                        <option value="On Site">On Site</option>
                                        <option value="Remote">Remote</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInventoryItem()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csvFile" accept=".csv" required>
                        </div>
                        <div class="alert alert-info">
                            <strong>Required columns:</strong> Make, Model, Serial Number, Property Number, Use Case, Location
                            <br>
                            <strong>Optional columns:</strong> Warranty End Date, Excess Date, On Site, Description
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="importCSV()">Import</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>