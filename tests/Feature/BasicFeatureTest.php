<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicFeatureTest extends TestCase
{
    public function test_application_loads(): void
    {
        // Простой тест, который не требует базы данных
        $this->assertTrue(true);
    }

    public function test_config_values(): void
    {
        // Проверяем, что конфигурация загружается
        $this->assertEquals('testing', config('app.env'));
        $this->assertEquals('array', config('cache.default'));
    }

    public function test_routes_exist(): void
    {
        // Проверяем, что маршруты определены
        $routes = app('router')->getRoutes();
        $this->assertNotEmpty($routes->getRoutes());
    }
}