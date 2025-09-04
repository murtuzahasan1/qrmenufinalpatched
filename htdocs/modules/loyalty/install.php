<?php
// Loyalty Module Install Script
function module_install() {
    global $db;
    
    try {
        // Create loyalty tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS loyalty_customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                points_balance INTEGER DEFAULT 0,
                total_earned INTEGER DEFAULT 0,
                total_spent DECIMAL(10, 2) DEFAULT 0,
                tier VARCHAR(20) DEFAULT 'bronze',
                join_date DATE NOT NULL,
                last_activity DATE,
                referral_code VARCHAR(10) UNIQUE,
                referred_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES users(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS loyalty_points (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                order_id INTEGER,
                points INTEGER NOT NULL,
                type VARCHAR(20) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES loyalty_customers(id),
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS loyalty_rewards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                restaurant_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                points_required INTEGER NOT NULL,
                reward_type VARCHAR(50) NOT NULL,
                reward_value TEXT,
                is_active INTEGER DEFAULT 1,
                stock_quantity INTEGER DEFAULT -1,
                expiry_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS loyalty_redemptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                reward_id INTEGER NOT NULL,
                points_used INTEGER NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                redemption_date DATETIME,
                used_date DATETIME,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES loyalty_customers(id),
                FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id)
            )"
        ];
        
        foreach ($tables as $sql) {
            $db->query($sql);
        }
        
        // Insert default rewards
        $defaultRewards = [
            [
                'restaurant_id' => 1,
                'name' => '10% Discount',
                'description' => 'Get 10% off your next order',
                'points_required' => 500,
                'reward_type' => 'discount',
                'reward_value' => '10'
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Free Dessert',
                'description' => 'Complimentary dessert with your meal',
                'points_required' => 300,
                'reward_type' => 'free_item',
                'reward_value' => 'dessert'
            ],
            [
                'restaurant_id' => 1,
                'name' => 'Free Drink',
                'description' => 'Complimentary beverage with your meal',
                'points_required' => 200,
                'reward_type' => 'free_item',
                'reward_value' => 'drink'
            ]
        ];
        
        foreach ($defaultRewards as $reward) {
            $db->insert('loyalty_rewards', $reward);
        }
        
        return ['success' => true, 'message' => 'Loyalty module installed successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Installation failed: ' . $e->getMessage()];
    }
}

function module_uninstall() {
    global $db;
    
    try {
        // Drop loyalty tables
        $tables = ['loyalty_redemptions', 'loyalty_rewards', 'loyalty_points', 'loyalty_customers'];
        
        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS $table");
        }
        
        return ['success' => true, 'message' => 'Loyalty module uninstalled successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Uninstallation failed: ' . $e->getMessage()];
    }
}
?>