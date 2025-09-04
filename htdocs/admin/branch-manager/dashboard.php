<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class BranchManagerDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('branch_manager');
    }
    
    public function render() {
        $this->layout->renderHeader('Branch Manager Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(5);
        $users = $this->getUsers();
        $menuItems = $this->getMenuItems();
        
        // Get branch info
        $branchInfo = $this->db->fetch("SELECT * FROM branches WHERE id = ?", [$this->currentUser['branch_id']]);
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Total Staff', $stats['users'], 'fas fa-users', 12);
        echo $this->renderStatsCard('Total Orders', $stats['orders'], 'fas fa-clipboard-list', 18);
        echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'], 'fas fa-calendar-day', 8);
        echo $this->renderStatsCard('Menu Items', count($menuItems), 'fas fa-book', 5);
        echo '</div>';
        
        // Revenue Card
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Branch Revenue', '৳' . number_format($stats['revenue'], 2), 'fas fa-money-bill-wave', 15);
        echo '</div>';
        
        // Branch Info Card
        if ($branchInfo) {
            $branchInfoContent = '
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong>Branch Name:</strong><br>
                        ' . htmlspecialchars($branchInfo['name']) . '
                    </div>
                    <div>
                        <strong>Phone:</strong><br>
                        ' . htmlspecialchars($branchInfo['phone']) . '
                    </div>
                    <div>
                        <strong>Address:</strong><br>
                        ' . htmlspecialchars($branchInfo['address']) . '
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span class="status-badge ' . ($branchInfo['status'] ? 'active' : 'pending') . '">
                            ' . ucfirst($branchInfo['status'] ? 'Active' : 'Inactive') . '
                        </span>
                    </div>
                </div>
            ';
            
            echo $this->renderContentCard('Branch Information', $branchInfoContent, 
                '<a href="../branches.php?action=edit&id=' . $branchInfo['id'] . '" class="btn btn-primary">Edit Branch</a>');
        }
        
        // Recent Orders - Focus on active orders
        $activeOrders = array_filter($recentOrders, function($order) {
            return in_array($order['status'], ['pending', 'preparing', 'ready']);
        });
        
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order ID'],
            ['key' => 'table_number', 'label' => 'Table'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Time', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}'],
            ['icon' => 'fas fa-edit', 'href' => '../orders.php?action=update&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable(array_slice($activeOrders, 0, 5), $ordersColumns, $ordersActions);
        echo $this->renderContentCard('Active Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Staff Management
        $userColumns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status']
        ];
        
        $userActions = [
            ['icon' => 'fas fa-edit', 'href' => '../users.php?action=edit&id={id}'],
            ['icon' => 'fas fa-calendar', 'href' => '../users.php?action=schedule&id={id}']
        ];
        
        $userTable = $this->renderDataTable(array_slice($users, 0, 5), $userColumns, $userActions);
        echo $this->renderContentCard('Branch Staff', $userTable, '<a href="../users.php" class="btn btn-primary">Manage Staff</a>');
        
        // Popular Menu Items
        $popularItems = array_slice($menuItems, 0, 5);
        $menuColumns = [
            ['key' => 'name', 'label' => 'Item Name'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'price', 'label' => 'Price', 'format' => 'price'],
            ['key' => 'available', 'label' => 'Available', 'format' => 'status']
        ];
        
        $menuActions = [
            ['icon' => 'fas fa-edit', 'href' => '../menu.php?action=edit&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../menu.php?action=view&id={id}']
        ];
        
        $menuTable = $this->renderDataTable($popularItems, $menuColumns, $menuActions);
        echo $this->renderContentCard('Popular Menu Items', $menuTable, '<a href="../menu.php" class="btn btn-primary">Manage Menu</a>');
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../orders.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i><br>New Order
                    </a>
                    <a href="../users.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-user-plus"></i><br>Add Staff
                    </a>
                    <a href="../menu.php?action=add" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i><br>Add Menu Item
                    </a>
                    <a href="../reports.php?branch=1" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-chart-line"></i><br>Branch Report
                    </a>
                </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new BranchManagerDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    }
} else {
    $dashboard = new BranchManagerDashboard();
    $dashboard->render();
}
?>