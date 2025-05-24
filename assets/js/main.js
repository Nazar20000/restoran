// Основной JavaScript файл для ресторанного приложения

class RestaurantApp {
    constructor() {
        this.cart = new Cart();
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateCartDisplay();
        this.initModals();
    }

    bindEvents() {
        // Добавление в корзину
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                e.preventDefault();
                const dishId = e.target.dataset.dishId;
                const quantity = parseInt(e.target.dataset.quantity) || 1;
                this.cart.addItem(dishId, quantity);
                this.showNotification('Блюдо добавлено в корзину!', 'success');
            }

            // Удаление из корзины
            if (e.target.classList.contains('remove-from-cart')) {
                e.preventDefault();
                const dishId = e.target.dataset.dishId;
                this.cart.removeItem(dishId);
                this.updateCartDisplay();
                this.showNotification('Блюдо удалено из корзины', 'info');
            }

            // Обновление количества
            if (e.target.classList.contains('quantity-btn')) {
                e.preventDefault();
                const dishId = e.target.dataset.dishId;
                const action = e.target.dataset.action;
                const currentQty = this.cart.getItemQuantity(dishId);
                
                if (action === 'increase') {
                    this.cart.updateQuantity(dishId, currentQty + 1);
                } else if (action === 'decrease' && currentQty > 1) {
                    this.cart.updateQuantity(dishId, currentQty - 1);
                }
                
                this.updateCartDisplay();
            }

            // Закрытие модальных окон
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal')) {
                this.closeModal();
            }
        });

        // Обработка форм
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleAjaxForm(e.target);
            }
        });

        // Обновление количества в корзине при изменении input
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('quantity-input')) {
                const dishId = e.target.dataset.dishId;
                const quantity = parseInt(e.target.value) || 0;
                this.cart.updateQuantity(dishId, quantity);
                this.updateCartDisplay();
            }
        });
    }

    initModals() {
        // Инициализация модальных окон
        const modalTriggers = document.querySelectorAll('[data-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                this.openModal(modalId);
            });
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }

    updateCartDisplay() {
        const cartCount = document.querySelector('.cart-count');
        const cartItems = document.querySelector('.cart-items');
        const cartTotal = document.querySelector('.cart-total');
        
        const itemCount = this.cart.getItemCount();
        
        if (cartCount) {
            cartCount.textContent = itemCount;
            cartCount.style.display = itemCount > 0 ? 'flex' : 'none';
        }

        if (cartItems) {
            this.renderCartItems();
        }

        if (cartTotal) {
            this.updateCartTotal();
        }
    }

    renderCartItems() {
        const cartItems = document.querySelector('.cart-items');
        if (!cartItems) return;

        const items = this.cart.getItems();
        
        if (Object.keys(items).length === 0) {
            cartItems.innerHTML = '<p class="text-center">Корзина пуста</p>';
            return;
        }

        // Здесь должен быть AJAX запрос для получения данных о блюдах
        this.fetchCartItemsData(items).then(data => {
            let html = '';
            data.forEach(item => {
                html += this.renderCartItem(item);
            });
            cartItems.innerHTML = html;
        });
    }

    renderCartItem(item) {
        return `
            <div class="cart-item" data-dish-id="${item.id}">
                <img src="/placeholder.svg?height=80&width=80&query=${item.name}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${item.price} ₽</div>
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn" data-dish-id="${item.id}" data-action="decrease">-</button>
                    <input type="number" class="quantity-input" data-dish-id="${item.id}" value="${item.quantity}" min="1">
                    <button class="quantity-btn" data-dish-id="${item.id}" data-action="increase">+</button>
                </div>
                <button class="btn btn-sm remove-from-cart" data-dish-id="${item.id}">Удалить</button>
            </div>
        `;
    }

    async fetchCartItemsData(items) {
        try {
            const response = await fetch('api/cart-items.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ items: items })
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error fetching cart items:', error);
            return [];
        }
    }

    async updateCartTotal() {
        const cartTotal = document.querySelector('.cart-total');
        if (!cartTotal) return;

        try {
            const items = this.cart.getItems();
            const response = await fetch('api/cart-total.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ items: items })
            });
            
            const data = await response.json();
            cartTotal.textContent = `Итого: ${data.total} ₽`;
        } catch (error) {
            console.error('Error updating cart total:', error);
        }
    }

    async handleAjaxForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Показать загрузку
        submitBtn.innerHTML = '<span class="loading"></span> Загрузка...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(result.message, 'success');
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
                if (result.reset_form) {
                    form.reset();
                }
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('Произошла ошибка. Попробуйте снова.', 'error');
        } finally {
            // Восстановить кнопку
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 3000;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Методы для работы с датами (для бронирования поваров)
    initDatePicker() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Установить минимальную дату - завтра
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            input.min = tomorrow.toISOString().split('T')[0];
        });
    }

    // Валидация форм
    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Это поле обязательно для заполнения');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        // Валидация email
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Введите корректный email адрес');
                isValid = false;
            }
        });

        // Валидация телефона
        const phoneFields = form.querySelectorAll('input[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !this.isValidPhone(field.value)) {
                this.showFieldError(field, 'Введите корректный номер телефона');
                isValid = false;
            }
        });

        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = '#721c24';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#721c24';
    }

    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.style.borderColor = '#e1e8ed';
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-$$$$]/g, ''));
    }
}

// Класс для работы с корзиной
class Cart {
    constructor() {
        this.items = this.loadFromStorage();
    }

    addItem(dishId, quantity = 1) {
        if (this.items[dishId]) {
            this.items[dishId] += quantity;
        } else {
            this.items[dishId] = quantity;
        }
        this.saveToStorage();
    }

    removeItem(dishId) {
        delete this.items[dishId];
        this.saveToStorage();
    }

    updateQuantity(dishId, quantity) {
        if (quantity <= 0) {
            this.removeItem(dishId);
        } else {
            this.items[dishId] = quantity;
            this.saveToStorage();
        }
    }

    getItems() {
        return this.items;
    }

    getItemQuantity(dishId) {
        return this.items[dishId] || 0;
    }

    getItemCount() {
        return Object.values(this.items).reduce((sum, qty) => sum + qty, 0);
    }

    clear() {
        this.items = {};
        this.saveToStorage();
    }

    saveToStorage() {
        localStorage.setItem('restaurant_cart', JSON.stringify(this.items));
    }

    loadFromStorage() {
        const stored = localStorage.getItem('restaurant_cart');
        return stored ? JSON.parse(stored) : {};
    }
}

// Инициализация приложения
document.addEventListener('DOMContentLoaded', () => {
    window.restaurantApp = new RestaurantApp();
    
    // Дополнительные стили для анимаций
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .notification {
            transition: all 0.3s ease;
        }
    `;
    document.head.appendChild(style);
});

// Утилиты
const Utils = {
    formatPrice: (price) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB'
        }).format(price);
    },

    formatDate: (date) => {
        return new Intl.DateTimeFormat('ru-RU').format(new Date(date));
    },

    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};