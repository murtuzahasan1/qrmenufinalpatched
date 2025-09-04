<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

class BranchesAPI {
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
        $endpoint = $_GET['endpoint'] ?? 'list';
        
        switch ($endpoint) {
            case 'list':
                $this->handleList($method);
                break;
            case 'get':
                $this->handleGet($method);
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Invalid endpoint'], 400);
        }
    }
    
    private function handleList($method) {
        if ($method === 'GET') {
            $restaurantId = $_GET['restaurant_id'] ?? null;
            $branches = $this->getBranches($restaurantId);
            $this->jsonResponse(['success' => true, 'data' => $branches]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function handleGet($method) {
        if ($method === 'GET') {
            $branchId = $_GET['id'] ?? null;
            
            if (!$branchId) {
                $this->jsonResponse(['success' => false, 'message' => 'Branch ID required'], 400);
            }
            
            $branch = $this->getBranch($branchId);
            
            if ($branch) {
                $this->jsonResponse(['success' => true, 'data' => $branch]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Branch not found'], 404);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function getBranches($restaurantId = null) {
        $sql = "SELECT b.*, r.name as restaurant_name 
                FROM branches b 
                JOIN restaurants r ON b.restaurant_id = r.id";
        
        $params = [];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE b.restaurant_id = ?";
            $params[] = $currentUser['restaurant_id'];
        } else if ($restaurantId) {
            $sql .= " WHERE b.restaurant_id = ?";
            $params[] = $restaurantId;
        }
        
        $sql .= " ORDER BY b.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getBranch($branchId) {
        $sql = "SELECT b.*, r.name as restaurant_name 
                FROM branches b 
                JOIN restaurants r ON b.restaurant_id = r.id 
                WHERE b.id = ?";
        
        $params = [$branchId];
        
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['role'] !== 'super_admin') {
            $sql .= " AND b.restaurant_id = ?";
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
$api = new BranchesAPI();
$api->handleRequest();
?>