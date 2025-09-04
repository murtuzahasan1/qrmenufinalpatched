<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class SuperAdminDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('super_admin');
    }
    
    public function render() {
        $this->layout->renderHeader('Super Admin Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(5);
        $restaurants = $this->getRestaurants();
        $users = $this->getUsers();
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Total Restaurants', $stats['restaurants'], 'fas fa-utensils', 12);
        echo $this->renderStatsCard('Total Branches', $stats['branches'], 'fas fa-map-marker-alt', 8);
        echo $this->renderStatsCard('Total Users', $stats['users'], 'fas fa-users', 15);
        echo $this->renderStatsCard('Total Orders', $stats['orders'], 'fas fa-clipboard-list', 22);
        echo '</div>';
        
        // Recent Orders
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order ID'],
            ['key' => 'restaurant_name', 'label' => 'Restaurant'],
            ['key' => 'branch_name', 'label' => 'Branch'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Date', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable($recentOrders, $ordersColumns, $ordersActions);
        echo $this->renderContentCard('Recent Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Restaurants
        $restaurantColumns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Created', 'format' => 'date']
        ];
        
        $restaurantActions = [
            ['icon' => 'fas fa-edit', 'href' => '../restaurants.php?action=edit&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../restaurants.php?action=view&id={id}']
        ];
        
        $restaurantTable = $this->renderDataTable(array_slice($restaurants, 0, 5), $restaurantColumns, $restaurantActions);
        echo $this->renderContentCard('Recent Restaurants', $restaurantTable, '<a href="../restaurants.php" class="btn btn-primary">View All Restaurants</a>');
        
        // Users
        $userColumns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'restaurant_name', 'label' => 'Restaurant'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $userActions = [
            ['icon' => 'fas fa-edit', 'href' => '../users.php?action=edit&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../users.php?action=view&id={id}']
        ];
        
        $userTable = $this->renderDataTable(array_slice($users, 0, 5), $userColumns, $userActions);
        echo $this->renderContentCard('Recent Users', $userTable, '<a href="../users.php" class="btn btn-primary">View All Users</a>');
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new SuperAdminDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    }
} else {
    $dashboard = new SuperAdminDashboard();
    $dashboard->render();
}
?>