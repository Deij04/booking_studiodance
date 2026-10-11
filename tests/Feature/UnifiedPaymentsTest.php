<?php
namespace Tests\Feature;
use App\Models\ClassRegistration;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnifiedPaymentsTest extends TestCase
{
    use RefreshDatabase;
    public function test_payment_belongs_to_exactly_one_reservation_and_initial_is_unique(): void
    {
        $studio = Booking::factory()->create(); $class = ClassRegistration::factory()->create();
        foreach ([[], ['booking_id' => $studio->id, 'class_registration_id' => $class->id], ['booking_id' => $studio->id]] as $owner) {
            try { Payment::create($owner + ['amount' => 50000]); $this->fail('Invalid or duplicate initial payment was accepted.'); }
            catch (ValidationException $exception) { $this->assertArrayHasKey('payment', $exception->errors()); }
        }
        $this->assertDatabaseCount('payments', 2);
        $this->assertFalse(Schema::hasColumn('bookings', 'payment_status'));
        $this->assertFalse(Schema::hasColumn('class_registrations', 'payment_status'));
        $this->assertFalse(Schema::hasTable('class_payments'));
        $this->assertFalse(Schema::hasTable('reservation_settlements'));
    }

    public function test_initial_payment_cannot_be_verified_through_settlement_endpoint(): void
    {
        $record = Booking::factory()->create();
        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->patch(route('admin.settlements.decide', $record->payment), ['decision' => 'verified'])->assertNotFound();
        $this->assertSame('pending', $record->payment->fresh()->verification_status);
    }

    public function test_seed_data_uses_unified_payments(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->assertDatabaseCount('payments', 4);
        $this->assertSame(2, Payment::where('verification_status', 'verified')->count());
        $this->assertEquals(300000, app(\App\Services\BookingDashboardMetrics::class)->get()['revenue']);
    }

}
