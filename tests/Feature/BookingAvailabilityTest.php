<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\BookingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_excludes_occupied_times_and_full_dates_per_studio(): void
    {
        config(['booking.opens_at' => '08:00', 'booking.closes_at' => '12:00', 'booking.minimum_minutes' => 60, 'booking.step_minutes' => 30]);
        $this->actingAs(User::factory()->admin()->create());
        $studio = Studio::factory()->create();
        $date = today()->addDay()->toDateString();
        $booking = Booking::factory()->create(['studio_id' => $studio->id, 'date' => $date, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'status' => 'pending']);
        $url = route('admin.bookings.availability', ['studio' => $studio, 'month' => substr($date, 0, 7)]);
        $slots = $this->getJson($url)->assertOk()->json('days')[$date];
        $this->assertSame(['08:00', '08:30', '09:00', '11:00'], array_keys($slots));
        $this->assertSame(['09:00', '09:30', '10:00'], $slots['08:00']);
        $this->assertSame(['12:00'], $slots['11:00']);
        $booking->update(['status' => 'confirmed']);
        $this->assertSame($slots, $this->getJson($url)->assertOk()->json('days')[$date]);
        $booking->update(['start_time' => '08:00:00', 'end_time' => '12:00:00']);
        $this->assertSame([], $this->getJson($url)->assertOk()->json('days')[$date]);
        $otherStudio = Studio::factory()->create();
        $other = $this->getJson(route('admin.bookings.availability', ['studio' => $otherStudio, 'month' => substr($date, 0, 7)]))->assertOk()->json('days')[$date];
        $this->assertNotEmpty($other);
        $booking->update(['status' => 'cancelled']);
        $this->assertNotEmpty($this->getJson($url)->assertOk()->json('days')[$date]);
        BookingSetting::factory()->create(['studio_id' => $studio->id, 'date' => $date, 'start_time' => '08:00', 'end_time' => '12:00']);
        $this->assertSame([], $this->getJson($url)->assertOk()->json('days')[$date]);
    }

    public function test_calendar_requires_staff_access_and_valid_month(): void
    {
        $studio = Studio::factory()->create();
        $url = route('admin.bookings.availability', ['studio' => $studio, 'month' => 'invalid']);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson($url)->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->getJson($url)->assertUnprocessable();
    }
}
