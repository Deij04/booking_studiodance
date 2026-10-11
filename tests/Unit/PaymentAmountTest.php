<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\ClassRegistration;
use App\Models\Booking;
use PHPUnit\Framework\TestCase;

class PaymentAmountTest extends TestCase
{
    public function test_studio_dp_is_half_and_full_payment_has_no_remaining_balance(): void
    {
        $booking = new Booking(['total_price' => '180000.00']);
        $booking->setRelation('payment', new Payment(['amount' => 90000, 'payment_type' => 'dp']));
        $this->assertSame(90000.0, $booking->payment_amount);
        $this->assertSame(90000.0, $booking->remaining_amount);
        $booking->payment->amount = 180000; $booking->payment->payment_type = 'full';
        $this->assertSame(180000.0, $booking->payment_amount);
        $this->assertSame(0.0, $booking->remaining_amount);
    }

    public function test_class_dp_rounding_preserves_total_and_full_price(): void
    {
        $registration = new ClassRegistration(['price' => '85000.01']);
        $registration->setRelation('payment', new Payment(['payment_type' => 'dp', 'amount' => 42500.01]));
        $this->assertSame(42500.01, $registration->payment_amount);
        $this->assertSame(42500.0, $registration->remaining_amount);
        $registration->payment->amount = 85000.01; $registration->payment->payment_type = 'full';
        $this->assertSame(85000.01, $registration->payment_amount);
        $this->assertSame(0.0, $registration->remaining_amount);
    }
}
