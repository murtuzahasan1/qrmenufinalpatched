<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class ManagerDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('manager');
    }
    
    public function render() {
        $this->layout->renderHeader('Manager Dashboard');
        
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
        
        // Recent Orders with Priority
        $priorityOrders = array_filter($recentOrders, function($order) {
            return in_array($order['status'], ['pending', 'preparing']);
        });
        
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order ID'],
            ['key' => 'branch_name', 'label' => 'Branch'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Date', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}'],
            ['icon' => 'fas fa-edit', 'href' => '../orders.php?action=edit&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable($recentOrders, $ordersColumns, $ordersActions);
        echo $this->renderContentCard('Recent Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Branches Performance
        $branchColumns = [
            ['key' => 'name', 'label' => 'Branch Name'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $branchActions = [
            ['icon' => 'fas fa-chart-line', 'href' => '../branches.php?action=performance&id={id}'],
            ['icon' => 'fas fa-edit', 'href' => '../branches.php?action=edit&id={id}']
        ];
        
        $branchTable = $this->renderDataTable(array_slice($branches, 0, 5), $branchColumns, $branchActions);
        echo $this->renderContentCard('Branches Performance', $branchTable, '<a href="../branches.php" class="btn btn-primary">Manage Branches</a>');
        
        // Staff Management
        $userColumns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'branch_name', 'label' => 'Branch'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $userActions = [
            ['icon' => 'fas fa-edit', 'href' => '../users.php?action=edit&id={id}'],
            ['icon' => 'fas fa-user-clock', 'href' => '../users.php?action=schedule&id={id}']
        ];
        
        $userTable = $this->renderDataTable(array_slice($users, 0, 5), $userColumns, $userActions);
        echo $this->renderContentCard('Staff Management', $userTable, '<a href="../users.php" class="btn btn-primary">Manage Staff</a>');
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../orders.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i><br>Create Order
                    </a>
                    <a href="../users.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-user-plus"></i><br>Add Staff
                    </a>
                    <a href="../branches.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-map-marker-alt"></i><br>Add Branch
                    </a>
                    <a href="../reports.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-chart-bar"></i><br>View Reports
                    </a>
                </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new ManagerDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    }
} else {
    $dashboard = new ManagerDashboard();
    $dashboard->render();
}
?>