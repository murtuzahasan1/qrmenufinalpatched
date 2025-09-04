<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class WaiterDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('waiter');
    }
    
    public function render() {
        $this->layout->renderHeader('Waiter Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(10);
        $menuItems = $this->getMenuItems();
        
        // Filter orders by waiter-relevant status
        $activeOrders = array_filter($recentOrders, function($order) {
            return in_array($order['status'], ['pending', 'preparing', 'ready', 'served']);
        });
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Active Tables', count(array_unique(array_column($activeOrders, 'table_number'))), 'fas fa-table', 'medium');
        echo $this->renderStatsCard('My Orders', count($activeOrders), 'fas fa-clipboard-list', 'high');
        echo $this->renderStatsCard('Ready to Serve', count(array_filter($activeOrders, function($o) { return $o['status'] === 'ready'; })), 'fas fa-bell', 'urgent');
        echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'], 'fas fa-calendar-day', 12);
        echo '</div>';
        
        // Active Tables Display
        $tableNumbers = array_unique(array_column($activeOrders, 'table_number'));
        $tableStats = [];
        
        foreach ($tableNumbers as $tableNumber) {
            $tableOrders = array_filter($activeOrders, function($order) use ($tableNumber) {
                return $order['table_number'] === $tableNumber;
            });
            
            $tableStats[$tableNumber] = [
                'orders' => count($tableOrders),
                'total_amount' => array_sum(array_column($tableOrders, 'total_amount')),
                'status' => $this->getTableStatus($tableOrders)
            ];
        }
        
        $tableContent = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">';
        
        foreach ($tableStats as $tableNumber => $stats) {
            $statusClass = $stats['status'] === 'available' ? 'active' : 'pending';
            $bgColor = $stats['status'] === 'available' ? '#dcfce7' : '#fef3c7';
            $textColor = $stats['status'] === 'available' ? '#166534' : '#92400e';
            
            $tableContent .= '
                <div style="text-align: center; padding: 1rem; background: ' . $bgColor . '; border-radius: 0.5rem; cursor: pointer; 
                           border: 2px solid ' . ($stats['status'] === 'available' ? '#bbf7d0' : '#fde68a') . ';" 
                     onclick="window.location.href=\'../orders.php?table=' . $tableNumber . '\'">
                    <div style="font-size: 1.5rem; font-weight: bold; color: ' . $textColor . ';">Table ' . $tableNumber . '</div>
                    <div style="color: ' . $textColor . '; margin: 0.5rem 0;">' . $stats['orders'] . ' orders</div>
                    <div style="color: ' . $textColor . '; font-weight: bold;">৳' . number_format($stats['total_amount'], 2) . '</div>
                    <span class="status-badge ' . $statusClass . '" style="margin-top: 0.5rem;">
                        ' . ucfirst($stats['status']) . '
                    </span>
                </div>';
        }
        
        $tableContent .= '</div>';
        
        echo $this->renderContentCard('Active Tables', $tableContent, 
            '<a href="../orders.php?action=new" class="btn btn-primary">New Order</a>');
        
        // My Orders
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order #'],
            ['key' => 'table_number', 'label' => 'Table'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Time', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}'],
            ['icon' => 'fas fa-plus', 'href' => '../orders.php?action=add-item&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable(array_slice($activeOrders, 0, 8), $ordersColumns, $ordersActions);
        echo $this->renderContentCard('My Active Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Ready to Serve Alert
        $readyOrders = array_filter($activeOrders, function($order) {
            return $order['status'] === 'ready';
        });
        
        if (!empty($readyOrders)) {
            $readyContent = '<div style="background: #dbeafe; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #3b82f6;">
                <h4 style="color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-bell"></i> Ready to Serve!
                </h4>
                <p style="color: #1e40af; margin-bottom: 1rem;">The following orders are ready to be served to customers:</p>';
            
            foreach (array_slice($readyOrders, 0, 3) as $order) {
                $readyContent .= '
                    <div style="background: white; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 0.25rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Order #' . htmlspecialchars($order['id']) . '</strong><br>
                            <small>Table ' . htmlspecialchars($order['table_number']) . ' - ' . htmlspecialchars($order['customer_name']) . '</small>
                        </div>
                        <a href="../orders.php?action=serve&id=' . $order['id'] . '" class="btn btn-primary btn-sm">
                            <i class="fas fa-hand-holding"></i> Serve
                        </a>
                    </div>';
            }
            
            $readyContent .= '</div>';
            
            echo $this->renderContentCard('Ready to Serve', $readyContent, 
                '<a href="../orders.php?status=ready" class="btn btn-primary">View All Ready Orders</a>');
        }
        
        // Popular Menu Items (for recommendations)
        $popularItems = array_slice($menuItems, 0, 6);
        $menuColumns = [
            ['key' => 'name', 'label' => 'Item'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'price', 'label' => 'Price', 'format' => 'price'],
            ['key' => 'available', 'label' => 'Available', 'format' => 'status']
        ];
        
        $menuActions = [
            ['icon' => 'fas fa-plus', 'href' => '../orders.php?action=quick-add&item={id}']
        ];
        
        $menuTable = $this->renderDataTable($popularItems, $menuColumns, $menuActions);
        echo $this->renderContentCard('Popular Menu Items', $menuTable, '<a href="../menu.php" class="btn btn-primary">View Full Menu</a>');
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../orders.php?action=new" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i><br>New Order
                    </a>
                    <a href="../orders.php?status=ready" class="btn btn-success" style="text-align: center;">
                        <i class="fas fa-hand-holding"></i><br>Serve Order
                    </a>
                    <a href="../orders.php?action=payment" class="btn btn-warning" style="text-align: center;">
                        <i class="fas fa-money-bill"></i><br>Process Payment
                    </a>
                    <a href="../reports.php?type=waiter" class="btn btn-info" style="text-align: center;">
                        <i class="fas fa-chart-line"></i><br>My Performance
                    </a>
                </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
    
    private function getTableStatus($tableOrders) {
        foreach ($tableOrders as $order) {
            if (in_array($order['status'], ['pending', 'preparing'])) {
                return 'occupied';
            }
        }
        return 'available';
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new WaiterDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    } elseif ($_GET['ajax'] === 'ready_orders') {
        $orders = $dashboard->getRecentOrders(10);
        $readyOrders = array_filter($orders, function($order) {
            return $order['status'] === 'ready';
        });
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => array_values($readyOrders)]);
    }
} else {
    $dashboard = new WaiterDashboard();
    $dashboard->render();
}
?>