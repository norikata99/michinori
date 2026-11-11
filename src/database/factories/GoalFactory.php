<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'planned_start' => $start,
            'planned_end' => fake()->dateTimeBetween($start, (clone $start)->modify('+3 months')),
            'achieved_at' => null,
            'status' => GoalStatus::InProgress,
        ];
    }

    public function achieved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoalStatus::Achieved,
            'achieved_at' => $attributes['planned_end'],
        ]);
    }
}
