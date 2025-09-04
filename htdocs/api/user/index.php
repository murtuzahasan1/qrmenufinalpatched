<?php
require_once __DIR__ . '/../bootstrap.php';

class UserAPI {
    // ... (rest of the file is unchanged)
    private $utils;
    private $db;

    public function __construct() {
        $this->utils = new Utils();
        $this->db = Database::getInstance();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = $_GET['endpoint'] ?? '';

        switch ($endpoint) {
            case 'restaurants':
                $this->handleRestaurants($method);
                break;
            case 'branches':
                $this->handleBranches($method);
                break;
            case 'menu':
                $this->handleMenu($method);
                break;
            case 'orders':
                $this->handleOrders($method);
                break;
            case 'search':
                $this->handleSearch($method);
                break;
            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid endpoint'], 400);
        }
    }

    private function handleRestaurants($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $restaurants = $this->db->fetchAll(
            "SELECT id, name, description, logo, address, phone, email, website 
             FROM restaurants 
             WHERE status = 1 
             ORDER BY name"
        );

        $this->utils->jsonResponse(['success' => true, 'data' => $restaurants]);
    }

    private function handleBranches($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $restaurantId = $_GET['restaurant_id'] ?? null;
        
        if (!$restaurantId) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Restaurant ID required'], 400);
        }

        $branches = $this->db->fetchAll(
            "SELECT id, restaurant_id, name, description, address, phone, email, latitude, longitude 
             FROM branches 
             WHERE restaurant_id = ? AND status = 1 
             ORDER BY name",
            [$restaurantId]
        );

        $this->utils->jsonResponse(['success' => true, 'data' => $branches]);
    }

    private function handleMenu($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $restaurantId = $_GET['restaurant_id'] ?? null;
        $branchId = $_GET['branch_id'] ?? null;
        
        if (!$restaurantId) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Restaurant ID required'], 400);
        }

        // Get categories
        $categories = $this->db->fetchAll(
            "SELECT id, name, description, image, display_order 
             FROM menu_categories 
             WHERE restaurant_id = ? AND status = 1 
             ORDER BY display_order, name",
            [$restaurantId]
        );

        // Get menu items
        $items = $this->db->fetchAll(
            "SELECT id, category_id, name, description, price, image, ingredients, allergens, 
                    spicy_level, vegetarian, vegan, gluten_free, available, featured, display_order 
             FROM menu_items 
             WHERE restaurant_id = ? AND available = 1 
             ORDER BY display_order, name",
            [$restaurantId]
        );

        // Group items by category
        $menu = [];
        foreach ($categories as $category) {
            $categoryItems = array_filter($items, function($item) use ($category) {
                return $item['category_id'] == $category['id'];
            });
            
            $menu[] = [
                'category' => $category,
                'items' => array_values($categoryItems)
            ];
        }

        // Get restaurant info
        $restaurant = $this->db->fetch(
            "SELECT id, name, description, logo, address, phone, email 
             FROM restaurants 
             WHERE id = ? AND status = 1",
            [$restaurantId]
        );

        // Get branch info if branch_id is provided
        $branch = null;
        if ($branchId) {
            $branch = $this->db->fetch(
                "SELECT id, name, description, address, phone, email 
                 FROM branches 
                 WHERE id = ? AND status = 1",
                [$branchId]
            );
        }

        $this->utils->jsonResponse([
            'success' => true,
            'data' => [
                'restaurant' => $restaurant,
                'branch' => $branch,
                'menu' => $menu
            ]
        ]);
    }

    private function handleOrders($method) {
        switch ($method) {
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
                }

                $required = ['restaurant_id', 'branch_id', 'items'];
                $errors = $this->utils->validateRequired($data, $required);
                
                if (!empty($errors)) {
                    $this->utils->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                }

                // Validate items
                if (empty($data['items']) || !is_array($data['items'])) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Items must be an array'], 400);
                }

                // Calculate total amount
                $totalAmount = 0;
                $orderItems = [];

                foreach ($data['items'] as $item) {
                    if (!isset($item['menu_item_id']) || !isset($item['quantity'])) {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Each item must have menu_item_id and quantity'], 400);
                    }

                    // Get menu item details
                    $menuItem = $this->db->fetch(
                        "SELECT price, name FROM menu_items WHERE id = ? AND available = 1",
                        [$item['menu_item_id']]
                    );

                    if (!$menuItem) {
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Menu item not found or unavailable'], 404);
                    }

                    $unitPrice = $menuItem['price'];
                    $quantity = max(1, intval($item['quantity']));
                    $totalPrice = $unitPrice * $quantity;

                    $orderItems[] = [
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'notes' => $this->utils->sanitizeInput($item['notes'] ?? '')
                    ];

                    $totalAmount += $totalPrice;
                }

                // Create order
                $orderData = [
                    'restaurant_id' => $data['restaurant_id'],
                    'branch_id' => $data['branch_id'],
                    'table_number' => $this->utils->sanitizeInput($data['table_number'] ?? ''),
                    'customer_name' => $this->utils->sanitizeInput($data['customer_name'] ?? ''),
                    'customer_phone' => $this->utils->sanitizeInput($data['customer_phone'] ?? ''),
                    'customer_email' => $this->utils->sanitizeInput($data['customer_email'] ?? ''),
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                    'payment_method' => $this->utils->sanitizeInput($data['payment_method'] ?? 'cash'),
                    'payment_status' => 'pending',
                    'notes' => $this->utils->sanitizeInput($data['notes'] ?? '')
                ];

                try {
                    $this->db->getConnection()->beginTransaction();
                    
                    // Insert order
                    $orderId = $this->db->insert('orders', $orderData);
                    
                    if (!$orderId) {
                        throw new Exception('Failed to create order');
                    }

                    // Insert order items
                    foreach ($orderItems as $item) {
                        $item['order_id'] = $orderId;
                        $this->db->insert('order_items', $item);
                    }

                    $this->db->getConnection()->commit();

                    $this->utils->jsonResponse([
                        'success' => true,
                        'message' => 'Order created successfully',
                        'order_id' => $orderId,
                        'total_amount' => $totalAmount
                    ]);

                } catch (Exception $e) {
                    $this->db->getConnection()->rollback();
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
                }
                break;

            case 'GET':
                $orderId = $_GET['order_id'] ?? null;
                
                if (!$orderId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Order ID required'], 400);
                }

                $order = $this->db->fetch(
                    "SELECT o.*, b.name as branch_name, r.name as restaurant_name 
                     FROM orders o 
                     JOIN branches b ON o.branch_id = b.id 
                     JOIN restaurants r ON o.restaurant_id = r.id 
                     WHERE o.id = ?",
                    [$orderId]
                );

                if (!$order) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
                }

                // Get order items
                $order['items'] = $this->db->fetchAll(
                    "SELECT oi.*, mi.name as item_name, mi.description as item_description 
                     FROM order_items oi 
                     JOIN menu_items mi ON oi.menu_item_id = mi.id 
                     WHERE oi.order_id = ?",
                    [$orderId]
                );

                $this->utils->jsonResponse(['success' => true, 'data' => $order]);
                break;

            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }

    private function handleSearch($method) {
        if ($method !== 'GET') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $restaurantId = $_GET['restaurant_id'] ?? null;
        $query = $_GET['q'] ?? '';
        
        if (!$restaurantId) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Restaurant ID required'], 400);
        }

        if (empty($query)) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Search query required'], 400);
        }

        $searchTerm = '%' . $this->utils->sanitizeInput($query) . '%';

        // Search in menu items
        $items = $this->db->fetchAll(
            "SELECT mi.*, mc.name as category_name 
             FROM menu_items mi 
             JOIN menu_categories mc ON mi.category_id = mc.id 
             WHERE mi.restaurant_id = ? AND mi.available = 1 
             AND (mi.name LIKE ? OR mi.description LIKE ? OR mi.ingredients LIKE ?) 
             ORDER BY mi.name",
            [$restaurantId, $searchTerm, $searchTerm, $searchTerm]
        );

        $this->utils->jsonResponse(['success' => true, 'data' => $items]);
    }
}

// Handle the request
$userAPI = new UserAPI();
$userAPI->handleRequest();
?>