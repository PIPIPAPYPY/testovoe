/**
 * Основной JavaScript файл приложения
 * Оптимизирован для производительности и следует современным стандартам
 */

class TaskManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Делегирование событий для лучшей производительности
        document.addEventListener('click', this.handleClick.bind(this));
        document.addEventListener('submit', this.handleSubmit.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    handleClick(event) {
        const target = event.target;
        
        // Модальные окна
        if (target.matches('[data-modal-open]')) {
            this.openModal(target.dataset.modalOpen);
        }
        
        if (target.matches('[data-modal-close]')) {
            this.closeModal();
        }
        
        // Изменение статуса задач
        if (target.matches('[data-task-status]')) {
            this.changeTaskStatus(
                target.dataset.taskId,
                target.dataset.taskStatus
            );
        }
    }

    handleSubmit(event) {
        if (event.target.matches('#addTaskForm')) {
            event.preventDefault();
            this.submitTaskForm(event.target);
        }
    }

    handleKeydown(event) {
        // Закрытие модального окна по Escape
        if (event.key === 'Escape') {
            this.closeModal();
        }
    }

    initializeComponents() {
        this.initializeAnimations();
        this.initializeSearch();
    }

    initializeAnimations() {
        // Анимация появления карточек с оптимизацией
        const cards = document.querySelectorAll('.task-card');
        if (cards.length > 0) {
            this.animateCards(cards);
        }
    }

    animateCards(cards) {
        // Используем requestAnimationFrame для плавной анимации
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            requestAnimationFrame(() => {
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    }

    initializeSearch() {
        const searchInput = document.querySelector('#search');
        if (searchInput) {
            // Debounced search для оптимизации
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const form = e.target.closest('form');
                
                e.target.style.background = '#f0f8ff';
                
                searchTimeout = setTimeout(() => {
                    e.target.style.background = '';
                    if (e.target.value.length >= 2 || e.target.value.length === 0) {
                        form.submit();
                    }
                }, 800);
            });
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Фокус на первое поле ввода
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal() {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Очистка формы
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }
    }

    async submitTaskForm(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Показываем загрузку
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>Создание...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            const taskData = Object.fromEntries(formData.entries());

            const response = await this.fetchWithTimeout('/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(taskData)
            });

            if (response.ok) {
                this.showNotification('Задача успешно создана!', 'success');
                this.closeModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error('Ошибка при создании задачи');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            this.showNotification(error.message || 'Не удалось создать задачу', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async changeTaskStatus(taskId, newStatus) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;

        taskCard.classList.add('updating');

        try {
            const response = await this.fetchWithTimeout(`/tasks/${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: newStatus })
            });

            if (response.ok) {
                this.showNotification('Статус задачи обновлен!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error('Ошибка при обновлении статуса');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            this.showNotification('Не удалось обновить статус задачи', 'error');
        } finally {
            taskCard.classList.remove('updating');
        }
    }

    async fetchWithTimeout(url, options, timeout = 10000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    showNotification(message, type = 'success') {
        // Удаляем существующие уведомления
        const existing = document.querySelectorAll('.notification');
        existing.forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            font-weight: 600;
            animation: slideIn 0.3s ease;
            background: ${type === 'success' ? 'linear-gradient(135deg, #00b894, #00a085)' : 'linear-gradient(135deg, #e17055, #d63031)'};
            color: white;
        `;
        
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }, 2500);
    }

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    new TaskManager();
});

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TaskManager;
}