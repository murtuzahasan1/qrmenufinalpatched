<?php
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class UsersPage extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('User Management');
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'view':
                $this->renderUserView();
                break;
            case 'edit':
                $this->renderUserEdit();
                break;
            case 'add':
                $this->renderUserAdd();
                break;
            default:
                $this->renderUserList();
        }
        
        $this->layout->renderFooter();
    }
    
    private function renderUserList() {
        $users = $this->getUsers();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Users</h3>
                <div>
                    ' . ($this->canManageUsers() ? '<a href="?action=add" class="btn btn-primary">Add User</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($users)) {
            echo '<p>No users found.</p>';
        } else {
            $columns = [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'role_name', 'label' => 'Role'],
                ['key' => 'restaurant_name', 'label' => 'Restaurant'],
                ['key' => 'branch_name', 'label' => 'Branch'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status']
            ];
            
            $actions = [];
            if ($this->canManageUsers()) {
                $actions = [
                    ['icon' => 'fas fa-edit', 'href' => '?action=edit&id={id}'],
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            } else {
                $actions = [
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            }
            
            echo $this->renderDataTable($users, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderUserView() {
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            echo '<div class="alert alert-error">User ID is required.</div>';
            return;
        }
        
        $user = $this->getUserDetails($userId);
        if (!$user) {
            echo '<div class="alert alert-error">User not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">User Details - ' . htmlspecialchars($user['name']) . '</h3>
                <div>
                    <a href="users.php" class="btn btn-secondary">Back to Users</a>
                    ' . ($this->canManageUsers() ? '<a href="?action=edit&id=' . $userId . '" class="btn btn-primary">Edit User</a>' : '') . '
                </div>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Personal Information</h4>
                        <p><strong>Name:</strong> ' . htmlspecialchars($user['name']) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($user['phone']) . '</p>
                        <p><strong>Role:</strong> ' . htmlspecialchars($user['role_name']) . '</p>
                        <p><strong>Status:</strong> <span class="status-badge ' . ($user['status'] ? 'active' : 'pending') . '">' . ($user['status'] ? 'Active' : 'Inactive') . '</span></p>
                    </div>
                    <div>
                        <h4>Assignment</h4>
                        <p><strong>Restaurant:</strong> ' . htmlspecialchars($user['restaurant_name']) . '</p>
                        <p><strong>Branch:</strong> ' . htmlspecialchars($user['branch_name']) . '</p>
                        <p><strong>Last Login:</strong> ' . ($user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never') . '</p>
                        <p><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($user['created_at'])) . '</p>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    private function renderUserEdit() {
        if (!$this->canManageUsers()) {
            echo '<div class="alert alert-error">You do not have permission to edit users.</div>';
            return;
        }
        
        $userId = $_GET['id'] ?? null;
        $user = $userId ? $this->getUserDetails($userId) : null;
        $roles = $this->getRoles();
        $restaurants = $this->getRestaurants();
        $branches = $this->getBranches();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . ($user ? 'Edit User' : 'Add User') . '</h3>
                <div>
                    <a href="users.php" class="btn btn-secondary">Back to Users</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="users.php?action=update' . ($userId ? '&id=' . $userId : '') . '" id="userForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" name="name" id="name" value="' . ($user ? htmlspecialchars($user['name']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" name="email" id="email" value="' . ($user ? htmlspecialchars($user['email']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="' . ($user ? htmlspecialchars($user['phone']) : '') . '" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="role_id">Role *</label>
                            <select name="role_id" id="role_id" required class="form-control">
                                <option value="">Select Role</option>';
        
        foreach ($roles as $role) {
            // Filter roles based on current user's permissions
            if ($this->currentUser['role'] === 'super_admin' || 
                in_array($role['name'], ['restaurant_owner', 'manager', 'branch_manager', 'chef', 'waiter', 'restaurant_staff'])) {
                $selected = ($user && $user['role_id'] == $role['id']) ? 'selected' : '';
                echo '<option value="' . $role['id'] . '" ' . $selected . '>' . htmlspecialchars($role['name']) . '</option>';
            }
        }
        
        echo '          </select>
                        </div>
                        <div class="form-group">
                            <label for="restaurant_id">Restaurant</label>
                            <select name="restaurant_id" id="restaurant_id" class="form-control">
                                <option value="">Select Restaurant</option>';
        
        foreach ($restaurants as $restaurant) {
            $selected = ($user && $user['restaurant_id'] == $restaurant['id']) ? 'selected' : '';
            echo '<option value="' . $restaurant['id'] . '" ' . $selected . '>' . htmlspecialchars($restaurant['name']) . '</option>';
        }
        
        echo '          </select>
                        </div>
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select name="branch_id" id="branch_id" class="form-control">
                                <option value="">Select Branch</option>';
        
        foreach ($branches as $branch) {
            $selected = ($user && $user['branch_id'] == $branch['id']) ? 'selected' : '';
            echo '<option value="' . $branch['id'] . '" ' . $selected . '>' . htmlspecialchars($branch['name']) . '</option>';
        }
        
        echo '          </select>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" ' . (!$user ? 'required' : '') . ' class="form-control">
                            ' . (!$user ? '' : '<small>Leave blank to keep current password</small>') . '
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1" ' . (!$user || $user['status'] ? 'selected' : '') . '>Active</option>
                                <option value="0" ' . ($user && !$user['status'] ? 'selected' : '') . '>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">' . ($user ? 'Update User' : 'Create User') . '</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'users.php\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
        
        // Add JavaScript for dynamic branch loading based on restaurant
        echo '<script>
        document.getElementById("restaurant_id").addEventListener("change", function() {
            const restaurantId = this.value;
            const branchSelect = document.getElementById("branch_id");
            
            // Clear current options
            branchSelect.innerHTML = "<option value=\"\">Select Branch</option>";
            
            if (restaurantId) {
                fetch("../api/branches.php?restaurant_id=" + restaurantId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            data.data.forEach(branch => {
                                const option = document.createElement("option");
                                option.value = branch.id;
                                option.textContent = branch.name;
                                branchSelect.appendChild(option);
                            });
                        }
                    });
            }
        });
        </script>';
    }
    
    private function renderUserAdd() {
        $this->renderUserEdit();
    }
    
    private function getUserDetails($userId) {
        $sql = "SELECT u.*, r.name as role_name, res.name as restaurant_name, b.name as branch_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN restaurants res ON u.restaurant_id = res.id 
                LEFT JOIN branches b ON u.branch_id = b.id 
                WHERE u.id = ?";
        
        $params = [$userId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND u.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
            
            if ($this->currentUser['branch_id']) {
                $sql .= " AND u.branch_id = ?";
                $params[] = $this->currentUser['branch_id'];
            }
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function getRoles() {
        if ($this->currentUser['role'] === 'super_admin') {
            return $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        } else {
            // Non-super admins can only create roles with equal or lower privileges
            $allowedRoles = ['restaurant_owner', 'manager', 'branch_manager', 'chef', 'waiter', 'restaurant_staff'];
            
            if ($this->currentUser['role'] === 'restaurant_owner') {
                $allowedRoles = ['manager', 'branch_manager', 'chef', 'waiter', 'restaurant_staff'];
            } elseif ($this->currentUser['role'] === 'manager') {
                $allowedRoles = ['branch_manager', 'chef', 'waiter', 'restaurant_staff'];
            } elseif ($this->currentUser['role'] === 'branch_manager') {
                $allowedRoles = ['chef', 'waiter', 'restaurant_staff'];
            }
            
            $placeholders = str_repeat('?,', count($allowedRoles) - 1) . '?';
            return $this->db->fetchAll("SELECT * FROM roles WHERE name IN ($placeholders) ORDER BY name", $allowedRoles);
        }
    }
    
    private function canManageUsers() {
        return in_array($this->currentUser['role'], ['super_admin', 'restaurant_owner', 'manager', 'branch_manager']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = new UsersPage();
    
    if ($_POST['action'] === 'update') {
        $userId = $_GET['id'];
        
        // Handle user update logic
        header('Location: users.php?success=1');
    }
} else {
    $page = new UsersPage();
    $page->render();
}
?>