<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Premium', 'VIP', 'Student']).' '.fake()->unique()->numerify('########'),
            'price' => fake()->numberBetween(100000, 600000),
            'duration_days' => fake()->randomElement([30, 60, 90, 180]),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
