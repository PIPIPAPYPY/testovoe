# Task Management API (Laravel)

Проект: **Task Management API** — REST API для управления задачами с современным веб-интерфейсом на Laravel.

---

## 🔹 Требования

* PHP >= 8.1
* Composer
* PostgreSQL
* Git
* Node.js & npm (опционально, для сборки ассетов фронтенда через Vite)

---

## 🔹 Установка

1. Клонируем репозиторий:

```bash
git clone https://github.com/PIPIPAPYPY/testovoe.git
cd testovoe
```

2. Установим зависимости PHP через Composer:

```bash
composer install
```

3. (Опционально) Установим зависимости JS и соберём фронтенд:

```bash
npm install
npm run dev
```

4. Создаём копию `.env` из примера:

```bash
cp .env.example .env
```

5. Заполни файл `.env` своими настройками, например:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=php artisan key:generate (сгенерируй после копирования)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=имя_бд
DB_USERNAME=пользователь
DB_PASSWORD=пароль
```

6. Сгенерируем ключ приложения:

```bash
php artisan key:generate
```

7. Прогоним миграции и, если нужно, сиды:

```bash
php artisan migrate --seed
```

---

## 🔹 Запуск локального сервера

```bash
php artisan serve --port=92
```

По умолчанию доступно по адресу: `http://localhost:92`

---

## 🔹 Тестирование и документация API

* **Главная страница с документацией:** [http://localhost:92/#api-docs](http://localhost:92/#api-docs) — содержит блок с API Endpoints.
* **Страница для тестирования:** `/test_api.html` — можно создавать, редактировать и удалять задачи через интерфейс.
* **Прямые API вызовы:**

  * Список задач: `GET /api/tasks`
  * Создать задачу: `POST /api/tasks`
  * Получить конкретную задачу: `GET /api/tasks/{id}`
  * Обновить задачу: `PUT /api/tasks/{id}`
  * Удалить задачу: `DELETE /api/tasks/{id}`

> Для тестирования также можно использовать [Postman](https://www.postman.com/).

---

## 🔹 Структура проекта

```
app/        # Контроллеры, модели, сервисы
database/   # Миграции, сиды, фабрики
public/     # Фронтенд и точка входа index.php
resources/  # Шаблоны Blade, CSS и JS
routes/     # Файлы маршрутов
```

---

## 🔹 Работа с .env (чеклист)

1. Создать локальный `.env` на основе `.env.example`.
2. Заполнить реальные значения для:

   * `APP_KEY` (сгенерировать через `php artisan key:generate`)
   * Подключения к базе данных (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
3. Убедиться, что `.env` добавлен в `.gitignore`.
4. Прогони миграции и сиды:

```bash
php artisan migrate --seed
```

5. Запустить сервер:

```bash
php artisan serve --port=92
```

Теперь проект готов к использованию локально.

---

## 🔹 Лицензия

MIT License
