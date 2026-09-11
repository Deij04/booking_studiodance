<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    public function definition(): array
    {
        return [
            'dance_class_id' => DanceClass::factory(),
            'studio_id' => Studio::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
            'capacity' => fake()->numberBetween(10, 30),
            'status' => 'active',
        ];
    }
}
