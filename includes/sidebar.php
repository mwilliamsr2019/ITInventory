<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                    <i class="bi bi-box-seam"></i> Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'add_item.php' ? 'active' : ''; ?>" href="add_item.php">
                    <i class="bi bi-plus-circle"></i> Add Item
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>" href="search.php">
                    <i class="bi bi-search"></i> Search
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'import.php' ? 'active' : ''; ?>" href="import.php">
                    <i class="bi bi-upload"></i> Import CSV
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'export.php' ? 'active' : ''; ?>" href="export.php">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'locations.php' ? 'active' : ''; ?>" href="locations.php">
                    <i class="bi bi-geo-alt"></i> Locations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="bi bi-file-text"></i> Reports
                </a>
            </li>
        </ul>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'groups.php' ? 'active' : ''; ?>" href="groups.php">
                    <i class="bi bi-collection"></i> Groups
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit_log.php' ? 'active' : ''; ?>" href="audit_log.php">
                    <i class="bi bi-clock-history"></i> Audit Log
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</nav>