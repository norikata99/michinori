<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkLog>
 */
class WorkLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'worked_on' => fake()->dateTimeBetween('-2 weeks', 'today'),
            'minutes' => fake()->numberBetween(1, 8) * 15,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
