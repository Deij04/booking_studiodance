<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\BookingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class BookingSchemaConsolidationTest extends TestCase
{
    use RefreshDatabase;
    public function test_fresh_schema_has_direct_booking_times_and_settings_blocks(): void
    {
        $booking = Booking::factory()->create();
        BookingSetting::factory()->create(['studio_id' => $booking->studio_id]);
        $this->assertFalse(Schema::hasTable('reservations')); $this->assertFalse(Schema::hasTable('studio_schedules'));
        $this->assertFalse(Schema::hasColumn('bookings', 'studio_schedule_id'));
        $this->assertTrue(Schema::hasColumns('bookings', ['studio_id', 'date', 'start_time', 'end_time']));
        $this->assertSame($booking->id, $booking->payment->booking_id);
        $this->assertSame(1, BookingSetting::blocked()->count());
    }
}
