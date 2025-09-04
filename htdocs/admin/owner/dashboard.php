<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class OwnerDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('restaurant_owner');
    }
    
    public function render() {
        $this->layout->renderHeader('Restaurant Owner Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(5);
        $branches = $this->getBranches();
        $users = $this->getUsers();
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Total Branches', $stats['branches'], 'fas fa-map-marker-alt', 8);
        echo $this->renderStatsCard('Total Staff', $stats['users'], 'fas fa-users', 15);
        echo $this->renderStatsCard('Total Orders', $stats['orders'], 'fas fa-clipboard-list', 22);
        echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'], 'fas fa-calendar-day', 5);
        echo '</div>';
        
        // Revenue Card
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Total Revenue', '৳' . number_format($stats['revenue'], 2), 'fas fa-money-bill-wave', 18);
        echo '</div>';
        
        // Recent Orders
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order ID'],
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
        
        // Branches
        $branchColumns = [
            ['key' => 'name', 'label' => 'Branch Name'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $branchActions = [
            ['icon' => 'fas fa-edit', 'href' => '../branches.php?action=edit&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../branches.php?action=view&id={id}']
        ];
        
        $branchTable = $this->renderDataTable(array_slice($branches, 0, 5), $branchColumns, $branchActions);
        echo $this->renderContentCard('Your Branches', $branchTable, '<a href="../branches.php" class="btn btn-primary">Manage Branches</a>');
        
        // Staff
        $userColumns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'branch_name', 'label' => 'Branch'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $userActions = [
            ['icon' => 'fas fa-edit', 'href' => '../users.php?action=edit&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../users.php?action=view&id={id}']
        ];
        
        $userTable = $this->renderDataTable(array_slice($users, 0, 5), $userColumns, $userActions);
        echo $this->renderContentCard('Your Staff', $userTable, '<a href="../users.php" class="btn btn-primary">Manage Staff</a>');
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new OwnerDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    }
} else {
    $dashboard = new OwnerDashboard();
    $dashboard->render();
}
?>