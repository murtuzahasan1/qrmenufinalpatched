<?php
// Analytics Module Hooks
function analytics_after_order_created($params) {
    global $db;
    
    $order = $params['order'];
    
    try {
        // Insert order analytics
        $db->insert('analytics_orders', [
            'order_id' => $order['id'],
            'restaurant_id' => $order['restaurant_id'],
            'branch_id' => $order['branch_id'],
            'total_amount' => $order['total_amount'],
            'item_count' => count($order['items']),
            'customer_type' => $order['customer_name'] ? 'registered' : 'guest',
            'order_date' => date('Y-m-d', strtotime($order['created_at'])),
            'order_time' => date('H:i:s', strtotime($order['created_at']))
        ]);
        
        // Update menu item analytics
        foreach ($order['items'] as $item) {
            $existing = $db->fetch(
                "SELECT * FROM analytics_menu_items WHERE menu_item_id = ? AND restaurant_id = ?",
                [$item['menu_item_id'], $order['restaurant_id']]
            );
            
            if ($existing) {
                $db->update('analytics_menu_items', [
                    'order_count' => $existing['order_count'] + 1,
                    'total_quantity' => $existing['total_quantity'] + $item['quantity'],
                    'total_revenue' => $existing['total_revenue'] + $item['total_price'],
                    'last_ordered' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                $db->insert('analytics_menu_items', [
                    'menu_item_id' => $item['menu_item_id'],
                    'restaurant_id' => $order['restaurant_id'],
                    'order_count' => 1,
                    'total_quantity' => $item['quantity'],
                    'total_revenue' => $item['total_price'],
                    'last_ordered' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function analytics_generate_daily_report($params) {
    global $db;
    
    $restaurantId = $params['restaurant_id'] ?? null;
    $date = $params['date'] ?? date('Y-m-d');
    
    try {
        $report = [];
        
        // Sales report
        $sales = $db->fetch(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM analytics_orders 
            WHERE restaurant_id = ? AND order_date = ?",
            [$restaurantId, $date]
        );
        
        $report['sales'] = $sales;
        
        // Popular items
        $popularItems = $db->fetchAll(
            "SELECT mi.name, ami.order_count, ami.total_quantity, ami.total_revenue
            FROM analytics_menu_items ami
            JOIN menu_items mi ON ami.menu_item_id = mi.id
            WHERE ami.restaurant_id = ? AND DATE(ami.last_ordered) = ?
            ORDER BY ami.order_count DESC LIMIT 10",
            [$restaurantId, $date]
        );
        
        $report['popular_items'] = $popularItems;
        
        // Hourly sales
        $hourlySales = $db->fetchAll(
            "SELECT 
                strftime('%H', order_time) as hour,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM analytics_orders 
            WHERE restaurant_id = ? AND order_date = ?
            GROUP BY strftime('%H', order_time)
            ORDER BY hour",
            [$restaurantId, $date]
        );
        
        $report['hourly_sales'] = $hourlySales;
        
        return ['success' => true, 'data' => $report];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function analytics_dashboard_widgets($params) {
    global $db;
    
    $restaurantId = $params['restaurant_id'];
    
    try {
        $widgets = [];
        
        // Today's sales widget
        $todaySales = $db->fetch(
            "SELECT 
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM analytics_orders 
            WHERE restaurant_id = ? AND order_date = ?",
            [$restaurantId, date('Y-m-d')]
        );
        
        $widgets['today_sales'] = [
            'title' => 'Today\'s Sales',
            'orders' => $todaySales['orders'] ?? 0,
            'revenue' => $todaySales['revenue'] ?? 0
        ];
        
        // Top selling items
        $topItems = $db->fetchAll(
            "SELECT mi.name, ami.total_quantity
            FROM analytics_menu_items ami
            JOIN menu_items mi ON ami.menu_item_id = mi.id
            WHERE ami.restaurant_id = ?
            ORDER BY ami.total_quantity DESC LIMIT 5",
            [$restaurantId]
        );
        
        $widgets['top_items'] = [
            'title' => 'Top Selling Items',
            'items' => $topItems
        ];
        
        return ['success' => true, 'widgets' => $widgets];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>