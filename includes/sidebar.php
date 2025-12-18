_POST["name"]<?php
/**
 * Sidebar Navigation Component
 *
 * This file generates the sidebar navigation for the IT Inventory system.
 * It includes role-based menu items and active state management.
 *
 * @package ITInventory
 * @author  IT Inventory System
 * @version 1.0
 */

// Security check: Ensure this file is included by another PHP file
if (!defined('INCLUDED_FROM_LAYOUT')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

/**
 * Get the current page filename for active state determination
 *
 * @return string Current page filename
 */
function getCurrentPage(): string {
    return basename($_SERVER['PHP_SELF'] ?? '');
}

/**
 * Determine if a menu item should be marked as active
 *
 * @param string $page Page filename to check
 * @return bool True if current page matches
 */
function isActivePage(string $page): bool {
    static $currentPage = null;
    
    // Cache the current page to avoid repeated basename() calls
    if ($currentPage === null) {
        $currentPage = getCurrentPage();
    }
    
    return $currentPage === $page;
}

/**
 * Render a navigation link with consistent structure
 *
 * @param string $href Link destination
 * @param string $icon Bootstrap icon class
 * @param string $text Link text
 * @param string $page Page filename for active state
 * @return string HTML for navigation link
 */
function renderNavLink(string $href, string $icon, string $text, string $page): string {
    $isActive = isActivePage($page);
    $activeClass = $isActive ? ' active' : '';
    
    return sprintf(
        '<li class="nav-item">
            <a class="nav-link%s" href="%s" aria-current="%s">
                <i class="%s" aria-hidden="true"></i> %s
            </a>
        </li>',
        $activeClass,
        htmlspecialchars($href, ENT_QUOTES, 'UTF-8'),
        $isActive ? 'page' : 'false',
        htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Check if user has admin role
 *
 * @return bool True if user is admin
 */
function isAdmin(): bool {
    static $isAdmin = null;
    
    // Cache admin check to avoid repeated session access
    if ($isAdmin === null) {
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    return $isAdmin;
}

// Menu configuration arrays for better maintainability
$mainMenuItems = [
    ['dashboard.php', 'bi bi-speedometer2', 'Dashboard'],
    ['inventory.php', 'bi bi-box-seam', 'Inventory'],
    ['add_item.php', 'bi bi-plus-circle', 'Add Item'],
    ['search.php', 'bi bi-search', 'Search'],
    ['import.php', 'bi bi-upload', 'Import CSV'],
    ['export.php', 'bi bi-download', 'Export CSV'],
    ['locations.php', 'bi bi-geo-alt', 'Locations'],
    ['reports.php', 'bi bi-file-text', 'Reports'],
];

$adminMenuItems = [
    ['users.php', 'bi bi-people', 'Users'],
    ['groups.php', 'bi bi-collection', 'Groups'],
    ['audit_log.php', 'bi bi-clock-history', 'Audit Log'],
    ['settings.php', 'bi bi-gear', 'Settings'],
];

// Error handling for session variables
try {
    $userRole = $_SESSION['role'] ?? 'user';
} catch (Exception $e) {
    // Fallback to default user role if session access fails
    $userRole = 'user';
    error_log('Session access error in sidebar: ' . $e->getMessage());
}
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" role="navigation" aria-label="Main navigation">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php
            // Render main menu items
            foreach ($mainMenuItems as [$page, $icon, $text]) {
                echo renderNavLink($page, $icon, $text, $page);
            }
            ?>
        </ul>
        
        <?php if (isAdmin()): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column">
            <?php
            // Render admin menu items
            foreach ($adminMenuItems as [$page, $icon, $text]) {
                echo renderNavLink($page, $icon, $text, $page);
            }
            ?>
        </ul>
        <?php endif; ?>
    </div>
</nav>