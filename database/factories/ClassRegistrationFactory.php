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

    public function configure(): static
    {
        return $this->afterMaking(function ($record) {
            $attributes = $record->getAttributes();
            $fields = ['payment_status', 'initial_payment_amount'];
            $payment = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $attributes)) $payment[$field] = $attributes[$field];
                unset($record[$field]);
            }
            $payment['verification_status'] = ($payment['payment_status'] ?? 'unpaid') === 'paid' ? 'verified' : 'pending';
            $payment['amount'] = $payment['initial_payment_amount'] ?? round((float) $record->price * (($payment['payment_type'] ?? 'full') === 'dp' ? 0.5 : 1), 2);
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
            'user_id' => User::factory(),
            'class_schedule_id' => ClassSchedule::factory(),
            'registration_date' => now()->toDateString(),
            'price' => fake()->numberBetween(75000, 200000),
            'payment_status' => 'unpaid',
            'registration_status' => 'pending',
        ];
    }
}
