<?php

namespace Database\Factories;

use App\Models\DanceClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DanceClass>
 */
class DanceClassFactory extends Factory
{
    protected $model = DanceClass::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'all_level']),
            'price' => fake()->numberBetween(75000, 200000),
            'capacity' => fake()->numberBetween(10, 30),
            'image' => null,
            'status' => 'active',
        ];
    }
}
