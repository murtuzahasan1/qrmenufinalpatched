<?php
require_once __DIR__ . '/../config/config.php';
require_once APP_ROOT . '/includes/database.php';

class DatabaseInitializer {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function initialize() {
        try {
            $this->db->getConnection()->beginTransaction();
            
            // Create tables
            $this->createRolesTable();
            $this->createUsersTable();
            $this->createRestaurantsTable();
            $this->createBranchesTable();
            $this->createMenuCategoriesTable();
            $this->createMenuItemsTable();
            $this->createOrdersTable();
            $this->createOrderItemsTable();
            $this->createLoginAttemptsTable();
            $this->createModulesTable();
            $this->createSettingsTable();
            
            // Create indexes for performance
            $this->createIndexes();
            
            // Insert default data only if it doesn't exist
            $this->insertDefaultRoles();
            $this->insertDefaultAdmin();
            $this->insertDefaultSettings();
            
            $this->db->getConnection()->commit();
            
            return ['success' => true, 'message' => 'Database initialized successfully'];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => 'Database initialization failed: ' . $e->getMessage()];
        }
    }

    private function createRolesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            permissions TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role_id INTEGER NOT NULL,
            restaurant_id INTEGER,
            branch_id INTEGER,
            status INTEGER DEFAULT 1,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id),
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
            FOREIGN KEY (branch_id) REFERENCES branches(id)
        )";
        
        $this->db->query($sql);
    }

    private function createRestaurantsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS restaurants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            logo VARCHAR(255),
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(255),
            website VARCHAR(255),
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    private function createBranchesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS branches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            restaurant_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(255),
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
        )";
        
        $this->db->query($sql);
    }

    private function createMenuCategoriesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS menu_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            restaurant_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            display_order INTEGER DEFAULT 0,
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
        )";
        
        $this->db->query($sql);
    }

    private function createMenuItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS menu_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            restaurant_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            image VARCHAR(255),
            ingredients TEXT,
            allergens TEXT,
            spicy_level INTEGER DEFAULT 0,
            vegetarian INTEGER DEFAULT 0,
            vegan INTEGER DEFAULT 0,
            gluten_free INTEGER DEFAULT 0,
            available INTEGER DEFAULT 1,
            featured INTEGER DEFAULT 0,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
            FOREIGN KEY (category_id) REFERENCES menu_categories(id)
        )";
        
        $this->db->query($sql);
    }

    private function createOrdersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            restaurant_id INTEGER NOT NULL,
            branch_id INTEGER NOT NULL,
            table_number VARCHAR(10),
            customer_name VARCHAR(255),
            customer_phone VARCHAR(20),
            customer_email VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            total_amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(50),
            payment_status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
            FOREIGN KEY (branch_id) REFERENCES branches(id)
        )";
        
        $this->db->query($sql);
    }

    private function createOrderItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            menu_item_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
        )";
        
        $this->db->query($sql);
    }

    private function createLoginAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL,
            success INTEGER NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    private function createModulesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS modules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            version VARCHAR(20) NOT NULL,
            description TEXT,
            author VARCHAR(255),
            status INTEGER DEFAULT 0,
            settings TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key VARCHAR(255) NOT NULL UNIQUE,
            value TEXT,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    private function createIndexes() {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_orders_restaurant_id ON orders(restaurant_id)",
            "CREATE INDEX IF NOT EXISTS idx_orders_branch_id ON orders(branch_id)",
            "CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)",
            "CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_restaurant_id ON menu_items(restaurant_id)",
            "CREATE INDEX IF NOT EXISTS idx_users_restaurant_id ON users(restaurant_id)"
        ];

        foreach ($indexes as $sql) {
            $this->db->query($sql);
        }
    }

    private function insertDefaultRoles() {
        $roles = [
            ['name' => 'super_admin', 'description' => 'Super Administrator with full access', 'permissions' => json_encode(['all'])],
            ['name' => 'restaurant_owner', 'description' => 'Restaurant Owner', 'permissions' => json_encode(['manage_restaurant', 'manage_branches', 'manage_menu', 'manage_orders', 'manage_staff', 'view_reports'])],
            ['name' => 'manager', 'description' => 'Restaurant Manager', 'permissions' => json_encode(['manage_branches', 'manage_menu', 'manage_orders', 'manage_staff', 'view_reports'])],
            ['name' => 'branch_manager', 'description' => 'Branch Manager', 'permissions' => json_encode(['manage_menu', 'manage_orders', 'manage_staff', 'view_reports'])],
            ['name' => 'chef', 'description' => 'Chef', 'permissions' => json_encode(['view_menu', 'manage_orders', 'view_reports'])],
            ['name' => 'waiter', 'description' => 'Waiter', 'permissions' => json_encode(['view_menu', 'manage_orders'])],
            ['name' => 'restaurant_staff', 'description' => 'Restaurant Staff', 'permissions' => json_encode(['view_menu', 'view_orders'])]
        ];

        foreach ($roles as $role) {
            $existing = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$role['name']]);
            if (!$existing) {
                $this->db->insert('roles', $role);
            }
        }
    }

    private function insertDefaultAdmin() {
        $existing = $this->db->fetch("SELECT id FROM users WHERE email = ?", ['admin@qrmenu.com']);
        if (!$existing) {
            $adminRole = $this->db->fetch("SELECT id FROM roles WHERE name = 'super_admin'");
            if ($adminRole) {
                $this->db->insert('users', [
                    'email' => 'admin@qrmenu.com',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'name' => 'Super Admin',
                    'role_id' => $adminRole['id'],
                    'status' => 1
                ]);
            }
        }
    }

    private function insertDefaultSettings() {
        $settings = [
            ['key' => 'site_name', 'value' => 'QR Menu System', 'description' => 'Website name'],
            ['key' => 'site_description', 'value' => 'Digital QR Menu System', 'description' => 'Website description'],
            ['key' => 'currency', 'value' => 'BDT', 'description' => 'Default currency'],
            ['key' => 'currency_symbol', 'value' => '৳', 'description' => 'Currency symbol'],
            ['key' => 'tax_rate', 'value' => '15', 'description' => 'Default tax rate'],
            ['key' => 'service_charge', 'value' => '10', 'description' => 'Default service charge'],
            ['key' => 'qr_expiry_time', 'value' => '24', 'description' => 'QR code expiry time in hours'],
            ['key' => 'max_order_items', 'value' => '50', 'description' => 'Maximum items per order'],
            ['key' => 'enable_notifications', 'value' => '1', 'description' => 'Enable notifications'],
            ['key' => 'maintenance_mode', 'value' => '0', 'description' => 'Maintenance mode']
        ];

        foreach ($settings as $setting) {
            $existing = $this->db->fetch("SELECT id FROM settings WHERE key = ?", [$setting['key']]);
            if (!$existing) {
                $this->db->insert('settings', $setting);
            }
        }
    }
}
?>