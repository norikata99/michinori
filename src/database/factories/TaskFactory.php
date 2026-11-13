<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
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
        $start = fake()->dateTimeBetween('-2 weeks', '+2 weeks');

        return [
            'goal_id' => Goal::factory(),
            'title' => fake()->sentence(3),
            'planned_start' => $start,
            'planned_end' => fake()->dateTimeBetween($start, (clone $start)->modify('+2 weeks')),
            'estimated_minutes' => fake()->numberBetween(1, 20) * 30,
            'status' => TaskStatus::Todo,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
            'completed_at' => $attributes['planned_end'],
        ]);
    }
}
