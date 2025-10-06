<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // RefreshDatabase trait обеспечивает миграции для PostgreSQL
        // Тестовая база данных создается автоматически
    }

    /**
     * Create and authenticate a user for testing
     */
    protected function authenticateUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);
        return $user;
    }

    /**
     * Create a user without authentication
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure(array $structure, $response): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert successful JSON response with data
     */
    protected function assertSuccessfulJsonResponse($response, array $expectedData = []): void
    {
        $response->assertStatus(200)
                ->assertJson($expectedData);
    }

    /**
     * Assert validation error response
     */
    protected function assertValidationError($response, array $expectedErrors = []): void
    {
        $response->assertStatus(422);
        
        if (!empty($expectedErrors)) {
            $response->assertJsonValidationErrors($expectedErrors);
        }
    }
}
