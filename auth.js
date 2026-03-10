// auth.js - ПОЛНАЯ ВЕРСИЯ с запомнить меня и восстановлением пароля

class AuthSystem {
    constructor() {
        this.currentUser = null;
        this.resetPhone = null;
        this.cartCount = 0; // Добавляем свойство для хранения количества
        this.init();
    }

    async init() {
        await this.checkSession();
        await this.checkRememberMe();
        this.setupLoginButton();
    }

    async getCartCount() {
        if (!this.currentUser) return 0;
        
        try {
            const response = await fetch('cart_actions.php?action=get');
            const data = await response.json();
            this.cartCount = data.count || 0; // Сохраняем в свойство
            return this.cartCount;
        } catch(e) {
            console.error('Ошибка получения корзины:', e);
            return 0;
        }
    }

    async checkSession() {
        try {
            const response = await fetch('check_session.php');
            const data = await response.json();
            
            if (data.logged_in) {
                this.currentUser = {
                    id: data.id,
                    name: data.name,
                    role: data.role,
                    email: data.email,
                    phone: data.phone
                };
                this.updateHeader();
                
                // Получаем количество товаров в корзине
                await this.getCartCount();
            }
        } catch(e) {
            console.error('Ошибка проверки сессии:', e);
        }
    }

    async checkRememberMe() {
        const token = localStorage.getItem('remember_token');
        if (token && !this.currentUser) {
            try {
                const response = await fetch('remember_login.php');
                const data = await response.json();
                if (data.logged_in) {
                    window.location.reload();
                }
            } catch(e) {
                console.error('Ошибка remember me:', e);
            }
        }
    }

    updateHeader() {
        const loginBtn = document.querySelector('.login');
        if (!loginBtn) return;

        if (this.currentUser) {
            const shortName = this.currentUser.name.length > 10 
                ? this.currentUser.name.substring(0, 8) + '..' 
                : this.currentUser.name;
            
            if (this.currentUser.role === 'admin') {
                loginBtn.innerHTML = `<i class="fa-solid fa-crown"></i> ${shortName}`;
                loginBtn.classList.add('admin');
            } else {
                loginBtn.innerHTML = `<i class="fa-solid fa-user"></i> ${shortName}`;
                loginBtn.classList.remove('admin');
            }
        } else {
            loginBtn.innerHTML = 'Войти';
            loginBtn.classList.remove('admin');
        }
    }

    setupLoginButton() {
        const loginBtn = document.querySelector('.login');
        if (loginBtn) {
            loginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.currentUser) {
                    this.showUserMenu();
                } else {
                    this.showAuthModal();
                }
            });
        }
    }

    async login(login, password, remember = false) {
        const formData = new FormData();
        formData.append('login', login);
        formData.append('password', password);
        formData.append('remember', remember);

        try {
            const response = await fetch('login_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                if (remember && data.remember_token) {
                    localStorage.setItem('remember_token', data.remember_token);
                }
                
                this.showSuccess('Успешный вход!');
                
                setTimeout(() => {
                    if (data.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else {
                        window.location.reload();
                    }
                }, 1000);
            } else {
                this.showError(data.message);
            }
        } catch(error) {
            this.showError('Ошибка соединения');
        }
    }

    async register(userData) {
        const formData = new FormData();
        formData.append('full_name', userData.full_name);
        formData.append('phone', userData.phone);
        formData.append('email', userData.email);
        formData.append('password', userData.password);

        try {
            const response = await fetch('register_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Регистрация успешна!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                this.showError(data.message);
            }
        } catch(error) {
            this.showError('Ошибка соединения');
        }
    }

    async forgotPassword(phone) {
        const formData = new FormData();
        formData.append('phone', phone);

        try {
            const response = await fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                this.resetPhone = phone;
                this.showSuccess(data.message);
                if (data.debug_code) {
                    console.log('Код восстановления:', data.debug_code);
                }
                return true;
            } else {
                this.showError(data.message);
                return false;
            }
        } catch(error) {
            this.showError('Ошибка соединения');
            return false;
        }
    }

    async resetPassword(code, newPassword, confirmPassword) {
        if (newPassword !== confirmPassword) {
            this.showError('Пароли не совпадают');
            return false;
        }

        if (newPassword.length < 6) {
            this.showError('Пароль минимум 6 символов');
            return false;
        }

        const formData = new FormData();
        formData.append('phone', this.resetPhone);
        formData.append('code', code);
        formData.append('new_password', newPassword);

        try {
            const response = await fetch('reset_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Пароль изменен! Войдите с новым паролем');
                setTimeout(() => this.switchTab('login'), 2000);
                return true;
            } else {
                this.showError(data.message);
                return false;
            }
        } catch(error) {
            this.showError('Ошибка соединения');
            return false;
        }
    }

    async logout() {
        localStorage.removeItem('remember_token');
        await fetch('logout.php');
        this.currentUser = null;
        this.cartCount = 0;
        this.updateHeader();
        this.closeUserMenu();
        window.location.reload();
    }

    showAuthModal() {
        this.closeModal();
        
        const modalHTML = `
            <div class="modal active" id="authModal">
                <div class="modal-content auth-modal">
                    <button class="close-modal" onclick="auth.closeModal()">✕</button>
                    
                    <div class="auth-tabs">
                        <button class="auth-tab active" data-tab="login">Вход</button>
                        <button class="auth-tab" data-tab="register">Регистрация</button>
                    </div>
                    
                    <div id="authMessage" class="auth-message"></div>
                    
                    <!-- ФОРМА ВХОДА -->
                    <form id="loginForm" class="auth-form active">
                        <div class="form-group">
                            <input type="text" id="loginLogin" name="login" required 
                                   placeholder="Email или номер телефона">
                        </div>
                        
                        <div class="form-group password-field">
                            <input type="password" id="loginPassword" name="password" required 
                                   placeholder="Пароль">
                            <button type="button" class="toggle-password" onclick="auth.togglePassword('loginPassword')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" id="rememberMe" name="remember">
                                <span>Запомнить меня</span>
                            </label>
                            <a href="#" class="forgot-link" onclick="auth.showForgotForm(); return false;">
                                Забыли пароль?
                            </a>
                        </div>
                        
                        <button type="submit" class="auth-btn">Войти</button>
                        
                        <!-- ПОДСКАЗКА ДЛЯ АДМИНА -->
                        <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 10px; font-size: 0.9rem;">
                            <p style="margin-bottom: 5px;"><strong style="color: #d8737f;">🔐 Данные для входа:</strong></p>
                            <p style="margin: 3px 0;">👑 <strong>Админ:</strong> admin@citytort.by / Admin123!</p>
                            <p style="margin: 3px 0;">👤 <strong>Тестовый пользователь:</strong> +375291111111 / 123456 </p>
                        </div>
                    </form>
                    
                    <!-- ФОРМА РЕГИСТРАЦИИ -->
                    <form id="registerForm" class="auth-form">
                        <div class="form-group">
                            <input type="text" id="regName" name="full_name" required 
                                   placeholder="Ваше имя и фамилия">
                        </div>
                        <div class="form-group">
                            <input type="tel" id="regPhone" name="phone" required 
                                   placeholder="Номер телефона">
                        </div>
                        <div class="form-group">
                            <input type="email" id="regEmail" name="email" required 
                                   placeholder="Email">
                        </div>
                        <div class="form-group password-field">
                            <input type="password" id="regPassword" name="password" required 
                                   placeholder="Пароль (мин. 6 символов)">
                            <button type="button" class="toggle-password" onclick="auth.togglePassword('regPassword')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-group password-field">
                            <input type="password" id="regConfirmPassword" name="confirm_password" required 
                                   placeholder="Подтвердите пароль">
                            <button type="button" class="toggle-password" onclick="auth.togglePassword('regConfirmPassword')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <button type="submit" class="auth-btn">Зарегистрироваться</button>
                    </form>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.setupModalHandlers();
    }

    showForgotForm() {
        this.closeModal();
        
        const forgotHTML = `
            <div class="modal active" id="authModal">
                <div class="modal-content auth-modal">
                    <button class="close-modal" onclick="auth.closeModal()">✕</button>
                    
                    <h3 style="text-align: center; margin-bottom: 20px; color: #d8737f; font-size: 1.5rem;">
                        <i class="fa-solid fa-key"></i> Восстановление пароля
                    </h3>
                    
                    <div id="authMessage" class="auth-message"></div>
                    
                    <!-- ШАГ 1: Ввод телефона -->
                    <div id="forgotStep1">
                        <div class="reset-info">
                            <i class="fa-solid fa-info-circle"></i>
                            Введите номер телефона, который вы использовали при регистрации.
                            Мы отправим код подтверждения.
                        </div>
                        
                        <div class="form-group">
                            <input type="tel" id="forgotPhone" 
                                   placeholder="+375 (29) XXX-XX-XX" 
                                   required
                                   style="width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 12px; padding: 0 12px; font-family: 'Montserrat', sans-serif; font-size: 0.95rem;">
                        </div>
                        
                        <button class="auth-btn" onclick="auth.sendResetCode()">
                            <i class="fa-solid fa-paper-plane"></i> Получить код
                        </button>
                    </div>
                    
                    <!-- ШАГ 2: Ввод кода и нового пароля -->
                    <div id="forgotStep2" style="display: none;">
                        <div class="reset-info">
                            <i class="fa-solid fa-mobile-screen"></i>
                            Код отправлен на номер <strong id="displayPhone"></strong>
                        </div>
                        
                        <div class="form-group">
                            <input type="text" id="resetCode" 
                                   placeholder="000000" 
                                   maxlength="6"
                                   style="width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 12px; padding: 0 12px; font-family: 'Montserrat', sans-serif; font-size: 1.5rem; text-align: center; letter-spacing: 8px; font-weight: 600;">
                        </div>
                        
                        <div class="form-group password-field">
                            <input type="password" id="newPassword" 
                                   placeholder="Новый пароль (мин. 6 символов)"
                                   style="width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 12px; padding: 0 12px; font-family: 'Montserrat', sans-serif; font-size: 0.95rem;">
                            <button type="button" class="toggle-password" onclick="auth.togglePassword('newPassword')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-group password-field">
                            <input type="password" id="confirmNewPassword" 
                                   placeholder="Подтвердите новый пароль"
                                   style="width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 12px; padding: 0 12px; font-family: 'Montserrat', sans-serif; font-size: 0.95rem;">
                            <button type="button" class="toggle-password" onclick="auth.togglePassword('confirmNewPassword')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <button class="auth-btn" onclick="auth.confirmResetCode()">
                            <i class="fa-solid fa-check"></i> Сменить пароль
                        </button>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="#" onclick="document.getElementById('forgotStep1').style.display='block'; document.getElementById('forgotStep2').style.display='none'; return false;">
                                <i class="fa-solid fa-arrow-left"></i> Отправить код повторно
                            </a>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                        <a href="#" onclick="auth.showAuthModal(); return false;">
                            <i class="fa-solid fa-arrow-left"></i> Вернуться ко входу
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', forgotHTML);
    }

    async sendResetCode() {
        const phone = document.getElementById('forgotPhone').value;
        if (!phone) {
            this.showError('Введите номер телефона');
            return;
        }
        
        document.getElementById('displayPhone').textContent = phone;
        
        const success = await this.forgotPassword(phone);
        if (success) {
            document.getElementById('forgotStep1').style.display = 'none';
            document.getElementById('forgotStep2').style.display = 'block';
        }
    }

    async confirmResetCode() {
        const code = document.getElementById('resetCode').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        await this.resetPassword(code, newPassword, confirmPassword);
    }

    setupModalHandlers() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const login = document.getElementById('loginLogin').value;
                const password = document.getElementById('loginPassword').value;
                const remember = document.getElementById('rememberMe')?.checked || false;
                this.login(login, password, remember);
            });
        }

        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const password = document.getElementById('regPassword').value;
                const confirm = document.getElementById('regConfirmPassword').value;
                
                if (password !== confirm) {
                    this.showError('Пароли не совпадают');
                    return;
                }
                
                const userData = {
                    full_name: document.getElementById('regName').value,
                    phone: document.getElementById('regPhone').value,
                    email: document.getElementById('regEmail').value,
                    password: password
                };
                
                this.register(userData);
            });
        }

        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                document.getElementById(tabName + 'Form').classList.add('active');
            });
        });
    }

    togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

 // ИСПРАВЛЕННАЯ ФУНКЦИЯ showUserMenu
showUserMenu() {
    // Используем сохранённое значение cartCount
    const cartCount = this.cartCount || 0;
    
    const menuHTML = `
        <div class="user-menu-overlay" onclick="auth.closeUserMenu()"></div>
        <div class="user-menu-modal">
            <div class="user-menu-header">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-circle"></i>
                </div>
                <div class="user-info">
                    <h4>${this.currentUser.name}</h4>
                    <p>${this.currentUser.email}</p>
                    ${this.currentUser.role === 'admin' ? 
                      '<span class="admin-badge">Администратор</span>' : 
                      '<span class="user-badge">Покупатель</span>'}
                </div>
                <button class="close-menu-btn" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <div class="user-menu-items">
                <!-- Активные заказы -->
                <a href="profile.php?tab=active" class="user-menu-item" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-box"></i>
                    <span>Активные заказы</span>
                </a>
                
                <!-- История заказов -->
                <a href="profile.php?tab=history" class="user-menu-item" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>История заказов</span>
                </a>
                
                <!-- Корзина -->
                <a href="profile.php?tab=cart" class="user-menu-item" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-shopping-cart"></i>
                    <span>Корзина</span>
                    ${cartCount > 0 ? `<span class="cart-badge">${cartCount}</span>` : ''}
                </a>
                
                <!-- Настройки -->
                <a href="profile.php?tab=settings" class="user-menu-item" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-gear"></i>
                    <span>Настройки</span>
                </a>
                
                ${this.currentUser.role === 'admin' ? `
                <div class="menu-divider"></div>
                <a href="admin.php" class="user-menu-item admin-item" onclick="auth.closeUserMenu()">
                    <i class="fa-solid fa-cog"></i>
                    <span>Панель управления</span>
                </a>
                ` : ''}
                
                <div class="menu-divider"></div>
                
                <button class="user-menu-item logout-btn" onclick="auth.logout()">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Выйти</span>
                </button>
            </div>
        </div>
    `;

    this.closeUserMenu();
    document.body.insertAdjacentHTML('beforeend', menuHTML);
    
    // Добавляем стили для бейджика корзины (если ещё не добавлены)
    if (!document.getElementById('cart-badge-style')) {
        const style = document.createElement('style');
        style.id = 'cart-badge-style';
        style.textContent = `
            .user-menu-item {
                position: relative;
            }
            .cart-badge {
                position: absolute;
                right: 20px;
                background: #d8737f;
                color: white;
                border-radius: 50%;
                width: 22px;
                height: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8rem;
                font-weight: bold;
            }
        `;
        document.head.appendChild(style);
    }
}

    closeUserMenu() {
        document.querySelector('.user-menu-overlay')?.remove();
        document.querySelector('.user-menu-modal')?.remove();
    }

    closeModal() {
        document.getElementById('authModal')?.remove();
    }

    showError(message) {
        const messageEl = document.getElementById('authMessage');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.className = 'auth-message error';
            messageEl.style.display = 'block';
        } else {
            alert('Ошибка: ' + message);
        }
    }

    showSuccess(message) {
        const messageEl = document.getElementById('authMessage');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.className = 'auth-message success';
            messageEl.style.display = 'block';
        } else {
            alert(message);
        }
    }

    switchTab(tabName) {
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
        
        document.querySelector(`.auth-tab[data-tab="${tabName}"]`)?.classList.add('active');
        document.getElementById(`${tabName}Form`)?.classList.add('active');
    }

    // ===== НОВЫЕ МЕТОДЫ (ВНУТРИ КЛАССА) =====
    showAuthModalWithMessage(message = 'Для оформления заказа необходимо войти в аккаунт') {
        this.closeModal();
            // Убедимся, что нет других активных модалок
    document.querySelectorAll('.modal.active').forEach(modal => {
        modal.classList.remove('active');
    });
        const modalHTML = `
            <div class="modal active" id="authModal">
                <div class="modal-content auth-modal" style="max-width: 400px;">
                    <button class="close-modal" onclick="auth.closeModal()">✕</button>
                    
                    <div style="text-align: center; margin-bottom: 25px;">
                        <i class="fa-solid fa-lock" style="font-size: 3rem; color: #d8737f; margin-bottom: 15px;"></i>
                        <h3 style="color: #333; margin-bottom: 10px;">Необходима авторизация</h3>
                        <p style="color: #666; font-size: 0.95rem; line-height: 1.5;">${message}</p>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <button class="auth-btn" onclick="auth.switchToLoginTab()" style="flex: 1;">
                            <i class="fa-solid fa-right-to-bracket"></i> Войти
                        </button>
                        <button class="auth-btn" onclick="auth.switchToRegisterTab()" style="flex: 1; background: #28a745;">
                            <i class="fa-solid fa-user-plus"></i> Регистрация
                        </button>
                    </div>
                    
                    <div style="text-align: center; color: #999; font-size: 0.85rem;">
                        <i class="fa-regular fa-circle-check"></i> После входа вы сможете оформить заказ
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    switchToLoginTab() {
        this.closeModal();
        this.showAuthModal();
        // Активируем таб входа через небольшую задержку
        setTimeout(() => {
            const loginTab = document.querySelector('.auth-tab[data-tab="login"]');
            if (loginTab) loginTab.click();
        }, 100);
    }

    switchToRegisterTab() {
        this.closeModal();
        this.showAuthModal();
        setTimeout(() => {
            const registerTab = document.querySelector('.auth-tab[data-tab="register"]');
            if (registerTab) registerTab.click();
        }, 100);
    }
}

// Глобальная функция проверки авторизации
window.requireAuth = function(callback, message = null) {
    if (window.auth && window.auth.currentUser) {
        callback(); // пользователь авторизован - выполняем действие
    } else {
        // пользователь не авторизован - показываем модалку с сообщением
        if (message) {
            window.auth.showAuthModalWithMessage(message);
        } else {
            window.auth.showAuthModalWithMessage('Для оформления заказа необходимо войти в аккаунт');
        }
    }
};

window.auth = new AuthSystem();