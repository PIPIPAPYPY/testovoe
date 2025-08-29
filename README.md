# Task Management API (Laravel)

Проект: **Task Management API** — REST API для управления задачами с современным веб-интерфейсом на Laravel.

---

## 🔹 Требования

* PHP >= 8.1
* Composer
* PostgreSQL
* Node.js & npm (для сборки фронтенда)
* Git

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

3. Установим зависимости JS (если планируется сборка фронтенда):

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
php artisan serve
```

По умолчанию доступно по адресу: `http://127.0.0.1:8000`

---

## 🔹 Тестирование API

* Список задач: `GET /api/tasks`
* Создать задачу: `POST /api/tasks`
* Получить конкретную задачу: `GET /api/tasks/{id}`
* Обновить задачу: `PUT /api/tasks/{id}`
* Удалить задачу: `DELETE /api/tasks/{id}`

> Для тестирования можно использовать [Postman](https://www.postman.com/) или страницу `/test_api.html`.

---

## 🔹 Дополнительно

* Веб-интерфейс для просмотра задач: `/tasks`
* Документация API: `/test_api.html` и раздел `API Endpoints` на главной странице.

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
php artisan serve
```

Теперь проект готов к использованию локально.

---

## 🔹 Лицензия

MIT License
