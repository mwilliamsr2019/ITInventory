<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/User.php';

// Check if user is logged in
User::requireLogin();

setSecurityHeaders();
setNoCacheHeaders();

$user = new User();
$error = '';
$success = '';

// Get current user data
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT u.*, 
           GROUP_CONCAT(g.name SEPARATOR ', ') as groups,
           l.name as location_name
    FROM users u
    LEFT JOIN user_groups ug ON u.id = ug.user_id
    LEFT JOIN groups g ON ug.group_id = g.id
    LEFT JOIN locations l ON u.location_id = l.id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $result = $user->updateProfile($_SESSION['user_id'], [
                    'email' => sanitizeInput($_POST['email'] ?? ''),
                    'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                    'last_name' => sanitizeInput($_POST['last_name'] ?? '')
                ]);
                
                if ($result['success']) {
                    $success = $result['message'];
                    // Refresh user data
                    $stmt->execute([$_SESSION['user_id']]);
                    $userData = $stmt->fetch();
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'change_password':
                $oldPassword = $_POST['old_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'All password fields are required';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match';
                } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
                } else {
                    $result = $user->changePassword($_SESSION['user_id'], $oldPassword, $newPassword);
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - IT Inventory Management</title>
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
                    <h1 class="h2">My Profile</h1>
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
                
                <div class="row">
                    <div class="col-lg-6">
                        <!-- Profile Information -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo sanitizeInput($userData['username']); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo sanitizeInput($userData['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo sanitizeInput($userData['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo sanitizeInput($userData['last_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" 
                                               value="<?php echo ucfirst($userData['role']); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="auth_type" class="form-label">Authentication Type</label>
                                        <input type="text" class="form-control" id="auth_type" 
                                               value="<?php echo strtoupper($userData['auth_type']); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="groups" class="form-label">Groups</label>
                                        <input type="text" class="form-control" id="groups" 
                                               value="<?php echo sanitizeInput($userData['groups'] ?? 'None'); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="created_at" class="form-label">Account Created</label>
                                        <input type="text" class="form-control" id="created_at" 
                                               value="<?php echo formatDateTime($userData['created_at']); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_login" class="form-label">Last Login</label>
                                        <input type="text" class="form-control" id="last_login" 
                                               value="<?php echo $userData['last_login'] ? formatDateTime($userData['last_login']) : 'Never'; ?>" disabled>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <?php if ($userData['auth_type'] === 'local'): ?>
                            <!-- Change Password -->
                            <div class="card shadow mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="old_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-key"></i> Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card shadow mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Authentication</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Your account uses <?php echo strtoupper($userData['auth_type']); ?> authentication. 
                                        Password changes must be made through your <?php echo strtoupper($userData['auth_type']); ?> provider.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Account Statistics -->
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Account Statistics</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get user's inventory items count
                                $itemsStmt = $db->prepare("SELECT COUNT(*) FROM inventory_items WHERE created_by = ?");
                                $itemsStmt->execute([$_SESSION['user_id']]);
                                $itemsCount = $itemsStmt->fetchColumn();
                                
                                // Get user's recent activity
                                $activityStmt = $db->prepare("
                                    SELECT COUNT(*) 
                                    FROM audit_log 
                                    WHERE user_id = ? 
                                    AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
                                ");
                                $activityStmt->execute([$_SESSION['user_id']]);
                                $recentActivity = $activityStmt->fetchColumn();
                                ?>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <h4 class="text-primary"><?php echo number_format($itemsCount); ?></h4>
                                            <small class="text-muted">Items Created</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <h4 class="text-success"><?php echo number_format($recentActivity); ?></h4>
                                            <small class="text-muted">Actions (30 days)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>