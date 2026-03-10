// Система уведомлений
const NotificationSystem = {
    show(message, type = 'success', duration = 3000) {
        // Создаём уведомление
        const notification = document.createElement('div');
        notification.className = 'notification';
        
        let icon = 'fa-check';
        let bgColor = '#d4edda';
        let iconColor = '#28a745';
        
        if (type === 'error') {
            icon = 'fa-exclamation-circle';
            bgColor = '#f8d7da';
            iconColor = '#dc3545';
        } else if (type === 'info') {
            icon = 'fa-info-circle';
            bgColor = '#cce5ff';
            iconColor = '#17a2b8';
        }
        
        notification.innerHTML = `
            <div class="notification-icon" style="background: ${bgColor}; color: ${iconColor};">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${type === 'success' ? 'Успешно!' : type === 'error' ? 'Ошибка' : 'Информация'}</div>
                <div class="notification-text">${message}</div>
            </div>
            <div class="notification-close" onclick="this.parentElement.remove()">
                <i class="fa-solid fa-times"></i>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Показываем с анимацией
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Автоматически скрываем через duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
};

// Глобальная функция для вызова
function showNotification(message, type = 'success') {
    NotificationSystem.show(message, type);
}