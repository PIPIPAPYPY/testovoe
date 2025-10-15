<?php

echo "🚀 Starting Simple PHP Server for Laravel\n";
echo "=========================================\n\n";

$host = '127.0.0.1';
$port = 8000;

echo "Starting server on http://{$host}:{$port}\n";
echo "Press Ctrl+C to stop the server\n\n";

// Запускаем встроенный PHP сервер
$command = "php -S {$host}:{$port} -t public";
echo "Command: {$command}\n\n";

// Выполняем команду
passthru($command);





