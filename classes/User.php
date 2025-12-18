<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $firstName;
    private $lastName;
    private $role;
    private $authType;
    private $isActive;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function authenticate($username, $password) {
        // Check for lockout
        if ($this->isLockedOut($username)) {
            return ['success' => false, 'message' => 'Account temporarily locked due to failed login attempts'];
        }
        
        // Try local authentication first
        if ($this->authenticateLocal($username, $password)) {
            $this->resetFailedAttempts($username);
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        // Try LDAP/SSSD authentication if enabled
        if (LDAP_ENABLED && $this->authenticateLDAP($username, $password)) {
            $this->resetFailedAttempts($username);
            return ['success' => true, 'message' => 'LDAP login successful'];
        }
        
        // Increment failed attempts
        $this->incrementFailedAttempts($username);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    private function authenticateLocal($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND auth_type = 'local' AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->setUserData($user);
            $this->updateLastLogin($user['id']);
            $this->createSession($user['id']);
            return true;
        }
        
        return false;
    }
    
    private function authenticateLDAP($username, $password) {
        $ldap = ldap_connect(LDAP_HOST, LDAP_PORT);
        if (!$ldap) {
            return false;
        }
        
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        
        $bind = @ldap_bind($ldap, LDAP_BIND_DN, LDAP_BIND_PASS);
        if (!$bind) {
            return false;
        }
        
        $search = ldap_search($ldap, LDAP_BASE_DN, "(uid=$username)");
        $entries = ldap_get_entries($ldap, $search);
        
        if ($entries['count'] == 1) {
            $user_dn = $entries[0]['dn'];
            $bind = @ldap_bind($ldap, $user_dn, $password);
            
            if ($bind) {
                // Check if user exists in local database
                $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND auth_type IN ('ldap', 'sssd')");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    // Create user from LDAP
                    $this->createUserFromLDAP($entries[0], $username);
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                }
                
                if ($user && $user['is_active']) {
                    $this->setUserData($user);
                    $this->updateLastLogin($user['id']);
                    $this->createSession($user['id']);
                    ldap_close($ldap);
                    return true;
                }
            }
        }
        
        ldap_close($ldap);
        return false;
    }
    
    private function createUserFromLDAP($ldapEntry, $username) {
        $email = isset($ldapEntry['mail'][0]) ? $ldapEntry['mail'][0] : $username . '@yourdomain.com';
        $firstName = isset($ldapEntry['givenname'][0]) ? $ldapEntry['givenname'][0] : $username;
        $lastName = isset($ldapEntry['sn'][0]) ? $ldapEntry['sn'][0] : 'User';
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, email, first_name, last_name, auth_type, role) 
            VALUES (?, '', ?, ?, ?, 'ldap', 'user')
        ");
        $stmt->execute([$username, $email, $firstName, $lastName]);
        
        // Assign to default LDAP group if configured
        $this->assignLDAPGroups($username, $ldapEntry);
    }
    
    private function assignLDAPGroups($username, $ldapEntry) {
        if (isset($ldapEntry['memberof'])) {
            foreach ($ldapEntry['memberof'] as $group_dn) {
                // Extract group name from DN
                if (preg_match('/CN=([^,]+)/', $group_dn, $matches)) {
                    $group_name = $matches[1];
                    
                    // Check if group exists in database
                    $stmt = $this->db->prepare("SELECT id FROM groups WHERE ldap_group_dn = ? OR name = ?");
                    $stmt->execute([$group_dn, $group_name]);
                    $group = $stmt->fetch();
                    
                    if ($group) {
                        $user_id = $this->db->getConnection()->lastInsertId();
                        $stmt = $this->db->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                        $stmt->execute([$user_id, $group['id']]);
                    }
                }
            }
        }
    }
    
    private function isLockedOut($username) {
        $stmt = $this->db->prepare("SELECT locked_until FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if ($result && $result['locked_until']) {
            $lockedUntil = new DateTime($result['locked_until']);
            $now = new DateTime();
            
            if ($now < $lockedUntil) {
                return true;
            } else {
                // Unlock the account
                $stmt = $this->db->prepare("UPDATE users SET locked_until = NULL, failed_login_attempts = 0 WHERE username = ?");
                $stmt->execute([$username]);
            }
        }
        
        return false;
    }
    
    private function incrementFailedAttempts($username) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE 
                    WHEN failed_login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE locked_until
                END
            WHERE username = ?
        ");
        $stmt->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION, $username]);
    }
    
    private function resetFailedAttempts($username) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
    }
    
    private function setUserData($user) {
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->email = $user['email'];
        $this->firstName = $user['first_name'];
        $this->lastName = $user['last_name'];
        $this->role = $user['role'];
        $this->authType = $user['auth_type'];
        $this->isActive = $user['is_active'];
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    private function createSession($userId) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $this->username;
        $_SESSION['role'] = $this->role;
        $_SESSION['login_time'] = time();
        
        // Store session in database
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent, $expiresAt]);
    }
    
    public static function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        
        // Update session activity
        $_SESSION['login_time'] = time();
        
        // Check if session exists in database
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM user_sessions 
            WHERE id = ? AND user_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([session_id(), $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            self::logout();
            return false;
        }
        
        return true;
    }
    
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE id = ?");
            $stmt->execute([session_id()]);
        }
        
        session_destroy();
        $_SESSION = array();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public static function requireRole($roles) {
        self::requireLogin();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Verify old password
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ? AND auth_type = 'local'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    public function getUserById($userId) {
        $stmt = $this->db->prepare("
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
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['email', 'first_name', 'last_name'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    // Getter methods
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getRole() { return $this->role; }
    public function getAuthType() { return $this->authType; }
    public function isActive() { return $this->isActive; }
}
?>