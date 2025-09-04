<?php
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class RestaurantsPage extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('Restaurant Management');
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'view':
                $this->renderRestaurantView();
                break;
            case 'edit':
                $this->renderRestaurantEdit();
                break;
            case 'add':
                $this->renderRestaurantAdd();
                break;
            default:
                $this->renderRestaurantList();
        }
        
        $this->layout->renderFooter();
    }
    
    private function renderRestaurantList() {
        $restaurants = $this->getRestaurants();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Restaurants</h3>
                <div>
                    ' . ($this->canManageRestaurants() ? '<a href="?action=add" class="btn btn-primary">Add Restaurant</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($restaurants)) {
            echo '<p>No restaurants found.</p>';
        } else {
            $columns = [
                ['key' => 'name', 'label' => 'Restaurant Name'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
                ['key' => 'created_at', 'label' => 'Created', 'format' => 'date']
            ];
            
            $actions = [];
            if ($this->canManageRestaurants()) {
                $actions = [
                    ['icon' => 'fas fa-edit', 'href' => '?action=edit&id={id}'],
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            } else {
                $actions = [
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            }
            
            echo $this->renderDataTable($restaurants, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderRestaurantView() {
        $restaurantId = $_GET['id'] ?? null;
        if (!$restaurantId) {
            echo '<div class="alert alert-error">Restaurant ID is required.</div>';
            return;
        }
        
        $restaurant = $this->getRestaurantDetails($restaurantId);
        if (!$restaurant) {
            echo '<div class="alert alert-error">Restaurant not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Restaurant Details - ' . htmlspecialchars($restaurant['name']) . '</h3>
                <div>
                    <a href="restaurants.php" class="btn btn-secondary">Back to Restaurants</a>
                    ' . ($this->canManageRestaurants() ? '<a href="?action=edit&id=' . $restaurantId . '" class="btn btn-primary">Edit Restaurant</a>' : '') . '
                </div>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Restaurant Information</h4>
                        <p><strong>Name:</strong> ' . htmlspecialchars($restaurant['name']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($restaurant['phone']) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($restaurant['email']) . '</p>
                        <p><strong>Website:</strong> ' . ($restaurant['website'] ? '<a href="' . htmlspecialchars($restaurant['website']) . '" target="_blank">' . htmlspecialchars($restaurant['website']) . '</a>' : 'N/A') . '</p>
                        <p><strong>Status:</strong> <span class="status-badge ' . ($restaurant['status'] ? 'active' : 'pending') . '">' . ($restaurant['status'] ? 'Active' : 'Inactive') . '</span></p>
                    </div>
                    <div>
                        <h4>Additional Details</h4>
                        <p><strong>Address:</strong> ' . htmlspecialchars($restaurant['address']) . '</p>
                        <p><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($restaurant['created_at'])) . '</p>
                        ' . ($restaurant['description'] ? '<p><strong>Description:</strong> ' . htmlspecialchars($restaurant['description']) . '</p>' : '') . '
                    </div>
                </div>
                
                ' . ($restaurant['logo'] ? '<div style="margin-top: 1rem;">
                    <h4>Logo</h4>
                    <img src="' . htmlspecialchars($restaurant['logo']) . '" alt="' . htmlspecialchars($restaurant['name']) . '" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem;">
                </div>' : '') . '
                
                <div style="margin-top: 2rem;">
                    <h4>Restaurant Statistics</h4>
                    ' . $this->renderRestaurantStats($restaurantId) . '
                </div>
            </div>
        </div>';
    }
    
    private function renderRestaurantEdit() {
        if (!$this->canManageRestaurants()) {
            echo '<div class="alert alert-error">You do not have permission to edit restaurants.</div>';
            return;
        }
        
        $restaurantId = $_GET['id'] ?? null;
        $restaurant = $restaurantId ? $this->getRestaurantDetails($restaurantId) : null;
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . ($restaurant ? 'Edit Restaurant' : 'Add Restaurant') . '</h3>
                <div>
                    <a href="restaurants.php" class="btn btn-secondary">Back to Restaurants</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="restaurants.php?action=update' . ($restaurantId ? '&id=' . $restaurantId : '') . '" id="restaurantForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Restaurant Name *</label>
                            <input type="text" name="name" id="name" value="' . ($restaurant ? htmlspecialchars($restaurant['name']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" name="phone" id="phone" value="' . ($restaurant ? htmlspecialchars($restaurant['phone']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="' . ($restaurant ? htmlspecialchars($restaurant['email']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" name="website" id="website" value="' . ($restaurant ? htmlspecialchars($restaurant['website']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="address">Address *</label>
                            <textarea name="address" id="address" rows="3" required class="form-control">' . ($restaurant ? htmlspecialchars($restaurant['address']) : '') . '</textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="4" class="form-control">' . ($restaurant ? htmlspecialchars($restaurant['description']) : '') . '</textarea>
                        </div>
                        <div class="form-group">
                            <label for="logo">Logo</label>
                            <input type="file" name="logo" id="logo" accept="image/*" class="form-control">
                            ' . ($restaurant && $restaurant['logo'] ? '<small>Current logo: ' . htmlspecialchars($restaurant['logo']) . '</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1" ' . (!$restaurant || $restaurant['status'] ? 'selected' : '') . '>Active</option>
                                <option value="0" ' . ($restaurant && !$restaurant['status'] ? 'selected' : '') . '>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">' . ($restaurant ? 'Update Restaurant' : 'Create Restaurant') . '</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'restaurants.php\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    private function renderRestaurantAdd() {
        $this->renderRestaurantEdit();
    }
    
    private function renderRestaurantStats($restaurantId) {
        // Get basic statistics
        $totalBranches = $this->db->fetch("SELECT COUNT(*) as count FROM branches WHERE restaurant_id = ?", [$restaurantId])['count'];
        $totalUsers = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE restaurant_id = ?", [$restaurantId])['count'];
        $totalOrders = $this->db->fetch("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = ?", [$restaurantId])['count'];
        $totalRevenue = $this->db->fetch("SELECT SUM(total_amount) as total FROM orders WHERE restaurant_id = ? AND payment_status = 'paid'", [$restaurantId])['total'] ?: 0;
        
        $html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="text-align: center; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #0369a1;">' . $totalBranches . '</div>
                <div style="color: #0369a1;">Total Branches</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f0fdf4; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #166534;">' . $totalUsers . '</div>
                <div style="color: #166534;">Total Staff</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #92400e;">' . $totalOrders . '</div>
                <div style="color: #92400e;">Total Orders</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #e0e7ff; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #3730a3;">৳' . number_format($totalRevenue, 2) . '</div>
                <div style="color: #3730a3;">Total Revenue</div>
            </div>
        </div>';
        
        // Get recent activity
        $recentOrders = $this->db->fetchAll("
            SELECT o.id, o.total_amount, o.created_at, b.name as branch_name
            FROM orders o
            LEFT JOIN branches b ON o.branch_id = b.id
            WHERE o.restaurant_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ", [$restaurantId]);
        
        if (!empty($recentOrders)) {
            $html .= '<div style="margin-top: 2rem;">
                <h4>Recent Orders</h4>
                <div style="max-height: 200px; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Branch</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($recentOrders as $order) {
                $html .= '<tr>
                    <td>#' . $order['id'] . '</td>
                    <td>' . htmlspecialchars($order['branch_name']) . '</td>
                    <td>৳' . number_format($order['total_amount'], 2) . '</td>
                    <td>' . date('M d, H:i', strtotime($order['created_at'])) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div></div>';
        }
        
        return $html;
    }
    
    private function getRestaurantDetails($restaurantId) {
        $sql = "SELECT * FROM restaurants WHERE id = ?";
        $params = [$restaurantId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND id = ?";
            $params[] = $this->currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function canManageRestaurants() {
        return $this->currentUser['role'] === 'super_admin';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = new RestaurantsPage();
    
    if ($_POST['action'] === 'update') {
        $restaurantId = $_GET['id'];
        
        // Handle restaurant update logic
        header('Location: restaurants.php?success=1');
    }
} else {
    $page = new RestaurantsPage();
    $page->render();
}
?>