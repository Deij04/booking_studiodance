<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function configure(): static
    {
        return $this->afterMaking(function ($record) {
            $attributes = $record->getAttributes();
            $fields = ['payment_status', 'verified_by', 'verified_by_name', 'verified_at', 'payment_method', 'payment_type', 'payment_proof'];
            $payment = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $attributes)) $payment[$field] = $attributes[$field];
                unset($record[$field]);
            }
            $payment['verification_status'] = ($payment['payment_status'] ?? 'unpaid') === 'paid' ? 'verified' : 'pending';
            $payment['amount'] = $payment['initial_payment_amount'] ?? round((float) $record->total_price * (($payment['payment_type'] ?? 'full') === 'dp' ? 0.5 : 1), 2);
            unset($payment['payment_status'], $payment['initial_payment_amount']);
            $record->setRelation('payment', new \App\Models\Payment($payment));
        })->afterCreating(function ($record) {
            $payment = $record->getRelation('payment');
            $record->payment()->save($payment);
        });
    }

    public function definition(): array
    {
        return [
            'booking_code' => 'RES-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)),
            'user_id' => User::factory(),
            'studio_id' => Studio::factory(),
            'date' => today()->addDay()->toDateString(),
            'start_time' => '08:00:00', 'end_time' => '10:00:00',
            'purpose' => fake()->sentence(3),
            'total_price' => fake()->numberBetween(100000, 500000),
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'notes' => null,
        ];
    }
}
