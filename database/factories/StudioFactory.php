<?php

namespace Database\Factories;

use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Studio>
 */
class StudioFactory extends Factory
{
    protected $model = Studio::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Studio A', 'Studio B', 'Studio C', 'Studio D']),
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(8, 30),
            'price_per_hour' => fake()->numberBetween(80000, 250000),
            'image' => null,
            'status' => 'active',
        ];
    }
}
