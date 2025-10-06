<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();
        
        $expectedFillable = ['name', 'email', 'password'];
        
        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $user = new User();
        
        $expectedHidden = ['password', 'remember_token'];
        
        $this->assertEquals($expectedHidden, $user->getHidden());
    }

    public function test_user_has_many_tasks_relationship(): void
    {
        $user = User::factory()->create();
        $tasks = Task::factory()->count(3)->forUser($user)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->tasks);
        $this->assertCount(3, $user->tasks);
        
        foreach ($user->tasks as $task) {
            $this->assertInstanceOf(Task::class, $task);
            $this->assertEquals($user->id, $task->user_id);
        }
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'test-password'
        ]);

        $this->assertTrue(Hash::check('test-password', $user->password));
        $this->assertNotEquals('test-password', $user->password);
    }

    public function test_user_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    public function test_user_can_be_created_with_valid_data(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = User::create($userData);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
    }

    public function test_user_email_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'name' => 'Test User',
            'password' => 'password123'
        ]);
    }

    public function test_user_password_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
    }

    public function test_user_has_api_tokens_trait(): void
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'tokens'));
        $this->assertTrue(method_exists($user, 'createToken'));
    }
}