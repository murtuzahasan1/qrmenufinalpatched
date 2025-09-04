<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

class AdminRouter {
    private $auth;
    private $currentUser;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->currentUser = $this->auth->getCurrentUser();
        
        if (!$this->currentUser) {
            header('Location: ../login.html');
            exit;
        }
    }
    
    public function route() {
        $role = $this->currentUser['role'];
        $roleDashboards = [
            'super_admin' => 'superadmin/dashboard.php',
            'restaurant_owner' => 'owner/dashboard.php',
            'manager' => 'manager/dashboard.php',
            'branch_manager' => 'branch-manager/dashboard.php',
            'chef' => 'chef/dashboard.php',
            'waiter' => 'waiter/dashboard.php',
            'restaurant_staff' => 'restaurant-staff/dashboard.php'
        ];
        
        if (isset($roleDashboards[$role])) {
            header('Location: ' . $roleDashboards[$role]);
            exit;
        } else {
            // Fallback to generic dashboard
            header('Location: dashboard.php');
            exit;
        }
    }
}

// Handle direct access to admin index
$router = new AdminRouter();
$router->route();
?>