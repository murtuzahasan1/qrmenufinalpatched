<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class GenericDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(5);
        
        // Render basic stats cards
        echo '<div class="stats-grid">';
        
        if ($this->currentUser['role'] === 'super_admin') {
            echo $this->renderStatsCard('Total Restaurants', $stats['restaurants'], 'fas fa-utensils');
            echo $this->renderStatsCard('Total Branches', $stats['branches'], 'fas fa-map-marker-alt');
            echo $this->renderStatsCard('Total Users', $stats['users'], 'fas fa-users');
            echo $this->renderStatsCard('Total Orders', $stats['orders'], 'fas fa-clipboard-list');
        } else {
            echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'] ?? 0, 'fas fa-calendar-day');
            echo $this->renderStatsCard('Active Orders', $stats['orders'], 'fas fa-clipboard-list');
            
            if (isset($stats['branches'])) {
                echo $this->renderStatsCard('Branches', $stats['branches'], 'fas fa-map-marker-alt');
            }
            
            if (isset($stats['users'])) {
                echo $this->renderStatsCard('Staff', $stats['users'], 'fas fa-users');
            }
            
            if (isset($stats['revenue'])) {
                echo $this->renderStatsCard('Revenue', '৳' . number_format($stats['revenue'], 2), 'fas fa-money-bill-wave');
            }
        }
        
        echo '</div>';
        
        // Recent Orders
        if (!empty($recentOrders)) {
            $ordersColumns = [
                ['key' => 'id', 'label' => 'Order ID'],
                ['key' => 'customer_name', 'label' => 'Customer'],
                ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
                ['key' => 'created_at', 'label' => 'Date', 'format' => 'date']
            ];
            
            $ordersActions = [
                ['icon' => 'fas fa-eye', 'href' => 'orders.php?action=view&id={id}']
            ];
            
            $ordersTable = $this->renderDataTable($recentOrders, $ordersColumns, $ordersActions);
            echo $this->renderContentCard('Recent Orders', $ordersTable, '<a href="orders.php" class="btn btn-primary">View All Orders</a>');
        }
        
        // Role-specific information
        $this->renderRoleSpecificInfo();
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';
        
        $actions = $this->getRoleActions();
        foreach ($actions as $action) {
            echo '<a href="' . htmlspecialchars($action['href']) . '" class="btn btn-primary" style="text-align: center;">
                <i class="' . htmlspecialchars($action['icon']) . '"></i><br>' . htmlspecialchars($action['text']) . '
            </a>';
        }
        
        echo '      </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
    
    private function renderRoleSpecificInfo() {
        $role = $this->currentUser['role'];
        
        switch ($role) {
            case 'super_admin':
                $restaurants = $this->getRestaurants();
                if (!empty($restaurants)) {
                    $restaurantColumns = [
                        ['key' => 'name', 'label' => 'Restaurant'],
                        ['key' => 'status', 'label' => 'Status', 'format' => 'status']
                    ];
                    $restaurantTable = $this->renderDataTable(array_slice($restaurants, 0, 5), $restaurantColumns);
                    echo $this->renderContentCard('Recent Restaurants', $restaurantTable, '<a href="restaurants.php" class="btn btn-primary">View All</a>');
                }
                break;
                
            case 'restaurant_owner':
            case 'manager':
                $branches = $this->getBranches();
                if (!empty($branches)) {
                    $branchColumns = [
                        ['key' => 'name', 'label' => 'Branch'],
                        ['key' => 'status', 'label' => 'Status', 'format' => 'status']
                    ];
                    $branchTable = $this->renderDataTable(array_slice($branches, 0, 5), $branchColumns);
                    echo $this->renderContentCard('Your Branches', $branchTable, '<a href="branches.php" class="btn btn-primary">Manage Branches</a>');
                }
                break;
                
            case 'chef':
                $menuItems = $this->getMenuItems();
                if (!empty($menuItems)) {
                    $menuColumns = [
                        ['key' => 'name', 'label' => 'Item'],
                        ['key' => 'available', 'label' => 'Available', 'format' => 'status']
                    ];
                    $menuTable = $this->renderDataTable(array_slice($menuItems, 0, 5), $menuColumns);
                    echo $this->renderContentCard('Menu Items', $menuTable, '<a href="menu.php" class="btn btn-primary">Manage Menu</a>');
                }
                break;
                
            case 'waiter':
                $activeOrders = array_filter($this->getRecentOrders(10), function($order) {
                    return in_array($order['status'], ['pending', 'preparing', 'ready']);
                });
                if (!empty($activeOrders)) {
                    echo '<div class="content-card">
                        <div class="card-header">
                            <h3 class="card-title">Active Orders</h3>
                        </div>
                        <div class="card-content">
                            <p>You have ' . count($activeOrders) . ' active orders to manage.</p>
                            <a href="orders.php" class="btn btn-primary">View Orders</a>
                        </div>
                    </div>';
                }
                break;
        }
    }
    
    private function getRoleActions() {
        $role = $this->currentUser['role'];
        
        $actions = [
            'super_admin' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Restaurant', 'href' => 'restaurants.php?action=add'],
                ['icon' => 'fas fa-users', 'text' => 'Manage Users', 'href' => 'users.php'],
                ['icon' => 'fas fa-chart-bar', 'text' => 'View Reports', 'href' => 'reports.php'],
                ['icon' => 'fas fa-cog', 'text' => 'Settings', 'href' => 'settings.php']
            ],
            'restaurant_owner' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Branch', 'href' => 'branches.php?action=add'],
                ['icon' => 'fas fa-users', 'text' => 'Manage Staff', 'href' => 'users.php'],
                ['icon' => 'fas fa-chart-bar', 'text' => 'View Reports', 'href' => 'reports.php'],
                ['icon' => 'fas fa-qrcode', 'text' => 'QR Codes', 'href' => 'qrcode.php']
            ],
            'manager' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add User', 'href' => 'users.php?action=add'],
                ['icon' => 'fas fa-clipboard-list', 'text' => 'Manage Orders', 'href' => 'orders.php'],
                ['icon' => 'fas fa-book', 'text' => 'Manage Menu', 'href' => 'menu.php'],
                ['icon' => 'fas fa-chart-bar', 'text' => 'View Reports', 'href' => 'reports.php']
            ],
            'branch_manager' => [
                ['icon' => 'fas fa-plus', 'text' => 'Add Menu Item', 'href' => 'menu.php?action=add'],
                ['icon' => 'fas fa-clipboard-list', 'text' => 'Manage Orders', 'href' => 'orders.php'],
                ['icon' => 'fas fa-users', 'text' => 'Manage Staff', 'href' => 'users.php'],
                ['icon' => 'fas fa-chart-line', 'text' => 'Branch Report', 'href' => 'reports.php?branch=1']
            ],
            'chef' => [
                ['icon' => 'fas fa-fire', 'text' => 'Kitchen Orders', 'href' => 'orders.php?status=preparing'],
                ['icon' => 'fas fa-book', 'text' => 'Manage Menu', 'href' => 'menu.php'],
                ['icon' => 'fas fa-chart-bar', 'text' => 'Kitchen Report', 'href' => 'reports.php?type=kitchen'],
                ['icon' => 'fas fa-eye', 'text' => 'View Orders', 'href' => 'orders.php']
            ],
            'waiter' => [
                ['icon' => 'fas fa-plus', 'text' => 'New Order', 'href' => 'orders.php?action=new'],
                ['icon' => 'fas fa-hand-holding', 'text' => 'Serve Orders', 'href' => 'orders.php?status=ready'],
                ['icon' => 'fas fa-money-bill', 'text' => 'Process Payment', 'href' => 'orders.php?action=payment'],
                ['icon' => 'fas fa-eye', 'text' => 'View Orders', 'href' => 'orders.php']
            ],
            'restaurant_staff' => [
                ['icon' => 'fas fa-eye', 'text' => 'View Orders', 'href' => 'orders.php'],
                ['icon' => 'fas fa-book', 'text' => 'View Menu', 'href' => 'menu.php'],
                ['icon' => 'fas fa-chart-bar', 'text' => 'Daily Report', 'href' => 'reports.php?type=staff'],
                ['icon' => 'fas fa-question-circle', 'text' => 'Get Help', 'href' => 'help.php']
            ]
        ];
        
        return $actions[$role] || [];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new GenericDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    }
} else {
    $dashboard = new GenericDashboard();
    $dashboard->render();
}
?>