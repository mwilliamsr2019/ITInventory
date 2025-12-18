<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/User.php';

// Check if user is logged in and has admin role
User::requireRole('admin');

setSecurityHeaders();
setNoCacheHeaders();

$user = new User();
$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $result = createUser($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update':
                $result = updateUser($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = deleteUser($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'reset_password':
                $result = resetUserPassword($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get all users
$db = Database::getInstance();
$usersStmt = $db->prepare("
    SELECT u.*, 
           GROUP_CONCAT(g.name SEPARATOR ', ') as groups,
           l.name as location_name
    FROM users u
    LEFT JOIN user_groups ug ON u.id = ug.user_id
    LEFT JOIN groups g ON ug.group_id = g.id
    LEFT JOIN locations l ON u.location_id = l.id
    GROUP BY u.id
    ORDER BY u.username
");
$usersStmt->execute();
$users = $usersStmt->fetchAll();

// Get groups for dropdown
$groupsStmt = $db->prepare("SELECT id, name FROM groups ORDER BY name");
$groupsStmt->execute();
$groups = $groupsStmt->fetchAll();

function createUser($data) {
    global $db;
    
    // Validate required fields
    $requiredFields = ['username', 'email', 'first_name', 'last_name', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    $username = sanitizeInput($data['username']);
    $email = sanitizeInput($data['email']);
    $firstName = sanitizeInput($data['first_name']);
    $lastName = sanitizeInput($data['last_name']);
    $role = sanitizeInput($data['role']);
    $authType = sanitizeInput($data['auth_type'] ?? 'local');
    $isActive = isset($data['is_active']) ? 1 : 0;
    
    // Validate email
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Check if username exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Generate random password for local users
    $passwordHash = '';
    if ($authType === 'local') {
        $password = bin2hex(random_bytes(8)); // 16 character random password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }
    
    try {
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (username, password_hash, email, first_name, last_name, role, auth_type, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $passwordHash, $email, $firstName, $lastName, $role, $authType, $isActive]);
        
        $userId = $db->lastInsertId();
        
        // Assign to groups
        if (!empty($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $groupId) {
                $stmt = $db->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                $stmt->execute([$userId, $groupId]);
            }
        }
        
        // Log audit
        logAudit('users', $userId, 'insert', null, [
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'auth_type' => $authType,
            'is_active' => $isActive
        ]);
        
        $message = "User created successfully";
        if ($authType === 'local') {
            $message .= ". Temporary password: $password";
        }
        
        return ['success' => true, 'message' => $message];
        
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create user'];
    }
}

function updateUser($data) {
    global $db;
    
    if (empty($data['user_id'])) {
        return ['success' => false, 'message' => 'User ID is required'];
    }
    
    $userId = (int)$data['user_id'];
    
    // Get existing user data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $existingUser = $stmt->fetch();
    
    if (!$existingUser) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Validate required fields
    $requiredFields = ['email', 'first_name', 'last_name', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    $email = sanitizeInput($data['email']);
    $firstName = sanitizeInput($data['first_name']);
    $lastName = sanitizeInput($data['last_name']);
    $role = sanitizeInput($data['role']);
    $isActive = isset($data['is_active']) ? 1 : 0;
    
    // Validate email
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Check if email exists (excluding current user)
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    try {
        // Update user
        $stmt = $db->prepare("
            UPDATE users 
            SET email = ?, first_name = ?, last_name = ?, role = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$email, $firstName, $lastName, $role, $isActive, $userId]);
        
        // Update groups
        $stmt = $db->prepare("DELETE FROM user_groups WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if (!empty($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $groupId) {
                $stmt = $db->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                $stmt->execute([$userId, $groupId]);
            }
        }
        
        // Log audit
        $newData = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'is_active' => $isActive
        ];
        
        logAudit('users', $userId, 'update', $existingUser, $newData);
        
        return ['success' => true, 'message' => 'User updated successfully'];
        
    } catch (PDOException $e) {
        error_log("Error updating user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update user'];
    }
}

function deleteUser($data) {
    global $db;
    
    if (empty($data['user_id'])) {
        return ['success' => false, 'message' => 'User ID is required'];
    }
    
    $userId = (int)$data['user_id'];
    
    // Prevent deleting yourself
    if ($userId == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'You cannot delete your own account'];
    }
    
    // Get user data before deletion
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    try {
        // Delete user (cascade will handle related records)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Log audit
        logAudit('users', $userId, 'delete', $user, null);
        
        return ['success' => true, 'message' => 'User deleted successfully'];
        
    } catch (PDOException $e) {
        error_log("Error deleting user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
}

function resetUserPassword($data) {
    global $db;
    
    if (empty($data['user_id'])) {
        return ['success' => false, 'message' => 'User ID is required'];
    }
    
    $userId = (int)$data['user_id'];
    
    // Get user data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND auth_type = 'local'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found or not a local user'];
    }
    
    // Generate new password
    $newPassword = bin2hex(random_bytes(8)); // 16 character random password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    try {
        // Update password
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);
        
        // Log audit
        logAudit('users', $userId, 'password_reset', ['username' => $user['username']], null);
        
        return ['success' => true, 'message' => "Password reset successfully. New password: $newPassword"];
        
    } catch (PDOException $e) {
        error_log("Error resetting password: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IT Inventory Management</title>
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
                    <h1 class="h2">User Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus"></i> Add User
                        </button>
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
                
                <!-- Users Table -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Auth Type</th>
                                        <th>Groups</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo sanitizeInput($user['username']); ?></td>
                                            <td><?php echo sanitizeInput($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo sanitizeInput($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 
                                                                       ($user['role'] === 'manager' ? 'warning' : 'primary'); ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['auth_type'] === 'local' ? 'success' : 'info'; ?>">
                                                    <?php echo strtoupper($user['auth_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo sanitizeInput($user['groups'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" onclick="viewUser(<?php echo $user['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($user['auth_type'] === 'local'): ?>
                                                        <button type="button" class="btn btn-dark" onclick="resetPassword(<?php echo $user['id']; ?>)">
                                                            <i class="bi bi-key"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" name="role" required>
                                        <option value="user">User</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="auth_type" class="form-label">Authentication Type</label>
                                    <select class="form-select" name="auth_type">
                                        <option value="local">Local</option>
                                        <?php if (LDAP_ENABLED): ?>
                                            <option value="ldap">LDAP</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="groups" class="form-label">Groups</label>
                            <select class="form-select" name="groups[]" multiple size="4">
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>"><?php echo sanitizeInput($group['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple groups</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUser(id) {
            window.location.href = 'view_user.php?id=' + id;
        }
        
        function editUser(id) {
            window.location.href = 'edit_user.php?id=' + id;
        }
        
        function resetPassword(id) {
            if (confirm('Are you sure you want to reset this user\'s password? A new password will be generated.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>