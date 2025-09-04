// Global utility functions
class Utils {
    static showToast(message, type = 'info') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create new toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    static formatPrice(price) {
        return new Intl.NumberFormat('en-BD', {
            style: 'currency',
            currency: 'BDT',
            minimumFractionDigits: 2
        }).format(price);
    }

    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-BD', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePhone(phone) {
        const re = /^(\+880|0)[1-9]\d{9}$/;
        return re.test(phone);
    }

    static sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }

    static showLoading(element) {
        element.innerHTML = '<div class="spinner"></div>';
        element.disabled = true;
    }

    static hideLoading(element, originalText) {
        element.innerHTML = originalText;
        element.disabled = false;
    }

    static async fetchAPI(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, mergedOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            Utils.showToast(error.message, 'error');
            throw error;
        }
    }

    static confirmAction(message) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal active';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Confirm Action</h3>
                        <button class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancel-btn">Cancel</button>
                        <button class="btn btn-primary" id="confirm-btn">Confirm</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            const closeBtn = modal.querySelector('.close-btn');
            const cancelBtn = modal.querySelector('#cancel-btn');
            const confirmBtn = modal.querySelector('#confirm-btn');

            const closeModal = () => {
                modal.remove();
                resolve(false);
            };

            const confirmAction = () => {
                modal.remove();
                resolve(true);
            };

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            confirmBtn.addEventListener('click', confirmAction);
        });
    }
}

// API Service class
class APIService {
    static async login(email, password) {
        return Utils.fetchAPI('/api/auth/index.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    }

    static async logout() {
        return Utils.fetchAPI('/api/auth/index.php?action=logout', {
            method: 'POST'
        });
    }

    static async checkAuth() {
        return Utils.fetchAPI('/api/auth/index.php?action=check');
    }

    static async getCurrentUser() {
        return Utils.fetchAPI('/api/auth/index.php?action=user');
    }

    static async getRestaurants() {
        return Utils.fetchAPI('/api/user/index.php?endpoint=restaurants');
    }

    static async getBranches(restaurantId) {
        return Utils.fetchAPI(`/api/user/index.php?endpoint=branches&restaurant_id=${restaurantId}`);
    }

    static async getMenu(restaurantId, branchId = null) {
        let url = `/api/user/index.php?endpoint=menu&restaurant_id=${restaurantId}`;
        if (branchId) {
            url += `&branch_id=${branchId}`;
        }
        return Utils.fetchAPI(url);
    }

    static async createOrder(orderData) {
        return Utils.fetchAPI('/api/user/index.php?endpoint=orders', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }

    static async getOrder(orderId) {
        return Utils.fetchAPI(`/api/user/index.php?endpoint=orders&order_id=${orderId}`);
    }

    static async searchMenu(restaurantId, query) {
        return Utils.fetchAPI(`/api/user/index.php?endpoint=search&restaurant_id=${restaurantId}&q=${encodeURIComponent(query)}`);
    }

    // Admin APIs
    static async getAdminRestaurants() {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=restaurants');
    }

    static async createRestaurant(restaurantData) {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=restaurants', {
            method: 'POST',
            body: JSON.stringify(restaurantData)
        });
    }

    static async updateRestaurant(id, restaurantData) {
        return Utils.fetchAPI(`/api/admin/index.php?endpoint=restaurants&id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(restaurantData)
        });
    }

    static async deleteRestaurant(id) {
        return Utils.fetchAPI(`/api/admin/index.php?endpoint=restaurants&id=${id}`, {
            method: 'DELETE'
        });
    }

    static async getAdminBranches(restaurantId = null) {
        let url = '/api/admin/index.php?endpoint=branches';
        if (restaurantId) {
            url += `&restaurant_id=${restaurantId}`;
        }
        return Utils.fetchAPI(url);
    }

    static async createBranch(branchData) {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=branches', {
            method: 'POST',
            body: JSON.stringify(branchData)
        });
    }

    static async getAdminUsers(restaurantId = null, branchId = null) {
        let url = '/api/admin/index.php?endpoint=users';
        if (restaurantId) {
            url += `&restaurant_id=${restaurantId}`;
        }
        if (branchId) {
            url += `&branch_id=${branchId}`;
        }
        return Utils.fetchAPI(url);
    }

    static async createUser(userData) {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=users', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    static async getAdminMenu(restaurantId, type = 'categories') {
        return Utils.fetchAPI(`/api/admin/index.php?endpoint=menu&restaurant_id=${restaurantId}&type=${type}`);
    }

    static async createCategory(categoryData) {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=menu&type=category', {
            method: 'POST',
            body: JSON.stringify(categoryData)
        });
    }

    static async createMenuItem(itemData) {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=menu&type=item', {
            method: 'POST',
            body: JSON.stringify(itemData)
        });
    }

    static async getAdminOrders(restaurantId = null, branchId = null, status = null) {
        let url = '/api/admin/index.php?endpoint=orders';
        if (restaurantId) {
            url += `&restaurant_id=${restaurantId}`;
        }
        if (branchId) {
            url += `&branch_id=${branchId}`;
        }
        if (status) {
            url += `&status=${status}`;
        }
        return Utils.fetchAPI(url);
    }

    static async updateOrderStatus(id, status, paymentStatus = null) {
        const data = { status };
        if (paymentStatus) {
            data.payment_status = paymentStatus;
        }
        return Utils.fetchAPI(`/api/admin/index.php?endpoint=orders&id=${id}`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async getDashboard() {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=dashboard');
    }

    static async getSettings() {
        return Utils.fetchAPI('/api/admin/index.php?endpoint=settings');
    }
}

// Form validation
class FormValidator {
    constructor(form) {
        this.form = form;
        this.errors = [];
    }

    validate() {
        this.errors = [];
        const formData = new FormData(this.form);
        
        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.errors.push(`${field.name} is required`);
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });

        const emailFields = this.form.querySelectorAll('[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !Utils.validateEmail(field.value)) {
                this.errors.push('Invalid email format');
                field.classList.add('error');
            }
        });

        const phoneFields = this.form.querySelectorAll('[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !Utils.validatePhone(field.value)) {
                this.errors.push('Invalid phone number format');
                field.classList.add('error');
            }
        });

        return this.errors.length === 0;
    }

    getErrors() {
        return this.errors;
    }

    showErrors() {
        this.errors.forEach(error => {
            Utils.showToast(error, 'error');
        });
    }
}

// Modal management
class ModalManager {
    constructor() {
        this.modals = new Map();
    }

    createModal(id, title, content, footer = '') {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = id;
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    ${footer}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modals.set(id, modal);

        const closeBtn = modal.querySelector('.close-btn');
        closeBtn.addEventListener('click', () => this.closeModal(id));

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(id);
            }
        });

        return modal;
    }

    openModal(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    removeModal(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.remove();
            this.modals.delete(id);
        }
    }
}

const modalManager = new ModalManager();

document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'tooltip';
            tooltipEl.textContent = text;
            document.body.appendChild(tooltipEl);
            
            const rect = this.getBoundingClientRect();
            tooltipEl.style.top = rect.top + 'px';
            tooltipEl.style.left = rect.left + 'px';
        });

        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.querySelector('.tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
        });
    });

    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const validator = new FormValidator(form);
            if (!validator.validate()) {
                e.preventDefault();
                validator.showErrors();
            }
        });
    });

    let logoutTimer;
    function resetLogoutTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(() => {
            if (confirm('Your session is about to expire. Do you want to stay logged in?')) {
                APIService.checkAuth().then(() => {
                    resetLogoutTimer();
                });
            } else {
                APIService.logout();
                window.location.href = '/login.html';
            }
        }, 7200000); // 2 hours
    }

    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetLogoutTimer, true);
    });

    resetLogoutTimer();
});