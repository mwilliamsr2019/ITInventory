<?php
/**
 * Navigation Bar Component
 *
 * This file contains the main navigation bar for the IT Inventory system.
 * It handles user session validation, role-based menu items, and security features.
 *
 * @package ITInventory
 * @version 1.0.0
 */

// Security check - ensure session is started and user is authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate user session and redirect if necessary
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Clear any partial session data
    session_unset();
    session_destroy();
    
    // Redirect to login with return URL
    $currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header("Location: login.php?redirect=$currentUrl");
    exit();
}

// Define navigation configuration for better maintainability
$navConfig = [
    'brand' => [
        'name' => 'IT Inventory',
        'url' => 'dashboard.php',
        'icon' => 'bi bi-server'
    ],
    'user_menu' => [
        'profile' => [
            'label' => 'Profile',
            'url' => 'profile.php',
            'icon' => 'bi bi-person',
            'visible' => true
        ],
        'change_password' => [
            'label' => 'Change Password',
            'url' => 'change_password.php',
            'icon' => 'bi bi-key',
            'visible' => true
        ],
        'user_management' => [
            'label' => 'User Management',
            'url' => 'users.php',
            'icon' => 'bi bi-people',
            'visible' => $_SESSION['role'] === 'admin'
        ],
        'settings' => [
            'label' => 'Settings',
            'url' => 'settings.php',
            'icon' => 'bi bi-gear',
            'visible' => $_SESSION['role'] === 'admin'
        ],
        'logout' => [
            'label' => 'Logout',
            'url' => 'logout.php',
            'icon' => 'bi bi-box-arrow-right',
            'visible' => true
        ]
    ]
];

// Sanitize user data for output
$username = sanitizeInput($_SESSION['username'] ?? 'User');
$role = sanitizeInput($_SESSION['role'] ?? 'user');

// Generate CSRF token for logout link
$logoutToken = generateCSRFToken();

// Set security headers for this component
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <!-- Brand/Logo Section -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo htmlspecialchars($navConfig['brand']['url']); ?>"
           title="<?php echo htmlspecialchars($navConfig['brand']['name']); ?>">
            <i class="<?php echo htmlspecialchars($navConfig['brand']['icon']); ?> me-2"></i>
            <?php echo htmlspecialchars($navConfig['brand']['name']); ?>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- User Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                       id="userDropdown" role="button" data-bs-toggle="dropdown"
                       aria-expanded="false" title="User menu">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-lg-inline"><?php echo $username; ?></span>
                        <?php if ($role === 'admin'): ?>
                            <span class="badge bg-warning ms-1"><?php echo strtoupper($role); ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php
                        $dividerAdded = false;
                        foreach ($navConfig['user_menu'] as $key => $menuItem):
                            if (!$menuItem['visible']) continue;
                            
                            // Add divider before admin-only items
                            if (in_array($key, ['user_management', 'settings']) && !$dividerAdded):
                                $dividerAdded = true;
                        ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php
                            endif;
                        ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center"
                                   href="<?php echo htmlspecialchars($menuItem['url']); ?>"
                                   <?php if ($key === 'logout'): ?>
                                       onclick="return confirmLogout(event)"
                                   <?php endif; ?>>
                                    <i class="<?php echo htmlspecialchars($menuItem['icon']); ?> me-2"></i>
                                    <?php echo htmlspecialchars($menuItem['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Security and UX Enhancement Scripts -->
<script>
/**
 * Confirm logout action and handle CSRF token
 * @param {Event} event - The click event
 * @returns {boolean} - Whether to proceed with logout
 */
function confirmLogout(event) {
    event.preventDefault();
    
    if (confirm('Are you sure you want to logout?')) {
        // Create a form to submit logout with CSRF token
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'logout.php';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'csrf_token';
        tokenInput.value = '<?php echo $logoutToken; ?>';
        
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    return false;
}

// Add keyboard navigation support
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.getElementById('userDropdown');
    if (dropdownToggle) {
        // Allow Enter key to toggle dropdown
        dropdownToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                dropdownToggle.click();
            }
        });
    }
});
</script>