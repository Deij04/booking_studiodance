<?php

namespace Tests\Unit;

use App\Models\ClassPayment;
use App\Models\ClassRegistration;
use App\Models\Reservation;
use PHPUnit\Framework\TestCase;

class PaymentAmountTest extends TestCase
{
    public function test_studio_dp_is_half_and_full_payment_has_no_remaining_balance(): void
    {
        $booking = new Reservation(['total_price' => '180000.00', 'payment_type' => 'dp']);
        $this->assertSame(90000.0, $booking->payment_amount);
        $this->assertSame(90000.0, $booking->remaining_amount);
        $booking->payment_type = 'full';
        $this->assertSame(180000.0, $booking->payment_amount);
        $this->assertSame(0.0, $booking->remaining_amount);
    }

    public function test_class_dp_rounding_preserves_total_and_full_price(): void
    {
        $registration = new ClassRegistration(['price' => '85000.01']);
        $registration->setRelation('payment', new ClassPayment(['payment_type' => 'dp']));
        $this->assertSame(42500.01, $registration->payment_amount);
        $this->assertSame(42500.0, $registration->remaining_amount);
        $registration->payment->payment_type = 'full';
        $this->assertSame(85000.01, $registration->payment_amount);
        $this->assertSame(0.0, $registration->remaining_amount);
    }
}
