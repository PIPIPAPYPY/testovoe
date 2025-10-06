<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => Task::STATUS_TODO,
            'priority' => Task::PRIORITY_MEDIUM,
            'user_id' => User::factory(),
            'deadline' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Create a task with high priority
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Task::PRIORITY_HIGH,
        ]);
    }

    /**
     * Create a task with low priority
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Task::PRIORITY_LOW,
        ]);
    }

    /**
     * Create a task in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Task::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Create a completed task
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Task::STATUS_DONE,
        ]);
    }

    /**
     * Create an overdue task
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => fake()->randomElement([Task::STATUS_TODO, Task::STATUS_IN_PROGRESS]),
        ]);
    }

    /**
     * Create a task without deadline
     */
    public function withoutDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => null,
        ]);
    }

    /**
     * Create a task for specific user
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}