<?php
require_once __DIR__ . '/../includes/BaseDashboard.php';
require_once __DIR__ . '/../templates/layout.php';

class RestaurantStaffDashboard extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
        $this->requireRole('restaurant_staff');
    }
    
    public function render() {
        $this->layout->renderHeader('Restaurant Staff Dashboard');
        
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders(8);
        $menuItems = $this->getMenuItems();
        
        // Filter orders by viewable status
        $viewableOrders = array_filter($recentOrders, function($order) {
            return in_array($order['status'], ['pending', 'preparing', 'ready', 'served', 'completed']);
        });
        
        // Render stats cards
        echo '<div class="stats-grid">';
        echo $this->renderStatsCard('Today\'s Orders', $stats['today_orders'], 'fas fa-calendar-day', 10);
        echo $this->renderStatsCard('Active Orders', count($viewableOrders), 'fas fa-clipboard-list', 5);
        echo $this->renderStatsCard('Menu Items', count($menuItems), 'fas fa-book', 2);
        echo $this->renderStatsCard('Available Items', count(array_filter($menuItems, function($item) { return $item['available']; })), 'fas fa-check-circle', 1);
        echo '</div>';
        
        // Today's Orders Overview
        $ordersColumns = [
            ['key' => 'id', 'label' => 'Order #'],
            ['key' => 'table_number', 'label' => 'Table'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Time', 'format' => 'date']
        ];
        
        $ordersActions = [
            ['icon' => 'fas fa-eye', 'href' => '../orders.php?action=view&id={id}']
        ];
        
        $ordersTable = $this->renderDataTable(array_slice($viewableOrders, 0, 6), $ordersColumns, $ordersActions);
        echo $this->renderContentCard('Today\'s Orders', $ordersTable, '<a href="../orders.php" class="btn btn-primary">View All Orders</a>');
        
        // Menu Availability
        $availableItems = array_filter($menuItems, function($item) {
            return $item['available'];
        });
        
        $unavailableItems = array_filter($menuItems, function($item) {
            return !$item['available'];
        });
        
        $menuContent = '
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div style="text-align: center; padding: 1rem; background: #dcfce7; border-radius: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: #166534;">' . count($availableItems) . '</div>
                    <div style="color: #166534;">Available Items</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #fee2e2; border-radius: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: #991b1b;">' . count($unavailableItems) . '</div>
                    <div style="color: #991b1b;">Unavailable Items</div>
                </div>
            </div>
        ';
        
        $menuColumns = [
            ['key' => 'name', 'label' => 'Item Name'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'price', 'label' => 'Price', 'format' => 'price'],
            ['key' => 'available', 'label' => 'Available', 'format' => 'status']
        ];
        
        $menuActions = [
            ['icon' => 'fas fa-eye', 'href' => '../menu.php?action=view&id={id}']
        ];
        
        $menuTable = $this->renderDataTable(array_slice($menuItems, 0, 6), $menuColumns, $menuActions);
        $menuContent .= $menuTable;
        
        echo $this->renderContentCard('Menu Availability', $menuContent, '<a href="../menu.php" class="btn btn-primary">View Full Menu</a>');
        
        // Order Status Summary
        $statusSummary = [];
        foreach ($viewableOrders as $order) {
            $status = $order['status'];
            if (!isset($statusSummary[$status])) {
                $statusSummary[$status] = 0;
            }
            $statusSummary[$status]++;
        }
        
        $statusContent = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">';
        
        $statusColors = [
            'pending' => '#fef3c7',
            'preparing' => '#dbeafe',
            'ready' => '#dcfce7',
            'served' => '#e0e7ff',
            'completed' => '#f3f4f6'
        ];
        
        $statusIcons = [
            'pending' => 'fas fa-clock',
            'preparing' => 'fas fa-fire',
            'ready' => 'fas fa-check-circle',
            'served' => 'fas fa-hand-holding',
            'completed' => 'fas fa-check-double'
        ];
        
        foreach ($statusSummary as $status => $count) {
            $statusContent .= '
                <div style="text-align: center; padding: 1rem; background: ' . $statusColors[$status] . '; border-radius: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: #374151;">' . $count . '</div>
                    <div style="color: #374151; margin: 0.5rem 0;">
                        <i class="' . $statusIcons[$status] . '"></i>
                    </div>
                    <div style="color: #374151; text-transform: capitalize;">' . $status . '</div>
                </div>';
        }
        
        $statusContent .= '</div>';
        
        echo $this->renderContentCard('Order Status Summary', $statusContent, 
            '<a href="../orders.php" class="btn btn-primary">View Order Details</a>');
        
        // Quick Information
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Information</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <h5><i class="fas fa-info-circle"></i> Current Shift</h5>
                        <p>Today: ' . date('l, F j, Y') . '</p>
                        <p>Time: ' . date('h:i A') . '</p>
                    </div>
                    <div>
                        <h5><i class="fas fa-utensils"></i> Kitchen Status</h5>
                        <p><span class="status-badge active">Open</span></p>
                        <p>Operating Hours: 10:00 AM - 10:00 PM</p>
                    </div>
                </div>
                <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem;">
                    <h5 style="color: #0369a1; margin-bottom: 0.5rem;">
                        <i class="fas fa-lightbulb"></i> Staff Tip
                    </h5>
                    <p style="color: #0369a1;">Always check the order status before informing customers about their food readiness. 
                    This helps manage customer expectations and improve satisfaction.</p>
                </div>
            </div>
        </div>';
        
        // Quick Actions
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../orders.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-eye"></i><br>View Orders
                    </a>
                    <a href="../menu.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-book"></i><br>View Menu
                    </a>
                    <a href="../reports.php?type=staff" class="btn btn-info" style="text-align: center;">
                        <i class="fas fa-chart-bar"></i><br>Daily Report
                    </a>
                    <a href="../help.php" class="btn btn-secondary" style="text-align: center;">
                        <i class="fas fa-question-circle"></i><br>Get Help
                    </a>
                </div>
            </div>
        </div>';
        
        $this->layout->renderFooter();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $dashboard = new RestaurantStaffDashboard();
    
    if ($_GET['ajax'] === 'stats') {
        $stats = $dashboard->getDashboardStats();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    } elseif ($_GET['ajax'] === 'menu_availability') {
        $menuItems = $dashboard->getMenuItems();
        $available = count(array_filter($menuItems, function($item) { return $item['available']; }));
        $unavailable = count(array_filter($menuItems, function($item) { return !$item['available']; }));
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => [
                'available' => $available,
                'unavailable' => $unavailable,
                'total' => count($menuItems)
            ]
        ]);
    }
} else {
    $dashboard = new RestaurantStaffDashboard();
    $dashboard->render();
}
?>