<?php
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/utils.php';

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class BaseDashboard {
    protected $db;
    protected $auth;
    protected $utils;
    protected $currentUser;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->utils = new Utils();
        
        // Debug: Log session status
        error_log("BaseDashboard Constructor - Session Status: " . session_status());
        error_log("BaseDashboard Constructor - Session ID: " . session_id());
        error_log("BaseDashboard Constructor - Session Data: " . print_r($_SESSION, true));
        
        $this->currentUser = $this->auth->getCurrentUser();
        
        // Debug: Let's see what's happening
        if (!$this->currentUser) {
            error_log("BaseDashboard Constructor - No current user found");
            $this->jsonResponse(['success' => false, 'message' => 'Authentication required - No current user found'], 401);
        }
        
        // Debug: Log the current user data
        error_log("BaseDashboard Constructor - Current User: " . print_r($this->currentUser, true));
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function requirePermission($permission) {
        if ($this->currentUser['role'] !== 'super_admin' && !in_array($permission, $this->currentUser['permissions'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
        }
    }
    
    protected function requireRole($role) {
        // Debug: Log the role check
        error_log("BaseDashboard requireRole - Checking role: {$role}, Current user role: " . ($this->currentUser['role'] ?? 'not set'));
        
        // Handle different role name formats - be more flexible
        $currentUserRole = $this->currentUser['role'];
        
        // Convert role parameter to match database format (underscore to no underscore)
        $roleMapping = [
            'superadmin' => ['super_admin'],
            'super_admin' => ['super_admin'],
            'owner' => ['restaurant_owner'],
            'restaurant_owner' => ['restaurant_owner'],
            'manager' => ['manager'],
            'branchmanager' => ['branch_manager'],
            'branch_manager' => ['branch_manager'],
            'chef' => ['chef'],
            'waiter' => ['waiter'],
            'staff' => ['restaurant_staff'],
            'restaurant_staff' => ['restaurant_staff']
        ];
        
        $isSuperAdmin = $currentUserRole === 'super_admin';
        $hasRequiredRole = false;
        
        // Check if current user role matches the required role
        if (isset($roleMapping[$role])) {
            $hasRequiredRole = in_array($currentUserRole, $roleMapping[$role]);
        }
        
        // Also check the reverse - if required role is in the current role's allowed roles
        if (!$hasRequiredRole && isset($roleMapping[$currentUserRole])) {
            $hasRequiredRole = in_array($role, $roleMapping[$currentUserRole]);
        }
        
        // Special case: super_admin can access everything
        if ($currentUserRole === 'super_admin') {
            $isSuperAdmin = true;
            $hasRequiredRole = true;
        }
        
        if (!$isSuperAdmin && !$hasRequiredRole) {
            error_log("BaseDashboard requireRole - Access denied. User role: {$currentUserRole}, Required: {$role}");
            $this->jsonResponse(['success' => false, 'message' => 'Insufficient role permissions. User role: ' . $currentUserRole . ', Required: ' . $role], 403);
        }
        
        error_log("BaseDashboard requireRole - Access granted for role: {$currentUserRole}");
    }
    
    protected function getDashboardStats() {
        $stats = [];
        
        // Handle different role name formats
        $currentUserRole = $this->currentUser['role'];
        $roleMapping = [
            'super_admin' => ['super_admin', 'superadmin'],
            'restaurant_owner' => ['restaurant_owner', 'owner'],
            'manager' => ['manager'],
            'branch_manager' => ['branch_manager', 'branchmanager'],
            'chef' => ['chef'],
            'waiter' => ['waiter'],
            'restaurant_staff' => ['restaurant_staff', 'staff']
        ];
        
        $isSuperAdmin = in_array($currentUserRole, $roleMapping['super_admin']);
        
        // Basic stats available to all roles
        if ($isSuperAdmin) {
            $stats['restaurants'] = $this->db->fetch("SELECT COUNT(*) as count FROM restaurants")['count'];
            $stats['branches'] = $this->db->fetch("SELECT COUNT(*) as count FROM branches")['count'];
            $stats['users'] = $this->db->fetch("SELECT COUNT(*) as count FROM users")['count'];
            $stats['orders'] = $this->db->fetch("SELECT COUNT(*) as count FROM orders")['count'];
        } else {
            $restaurantId = $this->currentUser['restaurant_id'];
            $branchId = $this->currentUser['branch_id'];
            
            if ($restaurantId) {
                $stats['branches'] = $this->db->fetch("SELECT COUNT(*) as count FROM branches WHERE restaurant_id = ?", [$restaurantId])['count'];
                $stats['users'] = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE restaurant_id = ?", [$restaurantId])['count'];
                
                $orderQuery = "SELECT COUNT(*) as count FROM orders WHERE restaurant_id = ?";
                $orderParams = [$restaurantId];
                
                if ($branchId) {
                    $orderQuery .= " AND branch_id = ?";
                    $orderParams[] = $branchId;
                }
                
                $stats['orders'] = $this->db->fetch($orderQuery, $orderParams)['count'];
                
                // Today's orders
                $todayOrdersQuery = "SELECT COUNT(*) as count FROM orders WHERE restaurant_id = ? AND DATE(created_at) = DATE('now')";
                $todayOrderParams = [$restaurantId];
                
                if ($branchId) {
                    $todayOrdersQuery .= " AND branch_id = ?";
                    $todayOrderParams[] = $branchId;
                }
                
                $stats['today_orders'] = $this->db->fetch($todayOrdersQuery, $todayOrderParams)['count'];
                
                // Total revenue
                $revenueQuery = "SELECT SUM(total_amount) as total FROM orders WHERE restaurant_id = ? AND payment_status = 'paid'";
                $revenueParams = [$restaurantId];
                
                if ($branchId) {
                    $revenueQuery .= " AND branch_id = ?";
                    $revenueParams[] = $branchId;
                }
                
                $revenue = $this->db->fetch($revenueQuery, $revenueParams)['total'];
                $stats['revenue'] = $revenue ? $revenue : 0;
            }
        }
        
        return $stats;
    }
    
    protected function getRecentOrders($limit = 10) {
        $sql = "SELECT o.*, b.name as branch_name, r.name as restaurant_name 
                FROM orders o 
                LEFT JOIN branches b ON o.branch_id = b.id 
                LEFT JOIN restaurants r ON o.restaurant_id = r.id";
        
        $params = [];
        
        // Handle different role name formats
        $currentUserRole = $this->currentUser['role'];
        $roleMapping = [
            'super_admin' => ['super_admin', 'superadmin'],
            'restaurant_owner' => ['restaurant_owner', 'owner'],
            'manager' => ['manager'],
            'branch_manager' => ['branch_manager', 'branchmanager'],
            'chef' => ['chef'],
            'waiter' => ['waiter'],
            'restaurant_staff' => ['restaurant_staff', 'staff']
        ];
        
        $isSuperAdmin = in_array($currentUserRole, $roleMapping['super_admin']);
        
        if (!$isSuperAdmin) {
            $sql .= " WHERE o.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
            
            if ($this->currentUser['branch_id']) {
                $sql .= " AND o.branch_id = ?";
                $params[] = $this->currentUser['branch_id'];
            }
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    protected function getRestaurants() {
        // Handle different role name formats
        $currentUserRole = $this->currentUser['role'];
        $roleMapping = [
            'super_admin' => ['super_admin', 'superadmin'],
            'restaurant_owner' => ['restaurant_owner', 'owner'],
            'manager' => ['manager'],
            'branch_manager' => ['branch_manager', 'branchmanager'],
            'chef' => ['chef'],
            'waiter' => ['waiter'],
            'restaurant_staff' => ['restaurant_staff', 'staff']
        ];
        
        $isSuperAdmin = in_array($currentUserRole, $roleMapping['super_admin']);
        
        if ($isSuperAdmin) {
            return $this->db->fetchAll("SELECT * FROM restaurants ORDER BY created_at DESC");
        } else {
            return $this->db->fetchAll("SELECT * FROM restaurants WHERE id = ? ORDER BY created_at DESC", [$this->currentUser['restaurant_id']]);
        }
    }
    
    protected function getBranches($restaurantId = null) {
        if (!$restaurantId && $this->currentUser['restaurant_id']) {
            $restaurantId = $this->currentUser['restaurant_id'];
        }
        
        $sql = "SELECT b.*, r.name as restaurant_name 
                FROM branches b 
                JOIN restaurants r ON b.restaurant_id = r.id";
        
        $params = [];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE b.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
        } else if ($restaurantId) {
            $sql .= " WHERE b.restaurant_id = ?";
            $params[] = $restaurantId;
        }
        
        $sql .= " ORDER BY b.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    protected function getUsers($restaurantId = null, $branchId = null) {
        $sql = "SELECT u.*, r.name as role_name, res.name as restaurant_name, b.name as branch_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN restaurants res ON u.restaurant_id = res.id 
                LEFT JOIN branches b ON u.branch_id = b.id";
        
        $params = [];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE u.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
            
            if ($this->currentUser['branch_id']) {
                $sql .= " AND u.branch_id = ?";
                $params[] = $this->currentUser['branch_id'];
            }
        } else if ($restaurantId) {
            $sql .= " WHERE u.restaurant_id = ?";
            $params[] = $restaurantId;
            
            if ($branchId) {
                $sql .= " AND u.branch_id = ?";
                $params[] = $branchId;
            }
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    protected function getMenuCategories($restaurantId = null) {
        if (!$restaurantId && $this->currentUser['restaurant_id']) {
            $restaurantId = $this->currentUser['restaurant_id'];
        }
        
        $sql = "SELECT * FROM menu_categories";
        $params = [];
        
        if ($restaurantId) {
            $sql .= " WHERE restaurant_id = ?";
            $params[] = $restaurantId;
        }
        
        $sql .= " ORDER BY display_order, name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    protected function getMenuItems($restaurantId = null, $categoryId = null) {
        if (!$restaurantId && $this->currentUser['restaurant_id']) {
            $restaurantId = $this->currentUser['restaurant_id'];
        }
        
        $sql = "SELECT mi.*, mc.name as category_name 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id";
        
        $params = [];
        
        if ($restaurantId) {
            $sql .= " WHERE mi.restaurant_id = ?";
            $params[] = $restaurantId;
        }
        
        if ($categoryId) {
            $sql .= " AND mi.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY mi.display_order, mi.name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    protected function renderStatsCard($title, $value, $icon, $change = null) {
        $changeHtml = '';
        if ($change !== null) {
            $changeClass = $change >= 0 ? 'positive' : 'negative';
            $changeIcon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
            $changeHtml = '<div class="stat-change ' . $changeClass . '">
                <i class="fas ' . $changeIcon . '"></i> ' . abs($change) . '% from last month
            </div>';
        }
        
        return '
        <div class="stat-card">
            <h3>' . htmlspecialchars($title) . '</h3>
            <div class="stat-value">' . htmlspecialchars($value) . '</div>
            ' . $changeHtml . '
            <div style="margin-top: 1rem; color: #6b7280;">
                <i class="' . htmlspecialchars($icon) . '" style="font-size: 1.5rem;"></i>
            </div>
        </div>';
    }
    
    protected function renderContentCard($title, $content, $actions = '') {
        return '
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . htmlspecialchars($title) . '</h3>
                ' . $actions . '
            </div>
            <div class="card-content">
                ' . $content . '
            </div>
        </div>';
    }
    
    protected function renderDataTable($data, $columns, $actions = []) {
        $html = '<table class="data-table">
            <thead>
                <tr>';
        
        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
        
        if (!empty($actions)) {
            $html .= '<th>Actions</th>';
        }
        
        $html .= '</tr>
            </thead>
            <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            
            foreach ($columns as $column) {
                $value = $row[$column['key']] ?? '';
                
                if (isset($column['format']) && $column['format'] === 'price') {
                    $value = number_format($value, 2);
                } elseif (isset($column['format']) && $column['format'] === 'date') {
                    $value = date('M d, Y H:i', strtotime($value));
                } elseif (isset($column['format']) && $column['format'] === 'status') {
                    $statusClass = $value === 'active' || $value === 'completed' ? 'active' : 'pending';
                    $value = '<span class="status-badge ' . $statusClass . '">' . ucfirst($value) . '</span>';
                }
                
                $html .= '<td>' . $value . '</td>';
            }
            
            if (!empty($actions)) {
                $html .= '<td>';
                foreach ($actions as $action) {
                    $href = str_replace(['{id}', '{restaurant_id}', '{branch_id}'], 
                                       [$row['id'], $row['restaurant_id'] ?? '', $row['branch_id'] ?? ''], 
                                       $action['href']);
                    $html .= '<a href="' . htmlspecialchars($href) . '" class="btn btn-secondary" style="margin-right: 5px;">
                        <i class="' . htmlspecialchars($action['icon']) . '"></i>
                    </a>';
                }
                $html .= '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
        return $html;
    }
}
?>