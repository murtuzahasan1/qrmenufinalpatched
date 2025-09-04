<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class ChefDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('chef');
    }
    
    public function render() {
        $this->layout->renderHeader('Chef Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(10);
        $menuItems = $this->getMenuItems();
        
        // Filter orders by kitchen status
        $kitchenOrders = array_filter($recentOrders, function($order) {
            return in_array($order['status'], ['pending', 'preparing', 'ready']);
        });
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Pending Orders', count(array_filter($kitchenOrders, function($o) { return $o['status'] === 'pending'; })), 'fas fa-clock', 'high');
        echo $this->renderStatsCard('Preparing Orders', count(array_filter($kitchenOrders, function($o) { return $o['status'] === 'preparing'; })), 'fas fa-fire', 'medium');
        echo $this->renderStatsCard('Ready Orders', count(array_filter($kitchenOrders, function($o) { return $o['status'] === 'ready'; })), 'fas fa-check-circle', 'low');
        echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'], 'fas fa-calendar-day', 15);
        echo '</div>';
        
        // Kitchen Orders Display
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order #'],
            ['key' => 'table_number', 'label' => 'Table'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Time', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-utensils', 'href' => '../orders.php?action=prepare&id={id}'],
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable($kitchenOrders, $ordersColumns, $ordersActions);
        echo $this->renderContentCard('Kitchen Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Menu Management
        $menuColumns = [
            ['key' => 'name', 'label' => 'Dish Name'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'price', 'label' => 'Price', 'format' => 'price'],
            ['key' => 'available', 'label' => 'Available', 'format' => 'status']
        ];
        
        $menuActions = [
            ['icon' => 'fas fa-edit', 'href' => '../menu.php?action=edit&id={id}'],
            ['icon' => 'fas fa-toggle-on', 'href' => '../menu.php?action=toggle&id={id}']
        ];
        
        $menuTable = $this->renderDataTable(array_slice($menuItems, 0, 8), $menuColumns, $menuActions);
        echo $this->renderContentCard('Menu Items', $menuTable, '<a href="../menu.php" class="btn btn-primary">Manage Menu</a>');
        
        // Order Priority Display
        $priorityOrders = array_filter($kitchenOrders, function($order) {
            $orderTime = strtotime($order['created_at']);
            $currentTime = time();
            $timeDiff = ($currentTime - $orderTime) / 60; // minutes
            
            return $timeDiff > 15 || $order['status'] === 'pending';
        });
        
        if (!empty($priorityOrders)) {
            $priorityContent = '<div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                <h4 style="color: #92400e; margin-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Priority Orders
                </h4>
                <p style="color: #92400e; margin-bottom: 1rem;">These orders need immediate attention!</p>';
            
            foreach (array_slice($priorityOrders, 0, 3) as $order) {
                $priorityContent .= '
                    <div style="background: white; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 0.25rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Order #' . htmlspecialchars($order['id']) . '</strong><br>
                            <small>Table ' . htmlspecialchars($order['table_number']) . ' - ' . htmlspecialchars($order['customer_name']) . '</small>
                        </div>
                        <span class="status-badge ' . ($order['status'] === 'pending' ? 'pending' : 'active') . '">
                            ' . ucfirst($order['status']) . '
                        </span>
                    </div>';
            }
            
            $priorityContent .= '</div>';
            
            echo $this->renderContentCard('Priority Orders', $priorityContent, 
                '<a href="../orders.php?filter=priority" class="btn btn-danger">Handle Priority Orders</a>');
        }
        
        // Kitchen Performance
        $performanceContent = '
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="text-align: center; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: #0369a1;">85%</div>
                    <div style="color: #0369a1;">On-Time Delivery</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f0fdf4; border-radius: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: #166534;">92%</div>
                    <div style="color: #166534;">Customer Satisfaction</div>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <h5>Today\'s Performance</h5>
                <div style="background: #e5e7eb; height: 8px; border-radius: 4px; margin: 0.5rem 0;">
                    <div style="background: #10b981; height: 100%; width: 85%; border-radius: 4px;"></div>
                </div>
                <small style="color: #6b7280;">85% of orders completed on time</small>
            </div>
        ';
        
        echo $this->renderContentCard('Kitchen Performance', $performanceContent, 
            '<a href="../reports.php?type=kitchen" class="btn btn-primary">View Detailed Report</a>');
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../orders.php?status=preparing" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-fire"></i><br>Start Preparing
                    </a>
                    <a href="../orders.php?status=ready" class="btn btn-success" style="text-align: center;">
                        <i class="fas fa-check"></i><br>Mark Ready
                    </a>
                    <a href="../menu.php?available=0" class="btn btn-warning" style="text-align: center;">
                        <i class="fas fa-times"></i><br>Out of Stock
                    </a>
                    <a href="../reports.php?type=kitchen" class="btn btn-info" style="text-align: center;">
                        <i class="fas fa-chart-bar"></i><br>Kitchen Report
                    </a>
                </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new ChefDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    } elseif ($_GET['ajax'] === 'kitchen_orders') {
        $orders = $dashboard->getRecentOrders(10);
        $kitchenOrders = array_filter($orders, function($order) {
            return in_array($order['status'], ['pending', 'preparing', 'ready']);
        });
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => array_values($kitchenOrders)]);
    }
} else {
    $dashboard = new ChefDashboard();
    $dashboard->render();
}
?>