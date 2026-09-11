<?php

namespace Database\Factories;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRegistration>
 */
class ClassRegistrationFactory extends Factory
{
    protected $model = ClassRegistration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'class_schedule_id' => ClassSchedule::factory(),
            'registration_date' => now()->toDateString(),
            'price' => fake()->numberBetween(75000, 200000),
            'payment_status' => 'unpaid',
            'registration_status' => 'pending',
        ];
    }
}
