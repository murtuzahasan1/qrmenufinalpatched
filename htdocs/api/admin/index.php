<?php
// /api/admin/index.php

// Load the central bootstrap file
require_once __DIR__ . '/../bootstrap.php';

// Now all core classes and session configs are loaded correctly.
// We can proceed with the API logic for this endpoint.
require_once APP_ROOT . '/includes/module_manager.php'; // This is specific to the admin API

class AdminAPI {
    private $auth;
    private $utils;
    private $db;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->utils = new Utils();
        $this->db = Database::getInstance();
    }

    public function handleRequest() {
        // Require authentication for all admin endpoints
        $this->auth->requireAuth();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = $_GET['endpoint'] ?? '';

        switch ($endpoint) {
            case 'restaurants':
                $this->handleRestaurants($method);
                break;
            case 'branches':
                $this->handleBranches($method);
                break;
            case 'users':
                $this->handleUsers($method);
                break;
            case 'menu':
                $this->handleMenu($method);
                break;
            case 'orders':
                $this->handleOrders($method);
                break;
            case 'dashboard':
                $this->handleDashboard($method);
                break;
            case 'settings':
                $this->handleSettings($method);
                break;
            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid endpoint'], 400);
        }
    }

    private function handleRestaurants($method) {
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($method) {
            case 'GET':
                if ($currentUser['role'] === 'super_admin') {
                    $restaurants = $this->db->fetchAll("SELECT * FROM restaurants ORDER BY created_at DESC");
                } else {
                    $restaurants = $this->db->fetchAll("SELECT * FROM restaurants WHERE id = ? ORDER BY created_at DESC", [$currentUser['restaurant_id']]);
                }
                $this->utils->jsonResponse(['success' => true, 'data' => $restaurants]);
                break;
                
            case 'POST':
                $this->auth->requirePermission('manage_restaurant');
                $data = json_decode(file_get_contents('php://input'), true);
                
                $required = ['name', 'address', 'phone'];
                $errors = $this->utils->validateRequired($data, $required);
                
                if (!empty($errors)) {
                    $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                }

                $restaurantData = [
                    'name' => $this->utils->sanitizeInput($data['name']),
                    'description' => $this->utils->sanitizeInput($data['description'] ?? ''),
                    'address' => $this->utils->sanitizeInput($data['address']),
                    'phone' => $this->utils->sanitizeInput($data['phone']),
                    'email' => $this->utils->sanitizeInput($data['email'] ?? ''),
                    'website' => $this->utils->sanitizeInput($data['website'] ?? ''),
                    'status' => $data['status'] ?? 1
                ];

                $restaurantId = $this->db->insert('restaurants', $restaurantData);
                
                if ($restaurantId) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'Restaurant created successfully', 'id' => $restaurantId]);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create restaurant'], 500);
                }
                break;
                
            case 'PUT':
                $this->auth->requirePermission('manage_restaurant');
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $_GET['id'] ?? null;
                
                if (!$id) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Restaurant ID required'], 400);
                }

                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $id) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $updateData = [];
                foreach ($data as $key => $value) {
                    if (in_array($key, ['name', 'description', 'address', 'phone', 'email', 'website', 'status'])) {
                        $updateData[$key] = $this->utils->sanitizeInput($value);
                    }
                }

                $updated = $this->db->update('restaurants', $updateData, 'id = ?', [$id]);
                
                if ($updated !== false) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'Restaurant updated successfully']);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to update restaurant'], 500);
                }
                break;
                
            case 'DELETE':
                $this->auth->requirePermission('manage_restaurant');
                $id = $_GET['id'] ?? null;
                
                if (!$id) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Restaurant ID required'], 400);
                }

                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $id) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $deleted = $this->db->delete('restaurants', 'id = ?', [$id]);
                
                if ($deleted) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'Restaurant deleted successfully']);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to delete restaurant'], 500);
                }
                break;
        }
    }

    private function handleBranches($method) {
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($method) {
            case 'GET':
                $restaurantId = $_GET['restaurant_id'] ?? null;
                
                if ($currentUser['role'] === 'super_admin') {
                    $sql = "SELECT b.*, r.name as restaurant_name FROM branches b JOIN restaurants r ON b.restaurant_id = r.id";
                    $params = [];
                } else {
                    $sql = "SELECT b.*, r.name as restaurant_name FROM branches b JOIN restaurants r ON b.restaurant_id = r.id WHERE b.restaurant_id = ?";
                    $params = [$currentUser['restaurant_id']];
                }
                
                if ($restaurantId) {
                    $sql .= " AND b.restaurant_id = ?";
                    $params[] = $restaurantId;
                }
                
                $sql .= " ORDER BY b.created_at DESC";
                $branches = $this->db->fetchAll($sql, $params);
                $this->utils->jsonResponse(['success' => true, 'data' => $branches]);
                break;
                
            case 'POST':
                $this->auth->requirePermission('manage_branches');
                $data = json_decode(file_get_contents('php://input'), true);
                
                $required = ['restaurant_id', 'name', 'address', 'phone'];
                $errors = $this->utils->validateRequired($data, $required);
                
                if (!empty($errors)) {
                    $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                }

                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $data['restaurant_id']) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $branchData = [
                    'restaurant_id' => $data['restaurant_id'],
                    'name' => $this->utils->sanitizeInput($data['name']),
                    'description' => $this->utils->sanitizeInput($data['description'] ?? ''),
                    'address' => $this->utils->sanitizeInput($data['address']),
                    'phone' => $this->utils->sanitizeInput($data['phone']),
                    'email' => $this->utils->sanitizeInput($data['email'] ?? ''),
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'status' => $data['status'] ?? 1
                ];

                $branchId = $this->db->insert('branches', $branchData);
                
                if ($branchId) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'Branch created successfully', 'id' => $branchId]);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create branch'], 500);
                }
                break;
        }
    }

    private function handleUsers($method) {
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($method) {
            case 'GET':
                $restaurantId = $_GET['restaurant_id'] ?? null;
                $branchId = $_GET['branch_id'] ?? null;
                
                $sql = "SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1";
                $params = [];
                
                if ($currentUser['role'] !== 'super_admin') {
                    $sql .= " AND u.restaurant_id = ?";
                    $params[] = $currentUser['restaurant_id'];
                }
                
                if ($restaurantId) {
                    $sql .= " AND u.restaurant_id = ?";
                    $params[] = $restaurantId;
                }
                
                if ($branchId) {
                    $sql .= " AND u.branch_id = ?";
                    $params[] = $branchId;
                }
                
                $sql .= " ORDER BY u.created_at DESC";
                $users = $this->db->fetchAll($sql, $params);
                $this->utils->jsonResponse(['success' => true, 'data' => $users]);
                break;
                
            case 'POST':
                $this->auth->requirePermission('manage_staff');
                $data = json_decode(file_get_contents('php://input'), true);
                
                $required = ['name', 'email', 'password', 'role'];
                $errors = $this->utils->validateRequired($data, $required);
                
                if (!empty($errors)) {
                    $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                }

                $roleData = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$data['role']]);
                if (!$roleData) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid role'], 400);
                }

                $userData = [
                    'name' => $this->utils->sanitizeInput($data['name']),
                    'email' => $this->utils->sanitizeInput($data['email']),
                    'password' => $this->auth->hashPassword($data['password']),
                    'role_id' => $roleData['id'],
                    'phone' => $this->utils->sanitizeInput($data['phone'] ?? ''),
                    'restaurant_id' => $data['restaurant_id'] ?? $currentUser['restaurant_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'status' => $data['status'] ?? 1
                ];

                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $userData['restaurant_id']) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $userId = $this->db->insert('users', $userData);
                
                if ($userId) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'User created successfully', 'id' => $userId]);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create user'], 500);
                }
                break;
        }
    }

    private function handleMenu($method) {
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($method) {
            case 'GET':
                $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];
                $type = $_GET['type'] ?? 'categories';
                
                if ($type === 'categories') {
                    $categories = $this->db->fetchAll(
                        "SELECT * FROM menu_categories WHERE restaurant_id = ? ORDER BY display_order, name",
                        [$restaurantId]
                    );
                    $this->utils->jsonResponse(['success' => true, 'data' => $categories]);
                } else {
                    $categoryId = $_GET['category_id'] ?? null;
                    $sql = "SELECT * FROM menu_items WHERE restaurant_id = ?";
                    $params = [$restaurantId];
                    
                    if ($categoryId) {
                        $sql .= " AND category_id = ?";
                        $params[] = $categoryId;
                    }
                    
                    $sql .= " ORDER BY display_order, name";
                    $items = $this->db->fetchAll($sql, $params);
                    $this->utils->jsonResponse(['success' => true, 'data' => $items]);
                }
                break;
                
            case 'POST':
                $this->auth->requirePermission('manage_menu');
                $data = json_decode(file_get_contents('php://input'), true);
                $type = $_GET['type'] ?? 'category';
                
                if ($type === 'category') {
                    $required = ['restaurant_id', 'name'];
                    $errors = $this->utils->validateRequired($data, $required);
                    
                    if (!empty($errors)) {
                        $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                    }

                    if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $data['restaurant_id']) {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                    }

                    $categoryData = [
                        'restaurant_id' => $data['restaurant_id'],
                        'name' => $this->utils->sanitizeInput($data['name']),
                        'description' => $this->utils->sanitizeInput($data['description'] ?? ''),
                        'display_order' => $data['display_order'] ?? 0,
                        'status' => $data['status'] ?? 1
                    ];

                    $categoryId = $this->db->insert('menu_categories', $categoryData);
                    
                    if ($categoryId) {
                        $this->utils->jsonResponse(['success' => true, 'message' => 'Category created successfully', 'id' => $categoryId]);
                    } else {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create category'], 500);
                    }
                } else {
                    $required = ['restaurant_id', 'category_id', 'name', 'price'];
                    $errors = $this->utils->validateRequired($data, $required);
                    
                    if (!empty($errors)) {
                        $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                    }

                    if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $data['restaurant_id']) {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                    }

                    $itemData = [
                        'restaurant_id' => $data['restaurant_id'],
                        'category_id' => $data['category_id'],
                        'name' => $this->utils->sanitizeInput($data['name']),
                        'description' => $this->utils->sanitizeInput($data['description'] ?? ''),
                        'price' => $data['price'],
                        'ingredients' => $this->utils->sanitizeInput($data['ingredients'] ?? ''),
                        'allergens' => $this->utils->sanitizeInput($data['allergens'] ?? ''),
                        'spicy_level' => $data['spicy_level'] ?? 0,
                        'vegetarian' => $data['vegetarian'] ?? 0,
                        'vegan' => $data['vegan'] ?? 0,
                        'gluten_free' => $data['gluten_free'] ?? 0,
                        'available' => $data['available'] ?? 1,
                        'featured' => $data['featured'] ?? 0,
                        'display_order' => $data['display_order'] ?? 0
                    ];

                    $itemId = $this->db->insert('menu_items', $itemData);
                    
                    if ($itemId) {
                        $this->utils->jsonResponse(['success' => true, 'message' => 'Menu item created successfully', 'id' => $itemId]);
                    } else {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create menu item'], 500);
                    }
                }
                break;
        }
    }

    private function handleOrders($method) {
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($method) {
            case 'GET':
                $restaurantId = $_GET['restaurant_id'] ?? null;
                $branchId = $_GET['branch_id'] ?? null;
                $status = $_GET['status'] ?? null;
                
                $sql = "SELECT o.*, b.name as branch_name FROM orders o JOIN branches b ON o.branch_id = b.id";
                $params = [];
                $whereConditions = [];

                if ($currentUser['role'] !== 'super_admin') {
                    $whereConditions[] = "o.restaurant_id = ?";
                    $params[] = $currentUser['restaurant_id'];
                } elseif ($restaurantId) {
                    $whereConditions[] = "o.restaurant_id = ?";
                    $params[] = $restaurantId;
                }
                
                if ($branchId) {
                    $whereConditions[] = "o.branch_id = ?";
                    $params[] = $branchId;
                }
                
                if ($status) {
                    $whereConditions[] = "o.status = ?";
                    $params[] = $status;
                }

                if (!empty($whereConditions)) {
                    $sql .= " WHERE " . implode(' AND ', $whereConditions);
                }
                
                $sql .= " ORDER BY o.created_at DESC";
                $orders = $this->db->fetchAll($sql, $params);
                
                foreach ($orders as &$order) {
                    $order['items'] = $this->db->fetchAll(
                        "SELECT oi.*, mi.name as item_name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?",
                        [$order['id']]
                    );
                }
                
                $this->utils->jsonResponse(['success' => true, 'data' => $orders]);
                break;
            
            case 'POST': // Allow POST for updates to avoid hosting issues
            case 'PUT':
                $this->auth->requirePermission('manage_orders');
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $_GET['id'] ?? null;
                
                if (!$id) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Order ID required for update'], 400);
                }

                $order = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$id]);
                if (!$order) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
                }
                
                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $order['restaurant_id']) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $updateData = [];
                if (isset($data['status'])) {
                    $updateData['status'] = $this->utils->sanitizeInput($data['status']);
                }
                if (isset($data['payment_status'])) {
                    $updateData['payment_status'] = $this->utils->sanitizeInput($data['payment_status']);
                }

                if (empty($updateData)) {
                     $this->utils->jsonResponse(['success' => false, 'message' => 'No data provided for update'], 400);
                }

                $updated = $this->db->update('orders', $updateData, 'id = ?', [$id]);
                
                if ($updated !== false) {
                    $this->utils->jsonResponse(['success' => true, 'message' => 'Order updated successfully']);
                } else {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to update order'], 500);
                }
                break;
        }
    }

    private function handleDashboard($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $currentUser = $this->auth->getCurrentUser();
        $restaurantId = $currentUser['restaurant_id'];

        $today = date('Y-m-d');
        $thisMonth = date('Y-m-01');
        
        $stats = [
            'today_orders' => 0,
            'today_revenue' => 0,
            'month_orders' => 0,
            'month_revenue' => 0,
            'total_orders' => 0,
            'total_revenue' => 0
        ];

        $whereConditions = [];
        $params = [];

        if ($currentUser['role'] !== 'super_admin' && $restaurantId) {
            $whereConditions[] = "restaurant_id = ?";
            $params[] = $restaurantId;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(' AND ', $whereConditions) : "";
        
        $todayParams = $params;
        $todayWhereClause = $whereClause;
        if (empty($whereConditions)) {
            $todayWhereClause = "WHERE DATE(created_at) = ?";
        } else {
            $todayWhereClause .= " AND DATE(created_at) = ?";
        }
        $todayParams[] = $today;

        $todayOrders = $this->db->fetchAll(
            "SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders $todayWhereClause",
            $todayParams
        );
        
        if ($todayOrders) {
            $stats['today_orders'] = $todayOrders[0]['count'];
            $stats['today_revenue'] = $todayOrders[0]['revenue'] ?? 0;
        }

        $monthParams = $params;
        $monthWhereClause = $whereClause;
        if (empty($whereConditions)) {
            $monthWhereClause = "WHERE created_at >= ?";
        } else {
            $monthWhereClause .= " AND created_at >= ?";
        }
        $monthParams[] = $thisMonth;

        $monthOrders = $this->db->fetchAll(
            "SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders $monthWhereClause",
            $monthParams
        );
        
        if ($monthOrders) {
            $stats['month_orders'] = $monthOrders[0]['count'];
            $stats['month_revenue'] = $monthOrders[0]['revenue'] ?? 0;
        }

        $totalOrders = $this->db->fetchAll(
            "SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders $whereClause",
            $params
        );
        
        if ($totalOrders) {
            $stats['total_orders'] = $totalOrders[0]['count'];
            $stats['total_revenue'] = $totalOrders[0]['revenue'] ?? 0;
        }

        $recentOrdersSql = "SELECT o.*, b.name as branch_name FROM orders o JOIN branches b ON o.branch_id = b.id " . $whereClause . " ORDER BY o.created_at DESC LIMIT 10";
        $recentOrders = $this->db->fetchAll($recentOrdersSql, $params);

        $popularItemsSql = "SELECT mi.name, SUM(oi.quantity) as total_quantity FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id JOIN orders o ON oi.order_id = o.id " . $whereClause . " GROUP BY mi.id, mi.name ORDER BY total_quantity DESC LIMIT 10";
        $popularItems = $this->db->fetchAll($popularItemsSql, $params);

        $this->utils->jsonResponse([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_orders' => $recentOrders,
                'popular_items' => $popularItems
            ]
        ]);
    }

    private function handleSettings($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $settings = $this->db->fetchAll("SELECT * FROM settings ORDER BY key");
        $settingsArray = [];
        
        foreach ($settings as $setting) {
            $settingsArray[$setting['key']] = $setting['value'];
        }

        $this->utils->jsonResponse(['success' => true, 'data' => $settingsArray]);
    }
}

$adminAPI = new AdminAPI();
$adminAPI->handleRequest();
?>