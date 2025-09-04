<?php
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class BranchesPage extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('Branch Management');
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'view':
                $this->renderBranchView();
                break;
            case 'edit':
                $this->renderBranchEdit();
                break;
            case 'add':
                $this->renderBranchAdd();
                break;
            case 'performance':
                $this->renderBranchPerformance();
                break;
            default:
                $this->renderBranchList();
        }
        
        $this->layout->renderFooter();
    }
    
    private function renderBranchList() {
        $branches = $this->getBranches();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Branches</h3>
                <div>
                    ' . ($this->canManageBranches() ? '<a href="?action=add" class="btn btn-primary">Add Branch</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($branches)) {
            echo '<p>No branches found.</p>';
        } else {
            $columns = [
                ['key' => 'name', 'label' => 'Branch Name'],
                ['key' => 'restaurant_name', 'label' => 'Restaurant'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'address', 'label' => 'Address'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status']
            ];
            
            $actions = [];
            if ($this->canManageBranches()) {
                $actions = [
                    ['icon' => 'fas fa-edit', 'href' => '?action=edit&id={id}'],
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}'],
                    ['icon' => 'fas fa-chart-line', 'href' => '?action=performance&id={id}']
                ];
            } else {
                $actions = [
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            }
            
            echo $this->renderDataTable($branches, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderBranchView() {
        $branchId = $_GET['id'] ?? null;
        if (!$branchId) {
            echo '<div class="alert alert-error">Branch ID is required.</div>';
            return;
        }
        
        $branch = $this->getBranchDetails($branchId);
        if (!$branch) {
            echo '<div class="alert alert-error">Branch not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Branch Details - ' . htmlspecialchars($branch['name']) . '</h3>
                <div>
                    <a href="branches.php" class="btn btn-secondary">Back to Branches</a>
                    ' . ($this->canManageBranches() ? '<a href="?action=edit&id=' . $branchId . '" class="btn btn-primary">Edit Branch</a>' : '') . '
                </div>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Branch Information</h4>
                        <p><strong>Name:</strong> ' . htmlspecialchars($branch['name']) . '</p>
                        <p><strong>Restaurant:</strong> ' . htmlspecialchars($branch['restaurant_name']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($branch['phone']) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($branch['email']) . '</p>
                        <p><strong>Status:</strong> <span class="status-badge ' . ($branch['status'] ? 'active' : 'pending') . '">' . ($branch['status'] ? 'Active' : 'Inactive') . '</span></p>
                    </div>
                    <div>
                        <h4>Location</h4>
                        <p><strong>Address:</strong> ' . htmlspecialchars($branch['address']) . '</p>
                        ' . ($branch['latitude'] && $branch['longitude'] ? '
                        <p><strong>Coordinates:</strong> ' . htmlspecialchars($branch['latitude']) . ', ' . htmlspecialchars($branch['longitude']) . '</p>
                        ' : '') . '
                        <p><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($branch['created_at'])) . '</p>
                    </div>
                </div>
                
                ' . ($branch['description'] ? '<div style="margin-top: 1rem;">
                    <h4>Description</h4>
                    <p>' . htmlspecialchars($branch['description']) . '</p>
                </div>' : '') . '
                
                <div style="margin-top: 2rem;">
                    <h4>Branch Statistics</h4>
                    ' . $this->renderBranchStats($branchId) . '
                </div>
            </div>
        </div>';
    }
    
    private function renderBranchEdit() {
        if (!$this->canManageBranches()) {
            echo '<div class="alert alert-error">You do not have permission to edit branches.</div>';
            return;
        }
        
        $branchId = $_GET['id'] ?? null;
        $branch = $branchId ? $this->getBranchDetails($branchId) : null;
        $restaurants = $this->getRestaurants();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . ($branch ? 'Edit Branch' : 'Add Branch') . '</h3>
                <div>
                    <a href="branches.php" class="btn btn-secondary">Back to Branches</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="branches.php?action=update' . ($branchId ? '&id=' . $branchId : '') . '" id="branchForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="restaurant_id">Restaurant *</label>
                            <select name="restaurant_id" id="restaurant_id" required class="form-control">
                                <option value="">Select Restaurant</option>';
        
        foreach ($restaurants as $restaurant) {
            $selected = ($branch && $branch['restaurant_id'] == $restaurant['id']) ? 'selected' : '';
            echo '<option value="' . $restaurant['id'] . '" ' . $selected . '>' . htmlspecialchars($restaurant['name']) . '</option>';
        }
        
        echo '          </select>
                        </div>
                        <div class="form-group">
                            <label for="name">Branch Name *</label>
                            <input type="text" name="name" id="name" value="' . ($branch ? htmlspecialchars($branch['name']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" name="phone" id="phone" value="' . ($branch ? htmlspecialchars($branch['phone']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="' . ($branch ? htmlspecialchars($branch['email']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="address">Address *</label>
                            <textarea name="address" id="address" rows="3" required class="form-control">' . ($branch ? htmlspecialchars($branch['address']) : '') . '</textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-control">' . ($branch ? htmlspecialchars($branch['description']) : '') . '</textarea>
                        </div>
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="number" name="latitude" id="latitude" step="any" value="' . ($branch ? htmlspecialchars($branch['latitude']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="number" name="longitude" id="longitude" step="any" value="' . ($branch ? htmlspecialchars($branch['longitude']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1" ' . (!$branch || $branch['status'] ? 'selected' : '') . '>Active</option>
                                <option value="0" ' . ($branch && !$branch['status'] ? 'selected' : '') . '>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">' . ($branch ? 'Update Branch' : 'Create Branch') . '</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'branches.php\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    private function renderBranchAdd() {
        $this->renderBranchEdit();
    }
    
    private function renderBranchPerformance() {
        $branchId = $_GET['id'] ?? null;
        if (!$branchId) {
            echo '<div class="alert alert-error">Branch ID is required.</div>';
            return;
        }
        
        $branch = $this->getBranchDetails($branchId);
        if (!$branch) {
            echo '<div class="alert alert-error">Branch not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Branch Performance - ' . htmlspecialchars($branch['name']) . '</h3>
                <div>
                    <a href="branches.php" class="btn btn-secondary">Back to Branches</a>
                    <a href="?action=view&id=' . $branchId . '" class="btn btn-primary">View Branch</a>
                </div>
            </div>
            <div class="card-content">
                ' . $this->renderBranchStats($branchId, true) . '
            </div>
        </div>';
    }
    
    private function renderBranchStats($branchId, $detailed = false) {
        // Get basic statistics
        $totalOrders = $this->db->fetch("SELECT COUNT(*) as count FROM orders WHERE branch_id = ?", [$branchId])['count'];
        $todayOrders = $this->db->fetch("SELECT COUNT(*) as count FROM orders WHERE branch_id = ? AND DATE(created_at) = DATE('now')", [$branchId])['count'];
        $totalRevenue = $this->db->fetch("SELECT SUM(total_amount) as total FROM orders WHERE branch_id = ? AND payment_status = 'paid'", [$branchId])['total'] ?: 0;
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        $html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div style="text-align: center; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #0369a1;">' . $totalOrders . '</div>
                <div style="color: #0369a1;">Total Orders</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f0fdf4; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #166534;">' . $todayOrders . '</div>
                <div style="color: #166534;">Today\'s Orders</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #92400e;">৳' . number_format($totalRevenue, 2) . '</div>
                <div style="color: #92400e;">Total Revenue</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #e0e7ff; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: #3730a3;">৳' . number_format($avgOrderValue, 2) . '</div>
                <div style="color: #3730a3;">Avg Order Value</div>
            </div>
        </div>';
        
        if ($detailed) {
            // Get monthly statistics for the past 6 months
            $monthlyStats = $this->db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as orders,
                    SUM(total_amount) as revenue
                FROM orders 
                WHERE branch_id = ? 
                AND created_at >= datetime('now', '-6 months')
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT 30
            ", [$branchId]);
            
            if (!empty($monthlyStats)) {
                $html .= '<div style="margin-top: 2rem;">
                    <h4>Recent Performance</h4>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg Order</th>
                                </tr>
                            </thead>
                            <tbody>';
                
                foreach ($monthlyStats as $stat) {
                    $avgOrder = $stat['orders'] > 0 ? $stat['revenue'] / $stat['orders'] : 0;
                    $html .= '<tr>
                        <td>' . date('M d, Y', strtotime($stat['date'])) . '</td>
                        <td>' . $stat['orders'] . '</td>
                        <td>৳' . number_format($stat['revenue'], 2) . '</td>
                        <td>৳' . number_format($avgOrder, 2) . '</td>
                    </tr>';
                }
                
                $html .= '</tbody></table></div></div>';
            }
            
            // Get top menu items for this branch
            $topItems = $this->db->fetchAll("
                SELECT mi.name, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_quantity
                FROM order_items oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.branch_id = ?
                AND o.created_at >= datetime('now', '-30 days')
                GROUP BY mi.id
                ORDER BY total_quantity DESC
                LIMIT 5
            ", [$branchId]);
            
            if (!empty($topItems)) {
                $html .= '<div style="margin-top: 2rem;">
                    <h4>Top Menu Items (Last 30 Days)</h4>
                    <div class="menu-grid">';
                
                foreach ($topItems as $item) {
                    $html .= '<div class="menu-item">
                        <div class="menu-item-info">
                            <div class="menu-item-name">' . htmlspecialchars($item['name']) . '</div>
                            <div class="menu-item-description">' . $item['total_quantity'] . ' orders</div>
                        </div>
                    </div>';
                }
                
                $html .= '</div></div>';
            }
        }
        
        return $html;
    }
    
    private function getBranchDetails($branchId) {
        $sql = "SELECT b.*, r.name as restaurant_name 
                FROM branches b 
                JOIN restaurants r ON b.restaurant_id = r.id 
                WHERE b.id = ?";
        
        $params = [$branchId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND b.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function canManageBranches() {
        return in_array($this->currentUser['role'], ['super_admin', 'restaurant_owner', 'manager']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = new BranchesPage();
    
    if ($_POST['action'] === 'update') {
        $branchId = $_GET['id'];
        
        // Handle branch update logic
        header('Location: branches.php?success=1');
    }
} else {
    $page = new BranchesPage();
    $page->render();
}
?>