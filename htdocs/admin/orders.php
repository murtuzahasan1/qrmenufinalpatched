<?php
require_once __DIR__ . '/includes/BaseDashboard.php';
require_once __DIR__ . '/templates/layout.php';

class OrdersPage extends BaseDashboard {
    private $layout;
    
    public function __construct() {
        parent::__construct();
        $this->layout = new AdminLayout();
    }
    
    public function render() {
        $this->layout->renderHeader('Order Management');
        
        $action = $_GET['action'] ?? 'list';
        $status = $_GET['status'] ?? 'all';
        
        switch ($action) {
            case 'view':
                $this->renderOrderView();
                break;
            case 'edit':
                $this->renderOrderEdit();
                break;
            case 'add':
                $this->renderOrderAdd();
                break;
            default:
                $this->renderOrderList($status);
        }
        
        $this->layout->renderFooter();
    }
    
    private function renderOrderList($status = 'all') {
        $orders = $this->getOrders($status);
        
        // Filter controls
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Orders</h3>
                <div>
                    ' . $this->renderStatusFilter($status) . '
                    ' . ($this->canAddOrders() ? '<a href="?action=add" class="btn btn-primary">Add New Order</a>' : '') . '
                </div>
            </div>
            <div class="card-content">';
        
        if (empty($orders)) {
            echo '<p>No orders found.</p>';
        } else {
            $columns = $this->getOrderColumns();
            $actions = $this->getOrderActions();
            
            echo $this->renderDataTable($orders, $columns, $actions);
        }
        
        echo '</div></div>';
    }
    
    private function renderOrderView() {
        $orderId = $_GET['id'] ?? null;
        if (!$orderId) {
            echo '<div class="alert alert-error">Order ID is required.</div>';
            return;
        }
        
        $order = $this->getOrderDetails($orderId);
        if (!$order) {
            echo '<div class="alert alert-error">Order not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Order Details - #' . htmlspecialchars($order['id']) . '</h3>
                <div>
                    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                    ' . ($this->canEditOrders() ? '<a href="?action=edit&id=' . $orderId . '" class="btn btn-primary">Edit Order</a>' : '') . '
                </div>
            </div>
            <div class="card-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Order Information</h4>
                        <p><strong>Order ID:</strong> #' . htmlspecialchars($order['id']) . '</p>
                        <p><strong>Customer:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($order['customer_phone']) . '</p>
                        <p><strong>Table:</strong> ' . htmlspecialchars($order['table_number']) . '</p>
                        <p><strong>Status:</strong> <span class="status-badge ' . $order['status'] . '">' . ucfirst($order['status']) . '</span></p>
                        <p><strong>Payment Status:</strong> <span class="status-badge ' . $order['payment_status'] . '">' . ucfirst($order['payment_status']) . '</span></p>
                        <p><strong>Total Amount:</strong> ৳' . number_format($order['total_amount'], 2) . '</p>
                        <p><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($order['created_at'])) . '</p>
                    </div>
                    <div>
                        <h4>Location</h4>
                        <p><strong>Restaurant:</strong> ' . htmlspecialchars($order['restaurant_name']) . '</p>
                        <p><strong>Branch:</strong> ' . htmlspecialchars($order['branch_name']) . '</p>
                        ' . ($order['notes'] ? '<h4>Notes</h4><p>' . htmlspecialchars($order['notes']) . '</p>' : '') . '
                    </div>
                </div>
                
                <h4 style="margin-top: 2rem;">Order Items</h4>';
        
        $items = $this->getOrderItems($orderId);
        if (!empty($items)) {
            $itemColumns = [
                ['key' => 'name', 'label' => 'Item'],
                ['key' => 'quantity', 'label' => 'Qty'],
                ['key' => 'unit_price', 'label' => 'Unit Price', 'format' => 'price'],
                ['key' => 'total_price', 'label' => 'Total', 'format' => 'price']
            ];
            
            echo $this->renderDataTable($items, $itemColumns);
        } else {
            echo '<p>No items found for this order.</p>';
        }
        
        echo '</div></div>';
    }
    
    private function renderOrderEdit() {
        $orderId = $_GET['id'] ?? null;
        if (!$orderId) {
            echo '<div class="alert alert-error">Order ID is required.</div>';
            return;
        }
        
        $order = $this->getOrderDetails($orderId);
        if (!$order) {
            echo '<div class="alert alert-error">Order not found.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Edit Order - #' . htmlspecialchars($order['id']) . '</h3>
                <div>
                    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                    <a href="?action=view&id=' . $orderId . '" class="btn btn-secondary">View Order</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="orders.php?action=update&id=' . $orderId . '" id="orderEditForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="pending" ' . ($order['status'] === 'pending' ? 'selected' : '') . '>Pending</option>
                                <option value="preparing" ' . ($order['status'] === 'preparing' ? 'selected' : '') . '>Preparing</option>
                                <option value="ready" ' . ($order['status'] === 'ready' ? 'selected' : '') . '>Ready</option>
                                <option value="served" ' . ($order['status'] === 'served' ? 'selected' : '') . '>Served</option>
                                <option value="completed" ' . ($order['status'] === 'completed' ? 'selected' : '') . '>Completed</option>
                                <option value="cancelled" ' . ($order['status'] === 'cancelled' ? 'selected' : '') . '>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_status">Payment Status</label>
                            <select name="payment_status" id="payment_status" class="form-control">
                                <option value="pending" ' . ($order['payment_status'] === 'pending' ? 'selected' : '') . '>Pending</option>
                                <option value="paid" ' . ($order['payment_status'] === 'paid' ? 'selected' : '') . '>Paid</option>
                                <option value="failed" ' . ($order['payment_status'] === 'failed' ? 'selected' : '') . '>Failed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control">' . htmlspecialchars($order['notes']) . '</textarea>
                        </div>
                    </div>
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">Update Order</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'orders.php\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    private function renderOrderAdd() {
        if (!$this->canAddOrders()) {
            echo '<div class="alert alert-error">You do not have permission to add orders.</div>';
            return;
        }
        
        echo '<div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Add New Order</h3>
                <div>
                    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>
            <div class="card-content">
                <form method="POST" action="orders.php?action=create" id="orderAddForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="customer_name">Customer Name *</label>
                            <input type="text" name="customer_name" id="customer_name" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Customer Phone</label>
                            <input type="tel" name="customer_phone" id="customer_phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="table_number">Table Number *</label>
                            <input type="text" name="table_number" id="table_number" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select name="branch_id" id="branch_id" class="form-control">
                                <option value="">Select Branch</option>
                                ' . $this->renderBranchOptions() . '
                            </select>
                        </div>
                    </div>
                    
                    <h4 style="margin: 1.5rem 0 1rem 0;">Order Items</h4>
                    <div id="orderItems">
                        <div class="order-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem; align-items: end;">
                            <div>
                                <label>Menu Item</label>
                                <select name="items[0][menu_item_id]" class="form-control menu-item-select">
                                    <option value="">Select Item</option>
                                    ' . $this->renderMenuItemOptions() . '
                                </select>
                            </div>
                            <div>
                                <label>Quantity</label>
                                <input type="number" name="items[0][quantity]" value="1" min="1" class="form-control">
                            </div>
                            <div>
                                <label>Price</label>
                                <input type="text" name="items[0][price]" readonly class="form-control">
                            </div>
                            <div>
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-danger" onclick="removeOrderItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin: 1rem 0;">
                        <button type="button" class="btn btn-secondary" onclick="addOrderItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">Create Order</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href=\'orders.php\'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>';
        
        // Add JavaScript for dynamic item management
        echo '<script>
        function addOrderItem() {
            const container = document.getElementById("orderItems");
            const itemCount = container.children.length;
            const newItem = document.createElement("div");
            newItem.className = "order-item";
            newItem.style.cssText = "display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem; align-items: end;";
            newItem.innerHTML = `
                <div>
                    <label>Menu Item</label>
                    <select name="items[${itemCount}][menu_item_id]" class="form-control menu-item-select">
                        <option value="">Select Item</option>
                ' . $this->renderMenuItemOptions() . '
                    </select>
                </div>
                <div>
                    <label>Quantity</label>
                    <input type="number" name="items[${itemCount}][quantity]" value="1" min="1" class="form-control">
                </div>
                <div>
                    <label>Price</label>
                    <input type="text" name="items[${itemCount}][price]" readonly class="form-control">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger" onclick="removeOrderItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        function removeOrderItem(button) {
            button.closest(".order-item").remove();
        }
        
        // Add event listeners for menu item selection
        document.addEventListener("change", function(e) {
            if (e.target.classList.contains("menu-item-select")) {
                const menuItemId = e.target.value;
                const priceInput = e.target.closest(".order-item").querySelector("input[readonly]");
                
                if (menuItemId) {
                    fetch(`../api/menu.php?action=get_price&id=${menuItemId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                priceInput.value = data.price;
                            }
                        });
                } else {
                    priceInput.value = "";
                }
            }
        });
        </script>';
    }
    
    private function getOrders($status = 'all') {
        $sql = "SELECT o.*, b.name as branch_name, r.name as restaurant_name 
                FROM orders o 
                LEFT JOIN branches b ON o.branch_id = b.id 
                LEFT JOIN restaurants r ON o.restaurant_id = r.id";
        
        $params = [];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " WHERE o.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
            
            if ($this->currentUser['branch_id']) {
                $sql .= " AND o.branch_id = ?";
                $params[] = $this->currentUser['branch_id'];
            }
        }
        
        if ($status !== 'all') {
            $sql .= (empty($params) ? " WHERE" : " AND") . " o.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getOrderDetails($orderId) {
        $sql = "SELECT o.*, b.name as branch_name, r.name as restaurant_name 
                FROM orders o 
                LEFT JOIN branches b ON o.branch_id = b.id 
                LEFT JOIN restaurants r ON o.restaurant_id = r.id 
                WHERE o.id = ?";
        
        $params = [$orderId];
        
        if ($this->currentUser['role'] !== 'super_admin') {
            $sql .= " AND o.restaurant_id = ?";
            $params[] = $this->currentUser['restaurant_id'];
            
            if ($this->currentUser['branch_id']) {
                $sql .= " AND o.branch_id = ?";
                $params[] = $this->currentUser['branch_id'];
            }
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    private function getOrderItems($orderId) {
        $sql = "SELECT oi.*, mi.name as item_name 
                FROM order_items oi 
                JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = ?";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }
    
    private function getOrderColumns() {
        $baseColumns = [
            ['key' => 'id', 'label' => 'Order ID'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'table_number', 'label' => 'Table'],
            ['key' => 'total_amount', 'label' => 'Amount', 'format' => 'price'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ['key' => 'created_at', 'label' => 'Date', 'format' => 'date']
        ];
        
        if ($this->currentUser['role'] === 'super_admin') {
            array_splice($baseColumns, 1, 0, [['key' => 'restaurant_name', 'label' => 'Restaurant']]);
            array_splice($baseColumns, 2, 0, [['key' => 'branch_name', 'label' => 'Branch']]);
        } elseif (in_array($this->currentUser['role'], ['restaurant_owner', 'manager'])) {
            array_splice($baseColumns, 1, 0, [['key' => 'branch_name', 'label' => 'Branch']]);
        }
        
        return $baseColumns;
    }
    
    private function getOrderActions() {
        $actions = [
            ['icon' => 'fas fa-eye', 'href' => '?action=view&id={id}']
        ];
        
        if ($this->canEditOrders()) {
            $actions[] = ['icon' => 'fas fa-edit', 'href' => '?action=edit&id={id}'];
        }
        
        return $actions;
    }
    
    private function renderStatusFilter($currentStatus) {
        $statuses = ['all' => 'All', 'pending' => 'Pending', 'preparing' => 'Preparing', 'ready' => 'Ready', 'served' => 'Served', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        
        $html = '<select onchange="window.location.href=\'orders.php?status=\' + this.value" class="form-control" style="display: inline-block; width: auto; margin-right: 1rem;">';
        
        foreach ($statuses as $status => $label) {
            $selected = $status === $currentStatus ? 'selected' : '';
            $html .= '<option value="' . $status . '" ' . $selected . '>' . $label . '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    private function renderBranchOptions() {
        $branches = $this->getBranches();
        $html = '';
        
        foreach ($branches as $branch) {
            $html .= '<option value="' . $branch['id'] . '">' . htmlspecialchars($branch['name']) . '</option>';
        }
        
        return $html;
    }
    
    private function renderMenuItemOptions() {
        $menuItems = $this->getMenuItems();
        $html = '';
        
        foreach ($menuItems as $item) {
            if ($item['available']) {
                $html .= '<option value="' . $item['id'] . '" data-price="' . $item['price'] . '">' . htmlspecialchars($item['name']) . ' - ৳' . number_format($item['price'], 2) . '</option>';
            }
        }
        
        return $html;
    }
    
    private function canAddOrders() {
        return in_array($this->currentUser['role'], ['super_admin', 'restaurant_owner', 'manager', 'branch_manager', 'waiter']);
    }
    
    private function canEditOrders() {
        return in_array($this->currentUser['role'], ['super_admin', 'restaurant_owner', 'manager', 'branch_manager', 'chef', 'waiter']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = new OrdersPage();
    
    if ($_POST['action'] === 'update') {
        $orderId = $_GET['id'];
        $status = $_POST['status'];
        $paymentStatus = $_POST['payment_status'];
        $notes = $_POST['notes'];
        
        $updateData = [
            'status' => $status,
            'payment_status' => $paymentStatus,
            'notes' => $notes
        ];
        
        $updated = $page->db->update('orders', $updateData, 'id = ?', [$orderId]);
        
        if ($updated) {
            header('Location: orders.php?action=view&id=' . $orderId . '&success=1');
        } else {
            header('Location: orders.php?action=edit&id=' . $orderId . '&error=1');
        }
    } elseif ($_POST['action'] === 'create') {
        // Handle order creation logic
        header('Location: orders.php?success=1');
    }
} else {
    $page = new OrdersPage();
    $page->render();
}
?>