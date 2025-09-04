# QR Menu System - Admin Panel

## Overview

The admin panel has been completely restructured with a role-based directory system to provide better organization, security, and user experience. Each role now has its dedicated dashboard and management pages tailored to their specific responsibilities and permissions.

## Directory Structure

```
admin/
├── README.md                           # This documentation file
├── index.php                          # Main router - redirects users to appropriate role dashboard
├── dashboard.php                      # Generic dashboard (fallback)
├── orders.php                         # Order management (shared by all roles)
├── menu.php                           # Menu management (shared by all roles)
├── users.php                          # User management (shared by all roles)
├── branches.php                       # Branch management (shared by all roles)
├── restaurants.php                    # Restaurant management (shared by all roles)
├── includes/
│   └── BaseDashboard.php              # Base dashboard class with common functionality
├── templates/
│   └── layout.php                    # Main layout template with navigation and header
├── api/
│   ├── branches.php                  # API endpoint for branch data
│   └── menu.php                      # API endpoint for menu data
├── superadmin/                       # Super Admin role directory
│   └── dashboard.php                 # Super Admin dashboard
├── owner/                            # Restaurant Owner role directory
│   └── dashboard.php                 # Restaurant Owner dashboard
├── manager/                          # Manager role directory
│   └── dashboard.php                 # Manager dashboard
├── branch-manager/                   # Branch Manager role directory
│   └── dashboard.php                 # Branch Manager dashboard
├── chef/                             # Chef role directory
│   └── dashboard.php                 # Chef dashboard
├── waiter/                           # Waiter role directory
│   └── dashboard.php                 # Waiter dashboard
└── restaurant-staff/                 # Restaurant Staff role directory
    └── dashboard.php                 # Restaurant Staff dashboard
```

## Role-Based Access Control

### 1. Super Admin (`admin/superadmin/`)
- **Full system access** - Can manage all restaurants, branches, users, and settings
- **Dashboard Features:**
  - System-wide statistics (restaurants, branches, users, orders)
  - Recent orders across all restaurants
  - Restaurant management overview
  - User management overview
- **Permissions:** All permissions including `manage_restaurant`, `manage_branches`, `manage_menu`, `manage_orders`, `manage_staff`, `view_reports`

### 2. Restaurant Owner (`admin/owner/`)
- **Restaurant-level management** - Can manage their restaurant's branches, staff, and operations
- **Dashboard Features:**
  - Restaurant-specific statistics (branches, staff, orders, revenue)
  - Branch performance overview
  - Staff management
  - Order monitoring
- **Permissions:** `manage_branches`, `manage_menu`, `manage_orders`, `manage_staff`, `view_reports`

### 3. Manager (`admin/manager/`)
- **Operational oversight** - Can manage day-to-day operations, staff, and orders
- **Dashboard Features:**
  - Operational statistics (branches, staff, orders)
  - Branch performance monitoring
  - Staff scheduling and management
  - Order priority display
- **Permissions:** `manage_branches`, `manage_menu`, `manage_orders`, `manage_staff`, `view_reports`

### 4. Branch Manager (`admin/branch-manager/`)
- **Branch-specific management** - Can manage a specific branch's operations, staff, and menu
- **Dashboard Features:**
  - Branch-specific statistics (staff, orders, revenue)
  - Branch information display
  - Staff management for the branch
  - Menu item management
  - Active order monitoring
- **Permissions:** `manage_menu`, `manage_orders`, `manage_staff`, `view_reports`

### 5. Chef (`admin/chef/`)
- **Kitchen operations** - Can manage kitchen orders and menu items
- **Dashboard Features:**
  - Kitchen order statistics (pending, preparing, ready)
  - Active kitchen orders with priority
  - Menu item availability management
  - Kitchen performance metrics
  - Quick order status updates
- **Permissions:** `view_menu`, `manage_orders`, `view_reports`

### 6. Waiter (`admin/waiter/`)
- **Customer service** - Can manage orders, tables, and customer interactions
- **Dashboard Features:**
  - Table management and status display
  - Active orders with serving priority
  - Quick order creation and management
  - Customer order tracking
  - Payment processing
- **Permissions:** `view_menu`, `manage_orders`

### 7. Restaurant Staff (`admin/restaurant-staff/`)
- **Limited access** - Can view orders and menu information
- **Dashboard Features:**
  - Basic order viewing
  - Menu availability display
  - Order status summary
  - Quick information access
  - Daily reports
- **Permissions:** `view_menu`, `view_orders`

## Common Management Pages

### Orders Management (`admin/orders.php`)
- **Features:**
  - Order listing with status filtering
  - Order details view
  - Order editing (role-based permissions)
  - New order creation (for authorized roles)
  - Order status updates
- **Role-Specific Functionality:**
  - Chefs: Focus on kitchen status (pending, preparing, ready)
  - Waiters: Focus on table management and serving
  - Managers: Full order management with priority handling

### Menu Management (`admin/menu.php`)
- **Features:**
  - Category management
  - Menu item management
  - Item availability toggle
  - Detailed item information
  - Dietary preferences display
- **Role-Specific Functionality:**
  - Chefs/Managers: Full menu editing capabilities
  - Waiters/Staff: View-only access with availability status

### User Management (`admin/users.php`)
- **Features:**
  - User listing with role filtering
  - User details view
  - User creation and editing
  - Role assignment (restricted by permissions)
  - Restaurant/Branch assignment
- **Role-Specific Functionality:**
  - Super Admins: Full user management across all restaurants
  - Owners/Managers: User management within their restaurant/branch

### Branch Management (`admin/branches.php`)
- **Features:**
  - Branch listing and details
  - Branch creation and editing
  - Performance statistics
  - Location information
  - Staff assignment overview
- **Role-Specific Functionality:**
  - Super Admins/Owners: Full branch management
  - Managers: Branch oversight within their restaurant
  - Branch Managers: Their specific branch management

### Restaurant Management (`admin/restaurants.php`)
- **Features:**
  - Restaurant listing and details
  - Restaurant creation and editing
  - Overall statistics
  - Branch overview
  - Revenue tracking
- **Role-Specific Functionality:**
  - Super Admins only: Complete restaurant management

## API Endpoints

### Branches API (`admin/api/branches.php`)
- **Endpoints:**
  - `GET /admin/api/branches.php?endpoint=list` - List branches
  - `GET /admin/api/branches.php?endpoint=get&id={id}` - Get specific branch
- **Parameters:**
  - `restaurant_id` - Filter by restaurant (optional)
  - `id` - Branch ID for specific branch

### Menu API (`admin/api/menu.php`)
- **Endpoints:**
  - `GET /admin/api/menu.php?action=list` - List menu items/categories
  - `GET /admin/api/menu.php?action=get_price&id={id}` - Get menu item price
  - `GET /admin/api/menu.php?action=get&id={id}` - Get specific menu item
- **Parameters:**
  - `restaurant_id` - Filter by restaurant (optional)
  - `category_id` - Filter by category (optional)
  - `type` - 'categories' or 'items' (default: 'items')
  - `id` - Menu item ID for specific item

## Navigation and Routing

### Main Router (`admin/index.php`)
- Automatically redirects users to their role-specific dashboard
- Handles authentication and authorization
- Ensures users only access permitted areas

### Layout Template (`admin/templates/layout.php`)
- Provides consistent header, navigation, and footer
- Role-based navigation menu filtering
- User information display
- Quick action buttons

### Base Dashboard (`admin/includes/BaseDashboard.php`)
- Common functionality for all dashboards
- Database operations
- Permission checking
- Statistics calculation
- Data table rendering
- JSON response handling

## Security Features

### Authentication
- Session-based authentication
- Automatic logout on session expiry
- Login attempt tracking and blocking

### Authorization
- Role-based access control
- Permission checking for all operations
- Data filtering based on user role and restaurant/branch assignment

### Input Validation
- Form validation for all user inputs
- SQL injection prevention
- XSS protection
- CSRF protection (recommended for forms)

## Usage Instructions

### Accessing the Admin Panel
1. Navigate to `/admin/` in your browser
2. You will be automatically redirected to your role-specific dashboard
3. Use the navigation menu to access different management pages

### Common Operations
- **Viewing Statistics:** Each dashboard shows role-relevant statistics
- **Managing Orders:** Use the orders page for all order-related operations
- **Managing Menu:** Use the menu page for category and item management
- **Managing Users:** Use the users page for staff management
- **Managing Branches:** Use the branches page for branch operations

### Role-Specific Workflows
- **Super Admin:** Create restaurants, manage system-wide settings
- **Restaurant Owner:** Add branches, manage restaurant staff
- **Manager:** Oversee operations, manage staff schedules
- **Branch Manager:** Handle branch-specific operations, local staff
- **Chef:** Monitor kitchen orders, update menu availability
- **Waiter:** Take orders, manage tables, process payments
- **Restaurant Staff:** View orders, check menu availability

## Customization

### Adding New Roles
1. Update the `roles` table in the database
2. Add role permissions in the database
3. Create role directory in `/admin/`
4. Create role-specific dashboard
5. Update routing logic in `index.php`

### Adding New Permissions
1. Update permissions in the `roles` table
2. Add permission checks in relevant PHP files
3. Update navigation menu logic in layout template

### Customizing Dashboards
1. Edit role-specific dashboard files
2. Modify statistics calculation in BaseDashboard
3. Update data table columns and actions
4. Customize quick action buttons

## Troubleshooting

### Common Issues
- **Access Denied:** Check user role and permissions
- **Blank Pages:** Verify file paths and includes
- **Database Errors:** Check database connection and queries
- **Navigation Issues:** Verify role-based navigation logic

### Debug Mode
- Enable error reporting in `config/config.php`
- Check browser console for JavaScript errors
- Review server error logs

## Support

For technical support or questions about the admin panel:
1. Check this documentation
2. Review the code comments
3. Contact the system administrator

---

**Note:** This admin panel is designed to work with standard web servers that support PHP. No Node.js or other JavaScript frameworks are required, making it compatible with most shared hosting environments.