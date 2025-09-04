<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/utils.php';

class AdminLayout {
    private $auth;
    private $utils;
    private $currentUser;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->utils = new Utils();
        $this->currentUser = $this->auth->getCurrentUser();
        
        if (!$this->currentUser) {
            header('Location: ../login.html');
            exit;
        }
    }
    
    public function renderHeader($title = 'Admin Dashboard') {
        $roleNames = [
            'super_admin' => 'Super Admin',
            'restaurant_owner' => 'Restaurant Owner',
            'manager' => 'Manager',
            'branch_manager' => 'Branch Manager',
            'chef' => 'Chef',
            'waiter' => 'Waiter',
            'restaurant_staff' => 'Restaurant Staff'
        ];
        
        $currentRole = $roleNames[$this->currentUser['role']] || 'User';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?> - QR Menu System</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body>
            <header class="header">
                <div class="container">
                    <div class="header-content">
                        <div class="logo">
                            <i class="fas fa-qrcode"></i>
                            <span>QR Menu System</span>
                        </div>
                        <nav>
                            <ul class="nav-menu">
                                <?php $this->renderNavigationItems(); ?>
                            </ul>
                        </nav>
                        <div class="user-info">
                            <div class="user-avatar" id="userAvatar">
                                <?php echo $this->getRoleInitials($this->currentUser['role']); ?>
                            </div>
                            <span><?php echo htmlspecialchars($this->currentUser['name']); ?></span>
                            <a href="../login.html" class="btn btn-secondary" onclick="return confirm('Are you sure you want to logout?');">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            <main class="dashboard">
                <div class="container">
                    <div class="dashboard-header">
                        <h1 class="dashboard-title"><?php echo htmlspecialchars($title); ?></h1>
                        <div class="dashboard-actions">
                            <?php $this->renderDashboardActions(); ?>
                        </div>
                    </div>
        <?php
    }
    
    public function renderFooter() {
        ?>
                </div>
            </main>
            <script src="../assets/js/app.js"></script>
            <script src="../assets/js/role-navigation.js"></script>
            <script>
                // Initialize dashboard functionality
                document.addEventListener('DOMContentLoaded', function() {
                    // Role navigation is handled by role-navigation.js
                    if (typeof window.roleNavigation !== 'undefined') {
                        window.roleNavigation.init();
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    private function renderNavigationItems() {
        $permissions = $this->getRolePermissions($this->currentUser['role']);
        
        $navItems = [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard', 'href' => 'dashboard.php', 'permission' => 'all'],
            ['icon' => 'fas fa-utensils', 'text' => 'Restaurants', 'href' => 'restaurants.php', 'permission' => 'manage_restaurant'],
            ['icon' => 'fas fa-map-marker-alt', 'text' => 'Branches', 'href' => 'branches.php', 'permission' => 'manage_branches'],
            ['icon' => 'fas fa-users', 'text' => 'Users', 'href' => 'users.php', 'permission' => 'manage_staff'],
            ['icon' => 'fas fa-clipboard-list', 'text' => 'Orders', 'href' => 'orders.php', 'permission' => 'manage_orders'],
            ['icon' => 'fas fa-book', 'text' => 'Menu', 'href' => 'menu.php', 'permission' => 'manage_menu'],
            ['icon' => 'fas fa-qrcode', 'text' => 'QR Codes', 'href' => 'qrcode.php', 'permission' => 'manage_qrcode'],
            ['icon' => 'fas fa-chart-bar', 'text' => 'Reports', 'href' => 'reports.php', 'permission' => 'view_reports'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'href' => 'settings.php', 'permission' => 'manage_settings']
        ];
        
        foreach ($navItems as $item) {
            if ($this->currentUser['role'] === 'super_admin' || in_array($item['permission'], $permissions)) {
                echo '<li><a href="' . htmlspecialchars($item['href']) . '"><i class="' . htmlspecialchars($item['icon']) . '"></i> ' . htmlspecialchars($item['text']) . '</a></li>';
            }
        }
    }
    
    private function renderDashboardActions() {
        $actions = $this->getRoleActions($this->currentUser['role']);
        
        foreach ($actions as $action) {
            echo '<button class="btn btn-primary" onclick="' . htmlspecialchars($action['onclick']) . '">';
            echo '<i class="' . htmlspecialchars($action['icon']) . '"></i> ' . htmlspecialchars($action['text']);
            echo '</button>';
        }
    }
    
    private function getRolePermissions($role) {
        $permissions = [
            'super_admin' => ['all'],
            'restaurant_owner' => ['manage_restaurant', 'manage_branches', 'manage_menu', 'manage_orders', 'manage_staff', 'view_reports'],
            'manager' => ['manage_branches', 'manage_menu', 'manage_orders', 'manage_staff', 'view_reports'],
            'branch_manager' => ['manage_menu', 'manage_orders', 'manage_staff', 'view_reports'],
            'chef' => ['view_menu', 'manage_orders', 'view_reports'],
            'waiter' => ['view_menu', 'manage_orders'],
            'restaurant_staff' => ['view_menu', 'view_orders']
        ];
        
        return $permissions[$role] || [];
    }
    
    private function getRoleActions($role) {
        $actions = [
            'super_admin' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Restaurant', 'onclick' => 'window.location.href=\'restaurants.php?action=add\'']
            ],
            'restaurant_owner' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Branch', 'onclick' => 'window.location.href=\'branches.php?action=add\'']
            ],
            'manager' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add User', 'onclick' => 'window.location.href=\'users.php?action=add\'']
            ],
            'branch_manager' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Menu Item', 'onclick' => 'window.location.href=\'menu.php?action=add\'']
            ],
            'chef' => [
                ['icon' => 'fas fa-eye', 'text' => 'View Orders', 'onclick' => 'window.location.href=\'orders.php\'']
            ],
            'waiter' => [
                ['icon' => 'fas fa-plus', 'text' => 'New Order', 'onclick' => 'window.location.href=\'orders.php?action=add\'']
            ],
            'restaurant_staff' => [
                ['icon' => 'fas fa-eye', 'text' => 'View Menu', 'onclick' => 'window.location.href=\'menu.php\'']
            ]
        ];
        
        return $actions[$role] || [];
    }
    
    private function getRoleInitials($role) {
        $initials = [
            'super_admin' => 'SA',
            'restaurant_owner' => 'RO',
            'manager' => 'M',
            'branch_manager' => 'BM',
            'chef' => 'C',
            'waiter' => 'W',
            'restaurant_staff' => 'RS'
        ];
        
        return $initials[$role] || 'U';
    }
}
?>