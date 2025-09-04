<?php
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class MenuPage extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('Menu Management');
        
        $action = $_GET['action'] ?? 'list';
        $type = $_GET['type'] ?? 'categories';
        
        switch ($action) {
            case 'view':
                $this->renderMenuItemView();
                break;
            case 'edit':
                $this->renderMenuItemEdit();
                break;
            case 'add':
                $this->renderMenuItemAdd();
                break;
            case 'toggle':
                $this->renderMenuItemToggle();
                break;
            default:
                $this->renderMenuList($type);
        }
        
        $this->layout->renderFooter();
    }
    
    private function renderMenuList($type = 'categories') {
        if ($type === 'categories') {
            $this->renderCategoriesList();
        } else {
            $this->renderMenuItemsList();
        }
    }
    
    private function renderCategoriesList() {
        $categories = $this->getMenuCategories();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Menu Categories</h3>
                <div>
                    <a href="?type=items" class="btn btn-secondary">View Menu Items</a>
                    ' . ($this->canManageMenu() ? '<a href="?action=add&type=category" class="btn btn-primary">Add Category</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($categories)) {
            echo '<p>No categories found.</p>';
        } else {
            $columns = [
                ['key' => 'name', 'label' => 'Category Name'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'display_order', 'label' => 'Order'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status']
            ];
            
            $actions = [];
            if ($this->canManageMenu()) {
                $actions = [
                    ['icon' => 'fas fa-edit', 'href' => '?action=edit&type=category&id={id}'],
                    ['icon' => 'fas fa-eye', 'href' => '?type=items&category_id={id}']
                ];
            }
            
            echo $this->renderDataTable($categories, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderMenuItemsList() {
        $categoryId = $_GET['category_id'] ?? null;
        $menuItems = $this->getMenuItems(null, $categoryId);
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Menu Items</h3>
                <div>
                    <a href="?type=categories" class="btn btn-secondary">View Categories</a>
                    ' . ($this->canManageMenu() ? '<a href="?action=add&type=item" class="btn btn-primary">Add Item</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($menuItems)) {
            echo '<p>No menu items found.</p>';
        } else {
            $columns = [
                ['key' => 'name', 'label' => 'Item Name'],
                ['key' => 'category_name', 'label' => 'Category'],
                ['key' => 'price', 'label' => 'Price', 'format' => 'price'],
                ['key' => 'available', 'label' => 'Available', 'format' => 'status']
            ];
            
            $actions = [];
            if ($this->canManageMenu()) {
                $actions = [
                    ['icon' => 'fas fa-edit', 'href' => '?action=edit&type=item&id={id}'],
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}'],
                    ['icon' => 'fas fa-toggle-on', 'href' => '?action=toggle&id={id}']
                ];
            } else {
                $actions = [
                    ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
                ];
            }
            
            echo $this->renderDataTable($menuItems, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderMenuItemView() {
        $itemId = $_GET['id'] ?? null;
        if (!$itemId) {
            echo '<div class="alert alert-error">Menu item ID is required.</div>';
            return;
        }
        
        $item = $this->getMenuItemDetails($itemId);
        if (!$item) {
            echo '<div class="alert alert-error">Menu item not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Menu Item Details</h3>
                <div>
                    <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
                    ' . ($this->canManageMenu() ? '<a href="?action=edit&id=' . $itemId . '" class="btn btn-primary">Edit Item</a>' : '') . '
                </div>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Item Information</h4>
                        <p><strong>Name:</strong> ' . htmlspecialchars($item['name']) . '</p>
                        <p><strong>Category:</strong> ' . htmlspecialchars($item['category_name']) . '</p>
                        <p><strong>Price:</strong> ৳' . number_format($item['price'], 2) . '</p>
                        <p><strong>Status:</strong> <span class="status-badge ' . ($item['available'] ? 'active' : 'pending') . '">' . ($item['available'] ? 'Available' : 'Unavailable') . '</span></p>
                        <p><strong>Display Order:</strong> ' . htmlspecialchars($item['display_order']) . '</p>
                    </div>
                    <div>
                        <h4>Additional Details</h4>
                        ' . ($item['description'] ? '<p><strong>Description:</strong> ' . htmlspecialchars($item['description']) . '</p>' : '') . '
                        ' . ($item['ingredients'] ? '<p><strong>Ingredients:</strong> ' . htmlspecialchars($item['ingredients']) . '</p>' : '') . '
                        ' . ($item['allergens'] ? '<p><strong>Allergens:</strong> ' . htmlspecialchars($item['allergens']) . '</p>' : '') . '
                        <p><strong>Spicy Level:</strong> ' . $item['spicy_level'] . '/5</p>
                        <p><strong>Vegetarian:</strong> ' . ($item['vegetarian'] ? 'Yes' : 'No') . '</p>
                        <p><strong>Vegan:</strong> ' . ($item['vegan'] ? 'Yes' : 'No') . '</p>
                        <p><strong>Gluten Free:</strong> ' . ($item['gluten_free'] ? 'Yes' : 'No') . '</p>
                    </div>
                </div>
                
                ' . ($item['image'] ? '<div style="margin-top: 1rem;">
                    <h4>Image</h4>
                    <img src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem;">
                </div>' : '') . '
            </div>
        </div>';
    }
    
    private function renderMenuItemEdit() {
        if (!$this->canManageMenu()) {
            echo '<div class="alert alert-error">You do not have permission to edit menu items.</div>';
            return;
        }
        
        $itemId = $_GET['id'] ?? null;
        $type = $_GET['type'] ?? 'item';
        
        if ($type === 'category') {
            $this->renderCategoryEditForm($itemId);
        } else {
            $this->renderMenuItemEditForm($itemId);
        }
    }
    
    private function renderCategoryEditForm($categoryId) {
        $category = $categoryId ? $this->getCategoryDetails($categoryId) : null;
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . ($category ? 'Edit Category' : 'Add Category') . '</h3>
                <div>
                    <a href="menu.php?type=categories" class="btn btn-secondary">Back to Categories</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="menu.php?action=update&type=category' . ($categoryId ? '&id=' . $categoryId : '') . '" id="categoryForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" name="name" id="name" value="' . ($category ? htmlspecialchars($category['name']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" id="display_order" value="' . ($category ? htmlspecialchars($category['display_order']) : '0') . '" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-control">' . ($category ? htmlspecialchars($category['description']) : '') . '</textarea>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">' . ($category ? 'Update Category' : 'Create Category') . '</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'menu.php?type=categories\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    private function renderMenuItemEditForm($itemId) {
        $item = $itemId ? $this->getMenuItemDetails($itemId) : null;
        $categories = $this->getMenuCategories();
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">' . ($item ? 'Edit Menu Item' : 'Add Menu Item') . '</h3>
                <div>
                    <a href="menu.php?type=items" class="btn btn-secondary">Back to Menu Items</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="menu.php?action=update&type=item' . ($itemId ? '&id=' . $itemId : '') . '" id="menuItemForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Item Name *</label>
                            <input type="text" name="name" id="name" value="' . ($item ? htmlspecialchars($item['name']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select name="category_id" id="category_id" required class="form-control">
                                <option value="">Select Category</option>';
        
        foreach ($categories as $category) {
            $selected = ($item && $item['category_id'] == $category['id']) ? 'selected' : '';
            echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
        }
        
        echo '          </select>
                        </div>
                        <div class="form-group">
                            <label for="price">Price *</label>
                            <input type="number" name="price" id="price" step="0.01" value="' . ($item ? htmlspecialchars($item['price']) : '') . '" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" id="display_order" value="' . ($item ? htmlspecialchars($item['display_order']) : '0') . '" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-control">' . ($item ? htmlspecialchars($item['description']) : '') . '</textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="ingredients">Ingredients</label>
                            <textarea name="ingredients" id="ingredients" rows="2" class="form-control">' . ($item ? htmlspecialchars($item['ingredients']) : '') . '</textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="allergens">Allergens</label>
                            <textarea name="allergens" id="allergens" rows="2" class="form-control">' . ($item ? htmlspecialchars($item['allergens']) : '') . '</textarea>
                        </div>
                        <div class="form-group">
                            <label for="spicy_level">Spicy Level</label>
                            <select name="spicy_level" id="spicy_level" class="form-control">
                                <option value="0" ' . (!$item || $item['spicy_level'] == 0 ? 'selected' : '') . '>Not Spicy</option>
                                <option value="1" ' . ($item && $item['spicy_level'] == 1 ? 'selected' : '') . '>Mild</option>
                                <option value="2" ' . ($item && $item['spicy_level'] == 2 ? 'selected' : '') . '>Medium</option>
                                <option value="3" ' . ($item && $item['spicy_level'] == 3 ? 'selected' : '') . '>Hot</option>
                                <option value="4" ' . ($item && $item['spicy_level'] == 4 ? 'selected' : '') . '>Very Hot</option>
                                <option value="5" ' . ($item && $item['spicy_level'] == 5 ? 'selected' : '') . '>Extra Hot</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Special Diets</label>
                            <div style="margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <input type="checkbox" name="vegetarian" value="1" ' . ($item && $item['vegetarian'] ? 'checked' : '') . ' style="margin-right: 0.5rem;">
                                    Vegetarian
                                </label>
                                <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <input type="checkbox" name="vegan" value="1" ' . ($item && $item['vegan'] ? 'checked' : '') . ' style="margin-right: 0.5rem;">
                                    Vegan
                                </label>
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" name="gluten_free" value="1" ' . ($item && $item['gluten_free'] ? 'checked' : '') . ' style="margin-right: 0.5rem;">
                                    Gluten Free
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" name="image" id="image" accept="image/*" class="form-control">
                            ' . ($item && $item['image'] ? '<small>Current image: ' . htmlspecialchars($item['image']) . '</small>' : '') . '
                        </div>
                        <div class="form-group">
                            <label for="available">Available</label>
                            <select name="available" id="available" class="form-control">
                                <option value="1" ' . (!$item || $item['available'] ? 'selected' : '') . '>Yes</option>
                                <option value="0" ' . ($item && !$item['available'] ? 'selected' : '') . '>No</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">' . ($item ? 'Update Item' : 'Create Item') . '</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'menu.php?type=items\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    private function renderMenuItemAdd() {
        if (!$this->canManageMenu()) {
            echo '<div class="alert alert-error">You do not have permission to add menu items.</div>';
            return;
        }
        
        $type = $_GET['type'] ?? 'item';
        
        if ($type === 'category') {
            $this->renderCategoryEditForm(null);
        } else {
            $this->renderMenuItemEditForm(null);
        }
    }
    
    private function renderMenuItemToggle() {
        if (!$this->canManageMenu()) {
            echo '<div class="alert alert-error">You do not have permission to manage menu items.</div>';
            return;
        }
        
        $itemId = $_GET['id'] ?? null;
        if (!$itemId) {
            echo '<div class="alert alert-error">Menu item ID is required.</div>';
            return;
        }
        
        $item = $this->getMenuItemDetails($itemId);
        if (!$item) {
            echo '<div class="alert alert-error">Menu item not found.</div>';
            return;
        }
        
        $newStatus = $item['available'] ? 0 : 1;
        $updated = $this->db->update('menu_items', ['available' => $newStatus], 'id = ?', [$itemId]);
        
        if ($updated) {
            header('Location: menu.php?type=items&success=1');
        } else {
            header('Location: menu.php?type=items&error=1');
        }
    }
    
    private function getMenuItemDetails($itemId) {
        $sql = "SELECT mi.*, mc.name as category_name 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id 
                WHERE mi.id = ?";
        
        $params = [$itemId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND mi.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function getCategoryDetails($categoryId) {
        $sql = "SELECT * FROM menu_categories WHERE id = ?";
        $params = [$categoryId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function canManageMenu() {
        return in_array($this->currentUser['role'], ['super_admin', 'restaurant_owner', 'manager', 'branch_manager']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = new MenuPage();
    
    if ($_POST['action'] === 'update') {
        $type = $_GET['type'];
        $itemId = $_GET['id'];
        
        if ($type === 'category') {
            // Handle category update
            header('Location: menu.php?type=categories&success=1');
        } else {
            // Handle menu item update
            header('Location: menu.php?type=items&success=1');
        }
    }
} else {
    $page = new MenuPage();
    $page->render();
}
?>