<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

class MenuAPI {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance();
        
        // Require authentication for all API endpoints
        $this->auth->requireAuth();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $this->handleList($method);
                break;
            case 'get_price':
                $this->handleGetPrice($method);
                break;
            case 'get':
                $this->handleGet($method);
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function handleList($method) {
        if ($method === 'GET') {
            $restaurantId = $_GET['restaurant_id'] ?? null;
            $categoryId = $_GET['category_id'] ?? null;
            $type = $_GET['type'] ?? 'items';
            
            if ($type === 'categories') {
                $categories = $this->getMenuCategories($restaurantId);
                $this->jsonResponse(['success' => true, 'data' => $categories]);
            } else {
                $items = $this->getMenuItems($restaurantId, $categoryId);
                $this->jsonResponse(['success' => true, 'data' => $items]);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function handleGetPrice($method) {
        if ($method === 'GET') {
            $itemId = $_GET['id'] ?? null;
            
            if (!$itemId) {
                $this->jsonResponse(['success' => false, 'message' => 'Menu item ID required'], 400);
            }
            
            $item = $this->getMenuItemPrice($itemId);
            
            if ($item) {
                $this->jsonResponse(['success' => true, 'price' => $item['price']]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Menu item not found'], 404);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function handleGet($method) {
        if ($method === 'GET') {
            $itemId = $_GET['id'] ?? null;
            
            if (!$itemId) {
                $this->jsonResponse(['success' => false, 'message' => 'Menu item ID required'], 400);
            }
            
            $item = $this->getMenuItem($itemId);
            
            if ($item) {
                $this->jsonResponse(['success' => true, 'data' => $item]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Menu item not found'], 404);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function getMenuCategories($restaurantId = null) {
        $sql = "SELECT * FROM menu_categories";
        $params = [];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($restaurantId) {
            $sql .= " WHERE restaurant_id = ?";
            $params[] = $restaurantId;
        } elseif ($currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE restaurant_id = ?";
            $params[] = $currentUser['restaurant_id'];
        }
        
        $sql .= " ORDER BY display_order, name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getMenuItems($restaurantId = null, $categoryId = null) {
        $sql = "SELECT mi.*, mc.name as category_name 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id";
        
        $params = [];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($restaurantId) {
            $sql .= " WHERE mi.restaurant_id = ?";
            $params[] = $restaurantId;
        } elseif ($currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE mi.restaurant_id = ?";
            $params[] = $currentUser['restaurant_id'];
        }
        
        if ($categoryId) {
            $sql .= " AND mi.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY mi.display_order, mi.name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getMenuItemPrice($itemId) {
        $sql = "SELECT price FROM menu_items WHERE id = ?";
        $params = [$itemId];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['role'] !== 'super_admin') {
            $sql .= " AND restaurant_id = ?";
            $params[] = $currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function getMenuItem($itemId) {
        $sql = "SELECT mi.*, mc.name as category_name 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id 
                WHERE mi.id = ?";
        
        $params = [$itemId];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['role'] !== 'super_admin') {
            $sql .= " AND mi.restaurant_id = ?";
            $params[] = $currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Handle the request
$api = new MenuAPI();
$api->handleRequest();
?>