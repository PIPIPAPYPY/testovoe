# 🚀 Task Management System - Laravel

Современное, высокопроизводительное веб-приложение для управления задачами, построенное на Laravel с применением лучших практик разработки, SOLID принципов и современной архитектуры.

## 📋 Описание

Полнофункциональная система управления задачами с красивым веб-интерфейсом, мощным REST API и детальной аналитикой. Приложение следует принципам чистой архитектуры, использует современные паттерны проектирования и оптимизировано для высокой производительности.

### ✨ Основные возможности:

#### 🔐 Аутентификация и безопасность
- **Двойная система авторизации**: Laravel Sanctum (API токены) + веб-сессии
- **Policy-based авторизация** с детальным контролем доступа
- **Rate limiting** и CSRF защита
- **Валидация через Form Request классы**

#### 📋 Управление задачами
- **CRUD операции** с полной валидацией данных
- **Статусы задач**: К выполнению, В работе, Выполнено
- **Система приоритетов**: Высокий, Средний, Низкий
- **Дедлайны** с проверкой просроченных задач
- **Быстрое изменение статуса** с hover-эффектами

#### 🔍 Поиск и фильтрация
- **Полнотекстовый поиск** по названию и описанию
- **Многоуровневая фильтрация** по статусу, приоритету, датам
- **Умная пагинация** (12 задач на страницу)
- **Debounced search** для оптимизации

#### 📊 Аналитика и визуализация
- **Интерактивные графики** с Chart.js
- **Статистика выполнения** в реальном времени
- **Графики по категориям и тегам**
- **Анализ продуктивности** по дням недели
- **Кэширование аналитики** для быстрой загрузки

#### 🎨 Современный UI/UX
- **Адаптивный дизайн** для всех устройств
- **Модальные окна** для создания задач
- **Анимации и переходы** с CSS3
- **Темная/светлая тема** поддержка
- **Accessibility** совместимость

#### ⚡ Производительность
- **Service Layer архитектура**
- **Repository Pattern** для работы с данными
- **Умное кэширование** с автоочисткой
- **Database transactions** для критических операций
- **Оптимизированные SQL запросы** с индексами
- **Gzip сжатие** контента
- **Lazy loading** и **Event delegation**

## 🛠️ Архитектура и технологии

### Архитектурные паттерны
- **Service Layer** - бизнес-логика вынесена в сервисы
- **Repository Pattern** - абстракция работы с данными  
- **Policy Pattern** - контроль доступа к ресурсам
- **Observer Pattern** - отслеживание изменений моделей
- **Factory Pattern** - создание графиков аналитики
- **Dependency Injection** - инверсия зависимостей

### SOLID принципы
- **Single Responsibility** - каждый класс имеет одну ответственность
- **Open/Closed** - открыт для расширения, закрыт для изменения
- **Liskov Substitution** - использование интерфейсов и абстракций
- **Interface Segregation** - специализированные интерфейсы
- **Dependency Inversion** - зависимость от абстракций

### Технологический стек
- **Backend**: Laravel 12, PHP 8.3+
- **Database**: PostgreSQL 13+ с оптимизированными индексами
- **Frontend**: Vanilla JavaScript (ES6+), CSS3 с Custom Properties
- **Charts**: Chart.js для интерактивной аналитики
- **Authentication**: Laravel Sanctum + Session-based
- **Caching**: Redis/File-based кэширование
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx с оптимизацией

## 📦 Установка

### Системные требования
- **PHP**: 8.3+ с расширениями (pdo_pgsql, redis, gd)
- **Composer**: 2.0+
- **PostgreSQL**: 13+ 
- **Node.js**: 18+ (для сборки фронтенда)
- **Docker**: 20+ (опционально)

### Локальная установка

1. **Клонируйте репозиторий:**
```bash
git clone <repository-url>
cd task-management-api
```

2. **Установите зависимости:**
```bash
composer install
```

3. **Настройте окружение:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Настройте базу данных в .env:**
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:udeN35N0RQOntEzo353qb5HkrcVq0BiOHBvYWj+qbmU=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5427
DB_DATABASE=db
DB_USERNAME=user
DB_PASSWORD=password
```

5. **Выполните миграции:**
```bash
php artisan migrate
```

6. **Заполните базу тестовыми данными (опционально):**
```bash
php artisan db:seed --class=TestUserSeeder
php artisan db:seed --class=TaskSeeder
```

7. **Запустите сервер:**
```bash
php artisan serve
```

Приложение будет доступно по адресу `http://localhost:8000`

### Docker установка

1. **Перейдите в папку docker_s:**
```bash
cd docker_s
```

2. **Настройте переменные окружения в .env:**
```env
PROJECT_NAME=task-management
NGINX_PORT=92

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5427
DB_DATABASE=db
DB_USERNAME=user
DB_PASSWORD=password

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:udeN35N0RQOntEzo353qb5HkrcVq0BiOHBvYWj+qbmU=
APP_DEBUG=true
APP_URL=http://localhost
```

3. **Запустите контейнеры:**
```bash
docker-compose up -d
```

Приложение будет доступно по адресу `http://localhost:92`

## Использование

### Веб-интерфейс
- Откройте `http://localhost:8000` (или `http://localhost:92` для Docker)
- Зарегистрируйтесь или войдите в систему
- Используйте веб-интерфейс для управления задачами

### API
- Все API endpoints доступны по адресу `/api/`
- Для авторизации используйте токены Sanctum
- Подробная документация доступна на странице `/api-test`

## 🗂️ Структура проекта

### Ключевые компоненты

#### Controllers (MVC)
- **TaskController** - REST API для задач с Resource responses
- **TaskWebController** - веб-интерфейс с Service Layer
- **AnalyticsController** - аналитика с Chart Factory
- **AuthController** - API аутентификация через Sanctum

#### Services (Business Logic)
- **TaskService** - основная бизнес-логика задач
- **TaskAnalyticsService** - аналитика и статистика
- **Chart Factory** - создание различных типов графиков

#### Models & Policies
- **Task Model** - с константами, scopes и аксессорами
- **TaskPolicy** - авторизация доступа к задачам
- **User Model** - расширенная модель пользователя

#### Requests & Resources
- **StoreTaskRequest** - валидация создания задач
- **UpdateTaskRequest** - валидация обновления задач  
- **TaskResource** - стандартизация API ответов

#### Frontend Assets
- **public/css/app.css** - оптимизированные стили с CSS Custom Properties
- **public/js/app.js** - современный JavaScript с классами и модулями

## 🛣️ API Endpoints

### 🔐 Аутентификация
| Endpoint | Method | Description | Access |
|----------|--------|-------------|--------|
| `/api/register` | POST | Регистрация пользователя | Public |
| `/api/login` | POST | Авторизация пользователя | Public |
| `/api/auth/register` | POST | Альтернативный endpoint регистрации | Public |
| `/api/auth/login` | POST | Альтернативный endpoint авторизации | Public |
| `/api/auth/logout` | POST | Выход (удаление токенов) | Authenticated |
| `/api/user` | GET | Данные текущего пользователя | Authenticated |

### 📋 Управление задачами
| Endpoint | Method | Description | Features |
|----------|--------|-------------|----------|
| `/api/tasks` | GET | Список задач | Фильтрация, сортировка, пагинация |
| `/api/tasks` | POST | Создание задачи | Валидация, автоматические значения |
| `/api/tasks/{id}` | GET | Конкретная задача | Policy проверка доступа |
| `/api/tasks/{id}` | PUT | Обновление задачи | Частичное обновление |
| `/api/tasks/{id}` | DELETE | Удаление задачи | Soft delete опционально |

### 📊 Аналитика
| Endpoint | Method | Description | Cache TTL |
|----------|--------|-------------|-----------|
| `/api/analytics/overall-stats` | GET | Общая статистика | 5 мин |
| `/api/analytics/completed-tasks-chart` | GET | График выполненных задач | 5 мин |
| `/api/analytics/category-chart` | GET | График по категориям | 5 мин |
| `/api/analytics/tag-chart` | GET | График по тегам | 5 мин |
| `/api/analytics/productive-days-chart` | GET | График продуктивности | 5 мин |
| `/api/analytics/categories` | GET | Доступные категории | 10 мин |
| `/api/analytics/tags` | GET | Доступные теги | 10 мин |

### 🌐 Веб-интерфейс
| Route | Method | Description | Features |
|-------|--------|-------------|----------|
| `/` | GET | Главная страница | Информация о проекте |
| `/tasks` | GET | Список задач | Пагинация, фильтры, поиск |
| `/tasks` | POST | Создание задачи | AJAX, модальное окно |
| `/tasks/{id}` | PUT | Обновление задачи | Быстрое изменение статуса |
| `/analytics` | GET | Страница аналитики | Интерактивные графики |
| `/api-test` | GET | Тестирование API | Интерактивная документация |

### Консольные команды (routes/console.php)

| Команда | Описание |
|---------|----------|
| `php artisan inspire` | Выводит вдохновляющие цитаты в консоль |

## 🏗️ Архитектурные компоненты

### Service Layer
```php
// TaskService - основная бизнес-логика
class TaskService {
    public function createTask(int $userId, array $data): Task
    public function updateTask(Task $task, array $data): Task  
    public function getUserTasks(int $userId, array $filters): LengthAwarePaginator
    public function getStatusCounts(int $userId): array
}

// TaskAnalyticsService - аналитика и статистика
class TaskAnalyticsService implements AnalyticsServiceInterface {
    public function getOverallStats(int $userId): array
    public function getTaskCreationStats(int $userId, string $period): array
    public function getWeeklyActivityStats(int $userId): array
}
```

### Request Validation
```php
// StoreTaskRequest - валидация создания
class StoreTaskRequest extends FormRequest {
    public function rules(): array {
        return [
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'in:1,2,3'],
            'deadline' => ['nullable', 'date', 'after:now'],
        ];
    }
}
```

### Policy Authorization
```php
// TaskPolicy - контроль доступа
class TaskPolicy {
    public function view(User $user, Task $task): bool
    public function update(User $user, Task $task): bool
    public function delete(User $user, Task $task): bool
}
```

### API Resources
```php
// TaskResource - стандартизация API ответов
class TaskResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status_label' => $this->getStatusLabel(),
            'is_overdue' => $this->isOverdue(),
        ];
    }
}
```

### Model Enhancements
```php
// Task Model с константами и методами
class Task extends Model {
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    
    public function scopeForUser(Builder $query, int $userId): Builder
    public function scopeOverdue(Builder $query): Builder
    public function isOverdue(): bool
}
```

## 🚀 Производительность и оптимизация

### Database Optimization
- **Составные индексы**: `(user_id, status)`, `(user_id, created_at)`
- **Query optimization**: использование `selectRaw` для агрегации
- **Eager loading**: предзагрузка связанных данных
- **Database transactions**: для критических операций

### Caching Strategy
```php
// Многоуровневое кэширование
Cache::remember("user_{$userId}_task_counts", 300, function() {
    return $this->getOptimizedCounts();
});

// Автоматическая очистка кэша при изменениях
$this->clearUserCache($userId);
```

### Frontend Performance
- **Event delegation** вместо множественных обработчиков
- **Debounced search** для оптимизации запросов
- **RequestAnimationFrame** для плавных анимаций
- **CSS Custom Properties** для консистентности
- **Lazy loading** изображений и компонентов

### Network Optimization
- **Gzip compression** для всех ответов
- **HTTP/2** поддержка через Nginx
- **Asset minification** CSS и JavaScript
- **CDN ready** структура статических файлов

## 🛡️ Безопасность

### Authentication & Authorization
- **Laravel Sanctum** для API токенов
- **Policy-based authorization** для детального контроля
- **Rate limiting**: 5 попыток/минуту для критических операций
- **CSRF protection** для веб-форм

### Data Validation
- **Form Request classes** для валидации
- **SQL injection protection** через Eloquent ORM
- **XSS protection** через Blade templating
- **Mass assignment protection** через `$fillable`

### Security Headers
```nginx
# Nginx security headers
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
```

## 🧪 Тестирование

### Доступные тесты
- **Feature tests** для API endpoints
- **Unit tests** для сервисов и моделей
- **Browser tests** для веб-интерфейса
- **Performance tests** для критических операций

### Запуск тестов
```bash
# Все тесты
php artisan test

# Конкретная группа
php artisan test --group=api

# С покрытием кода
php artisan test --coverage
```

## 📊 Мониторинг и логирование

### Логирование
- **Structured logging** с контекстом
- **Error tracking** с stack traces
- **Performance monitoring** медленных запросов
- **User activity logging** для аудита

### Метрики
- **Response time** для всех endpoints
- **Database query count** и время выполнения
- **Cache hit ratio** для оптимизации
- **Memory usage** и профилирование

## 🔧 Разработка и деплой

### Development Workflow
```bash
# Локальная разработка
php artisan serve --host=0.0.0.0 --port=8000

# Отслеживание изменений
php artisan pail

# Очистка кэшей
php artisan optimize:clear
```

### Production Deployment
```bash
# Оптимизация для продакшена
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Миграции с проверкой
php artisan migrate --force
```

### Docker Production
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    build: 
      context: .
      dockerfile: Dockerfile.prod
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
```

## 📈 Масштабирование

### Horizontal Scaling
- **Load balancer** готовая конфигурация
- **Session storage** в Redis/Database
- **File storage** через S3-compatible
- **Queue workers** для фоновых задач

### Vertical Scaling
- **Database connection pooling**
- **Redis clustering** для кэша
- **PHP-FPM optimization**
- **Nginx worker processes** настройка

---

## 🤝 Вклад в проект

### Code Style
- **PSR-12** стандарт кодирования
- **PHPDoc** документация для всех методов
- **Type hints** для всех параметров и возвращаемых значений
- **SOLID principles** в архитектуре

### Pull Request Process
1. Fork репозитория
2. Создайте feature branch
3. Напишите тесты для новой функциональности
4. Убедитесь, что все тесты проходят
5. Создайте Pull Request с описанием изменений

---

**Технологии:** Laravel 12, PHP 8.3, PostgreSQL 13, Redis, Docker, Nginx, Chart.js  
**Архитектура:** Service Layer, Repository Pattern, SOLID Principles  
**Производительность:** Optimized Queries, Multi-level Caching, Asset Optimization