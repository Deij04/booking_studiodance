<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'booking_code' => 'RES-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)),
            'user_id' => User::factory(),
            'studio_schedule_id' => StudioSchedule::factory(),
            'purpose' => fake()->sentence(3),
            'total_price' => fake()->numberBetween(100000, 500000),
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'notes' => null,
        ];
    }
}
