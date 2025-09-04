<?php
// This is the fix: Ensure the Database class is loaded before it is used.
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function safe_session_start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($email, $password) {
        // Check login attempts
        if ($this->isLoginBlocked($email)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }

        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? AND u.status = 1",
            [$email]
        );

        if (!$user) {
            $this->recordLoginAttempt($email, false);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!password_verify($password, $user['password'])) {
            $this->recordLoginAttempt($email, false);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Clear login attempts
        $this->clearLoginAttempts($email);

        // Set session
        $this->safe_session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_permissions'] = json_decode($user['permissions'], true);
        $_SESSION['restaurant_id'] = $user['restaurant_id'];
        $_SESSION['branch_id'] = $user['branch_id'];
        $_SESSION['last_activity'] = time();

        // Update last login
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );

        return ['success' => true, 'user' => $user];
    }

    public function logout() {
        $this->safe_session_start();
        session_destroy();
        return ['success' => true];
    }

    public function isLoggedIn() {
        $this->safe_session_start();
        return isset($_SESSION['user_id']) && $this->isSessionValid();
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
    }

    public function requireRole($requiredRole) {
        $this->requireAuth();
        
        if ($_SESSION['user_role'] !== $requiredRole && $_SESSION['user_role'] !== 'super_admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
    }

    public function requirePermission($permission) {
        $this->requireAuth();
        
        if ($_SESSION['user_role'] === 'super_admin') {
            return true;
        }

        if (!in_array($permission, $_SESSION['user_permissions'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'permissions' => $_SESSION['user_permissions'],
            'restaurant_id' => $_SESSION['restaurant_id'],
            'branch_id' => $_SESSION['branch_id']
        ];
    }

    private function isSessionValid() {
        return isset($_SESSION['last_activity']) && 
               (time() - $_SESSION['last_activity']) < SESSION_LIFETIME;
    }

    private function isLoginBlocked($email) {
        $attempts = $this->db->fetchAll(
            "SELECT * FROM login_attempts 
             WHERE email = ? AND created_at > datetime('now', '-15 minutes') 
             AND success = 0",
            [$email]
        );

        return count($attempts) >= MAX_LOGIN_ATTEMPTS;
    }

    private function recordLoginAttempt($email, $success) {
        $this->db->insert('login_attempts', [
            'email' => $email,
            'success' => $success ? 1 : 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }

    private function clearLoginAttempts($email) {
        $this->db->delete('login_attempts', 'email = ?', [$email]);
    }

    public function hashPassword($password) {
        return password_hash($password, HASH_ALGO);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>