<?php
// Analytics Module Install Script
function module_install() {
    global $db;
    
    try {
        // Create analytics tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS analytics_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                restaurant_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                total_amount DECIMAL(10, 2) NOT NULL,
                item_count INTEGER NOT NULL,
                customer_type VARCHAR(50),
                order_date DATE NOT NULL,
                order_time TIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS analytics_menu_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                menu_item_id INTEGER NOT NULL,
                restaurant_id INTEGER NOT NULL,
                order_count INTEGER DEFAULT 0,
                total_quantity INTEGER DEFAULT 0,
                total_revenue DECIMAL(10, 2) DEFAULT 0,
                last_ordered DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS analytics_customer_behavior (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                restaurant_id INTEGER NOT NULL,
                session_id VARCHAR(255),
                page_views INTEGER DEFAULT 0,
                menu_views INTEGER DEFAULT 0,
                cart_adds INTEGER DEFAULT 0,
                orders_placed INTEGER DEFAULT 0,
                total_spent DECIMAL(10, 2) DEFAULT 0,
                visit_date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            $db->query($sql);
        }
        
        return ['success' => true, 'message' => 'Analytics module installed successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Installation failed: ' . $e->getMessage()];
    }
}

function module_uninstall() {
    global $db;
    
    try {
        // Drop analytics tables
        $tables = ['analytics_customer_behavior', 'analytics_menu_items', 'analytics_orders'];
        
        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS $table");
        }
        
        return ['success' => true, 'message' => 'Analytics module uninstalled successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Uninstallation failed: ' . $e->getMessage()];
    }
}
?>