/**
 * Authentication and Role-based Redirect System
 * Handles user authentication and redirects to appropriate dashboards
 */

class AuthRedirect {
    constructor() {
        this.roleDashboards = {
            'super_admin': 'admin/superadmin/dashboard.php',
            'restaurant_owner': 'admin/owner/dashboard.php',
            'manager': 'admin/manager/dashboard.php',
            'branch_manager': 'admin/branch-manager/dashboard.php',
            'chef': 'admin/chef/dashboard.php',
            'waiter': 'admin/waiter/dashboard.php',
            'restaurant_staff': 'admin/restaurant-staff/dashboard.php'
        };

        this.init();
    }

    async init() {
        // Check authentication status on page load
        await this.checkAuthenticationAndRedirect();
    }

    async checkAuthenticationAndRedirect() {
        try {
            // Check if user is already authenticated
            const authResult = await APIService.getCurrentUser();
            
            if (authResult.success) {
                // User is authenticated, redirect to appropriate dashboard
                await this.redirectToRoleDashboard(authResult.user);
            } else {
                // User is not authenticated, ensure we're on login page
                this.ensureLoginPage();
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            this.ensureLoginPage();
        }
    }

    async redirectToRoleDashboard(user) {
        const currentPath = window.location.pathname;
        const isLoginPage = currentPath.includes('login.html');
        const isAuthPage = currentPath.includes('auth/');
        
        // Don't redirect if already on a dashboard page
        if (!isLoginPage && !isAuthPage && this.isOnDashboardPage(currentPath)) {
            return;
        }

        const userRole = user.role;
        const dashboardUrl = this.roleDashboards[userRole];

        if (dashboardUrl) {
            // Show loading message
            this.showLoadingRedirect(`Redirecting to ${userRole.replace('_', ' ')} dashboard...`);
            
            // Redirect to role-specific dashboard
            setTimeout(() => {
                window.location.href = dashboardUrl;
            }, 1000);
        } else {
            // Fallback to default dashboard if role not found
            console.warn(`Unknown role: ${userRole}, falling back to default dashboard`);
            setTimeout(() => {
                window.location.href = 'admin/dashboard.php';
            }, 1000);
        }
    }

    isOnDashboardPage(path) {
        return Object.values(this.roleDashboards).some(dashboard => 
            path.includes(dashboard) || path.includes('dashboard.php')
        );
    }

    ensureLoginPage() {
        const currentPath = window.location.pathname;
        const isLoginPage = currentPath.includes('login.html');
        const isAuthPage = currentPath.includes('auth/');
        
        // If not on login page and not on auth pages, redirect to login
        if (!isLoginPage && !isAuthPage) {
            window.location.href = 'login.html';
        }
    }

    showLoadingRedirect(message) {
        // Create loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'auth-loading-overlay';
        loadingOverlay.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.95);
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                z-index: 9999;
                font-family: system-ui, -apple-system, sans-serif;
            ">
                <div class="spinner"></div>
                <p style="margin-top: 1rem; color: #374151; font-size: 1rem; font-weight: 500;">${message}</p>
            </div>
        `;

        // Add spinner styles if not already present
        if (!document.querySelector('#auth-spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'auth-spinner-styles';
            style.textContent = `
                .spinner {
                    width: 40px;
                    height: 40px;
                    border: 4px solid #e5e7eb;
                    border-top: 4px solid #3b82f6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(loadingOverlay);
    }

    // Static method for manual role-based redirection
    static redirectToDashboard(userRole) {
        const roleDashboards = {
            'super_admin': 'admin/superadmin/dashboard.php',
            'restaurant_owner': 'admin/owner/dashboard.php',
            'manager': 'admin/manager/dashboard.php',
            'branch_manager': 'admin/branch-manager/dashboard.php',
            'chef': 'admin/chef/dashboard.php',
            'waiter': 'admin/waiter/dashboard.php',
            'restaurant_staff': 'admin/restaurant-staff/dashboard.php'
        };

        const dashboardUrl = roleDashboards[userRole] || 'admin/dashboard.php';
        return dashboardUrl;
    }

    // Static method for login handling
    static async handleLoginSuccess(userData) {
        const dashboardUrl = AuthRedirect.redirectToDashboard(userData.role);
        
        // Show success message
        if (typeof Utils !== 'undefined' && Utils.showToast) {
            Utils.showToast('Login successful!', 'success');
            Utils.showToast(`Redirecting to ${userData.role.replace('_', ' ')} dashboard...`, 'info');
        }

        // Redirect after a short delay
        setTimeout(() => {
            window.location.href = dashboardUrl;
        }, 1500);
    }

    // Static method for logout handling
    static async handleLogout() {
        try {
            await APIService.logout();
            
            if (typeof Utils !== 'undefined' && Utils.showToast) {
                Utils.showToast('Logged out successfully', 'success');
            }
            
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1000);
        } catch (error) {
            console.error('Logout failed:', error);
            
            if (typeof Utils !== 'undefined' && Utils.showToast) {
                Utils.showToast('Logout failed', 'error');
            }
            
            // Force redirect anyway
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1000);
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Make AuthRedirect available globally
    window.AuthRedirect = AuthRedirect;
    
    // Initialize the auth redirect system
    window.authRedirect = new AuthRedirect();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthRedirect;
}