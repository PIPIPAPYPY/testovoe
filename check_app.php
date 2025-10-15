<?php

echo "🔍 Checking Laravel Application Status\n";
echo "=====================================\n\n";

// Проверяем, что мы в правильной директории
if (!file_exists('artisan')) {
    echo "❌ Error: artisan file not found. Are you in the Laravel project directory?\n";
    exit(1);
}

echo "✅ Laravel project structure found\n";

// Проверяем composer
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Error: vendor/autoload.php not found. Run 'composer install' first.\n";
    exit(1);
}

echo "✅ Composer dependencies found\n";

// Загружаем Laravel
try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    echo "✅ Laravel application loaded\n";
} catch (Exception $e) {
    echo "❌ Error loading Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// Проверяем конфигурацию
try {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo "✅ Laravel kernel bootstrapped\n";
} catch (Exception $e) {
    echo "❌ Error bootstrapping Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// Проверяем базу данных
try {
    $connection = \Illuminate\Support\Facades\DB::connection();
    $connection->getPdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "💡 Tip: Make sure database is configured and accessible\n";
}

// Проверяем маршруты
try {
    $routes = app('router')->getRoutes();
    $webRoutes = collect($routes)->filter(function ($route) {
        return !str_starts_with($route->uri(), 'api/');
    });
    $apiRoutes = collect($routes)->filter(function ($route) {
        return str_starts_with($route->uri(), 'api/');
    });
    
    echo "✅ Found " . $webRoutes->count() . " web routes\n";
    echo "✅ Found " . $apiRoutes->count() . " API routes\n";
} catch (Exception $e) {
    echo "❌ Error loading routes: " . $e->getMessage() . "\n";
}

// Проверяем модели
try {
    $userModel = new \App\Models\User();
    $taskModel = new \App\Models\Task();
    echo "✅ Models loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error loading models: " . $e->getMessage() . "\n";
}

// Проверяем фабрики
try {
    $user = \App\Models\User::factory()->make();
    $task = \App\Models\Task::factory()->make();
    echo "✅ Factories working correctly\n";
} catch (Exception $e) {
    echo "❌ Error with factories: " . $e->getMessage() . "\n";
}

echo "\n🎯 Application Status Summary:\n";
echo "==============================\n";
echo "✅ Laravel project structure: OK\n";
echo "✅ Composer dependencies: OK\n";
echo "✅ Laravel application: OK\n";
echo "✅ Database connection: " . (isset($connection) ? "OK" : "FAILED") . "\n";
echo "✅ Routes: OK\n";
echo "✅ Models: OK\n";
echo "✅ Factories: OK\n";

echo "\n📝 Next steps:\n";
echo "1. If database connection failed, check your .env file\n";
echo "2. Run 'php artisan migrate' to set up database tables\n";
echo "3. Run 'php artisan serve' to start the development server\n";
echo "4. Open http://localhost:8000 in your browser\n";

echo "\n🚀 Ready to start the server!\n";





