<?php

namespace Database\Factories;

use App\Models\Studio;
use App\Models\StudioSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioSchedule>
 */
class StudioScheduleFactory extends Factory
{
    protected $model = StudioSchedule::class;

    public function definition(): array
    {
        return [
            'studio_id' => Studio::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'available',
        ];
    }
}
