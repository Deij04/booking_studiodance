<?php
namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClassRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerReservationDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_delete_bookings_and_class_reservations(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['role' => 'super_admin']);
        foreach ([Booking::factory()->create(), ClassRegistration::factory()->create()] as $record) {
            $studio = $record instanceof Booking;
            $route = $studio ? 'admin.bookings.destroy' : 'admin.class-registrations.destroy';
            $parent = $studio ? $record->studio : $record->classSchedule;
            $proof = ($studio ? 'payment-proofs/' : 'class-payment-proofs/').$record->id.'.pdf';
            Storage::disk('local')->put($proof, 'proof');
            $record->payment->update(['payment_proof' => $proof]);
            $paymentId = $record->payment->id;
            $this->actingAs($admin)->delete(route($route, $record))->assertForbidden();
            $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
            $this->actingAs($owner)->delete(route($route, $record))->assertRedirect();
            $this->assertDatabaseMissing($record->getTable(), ['id' => $record->id]);
            $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
            $this->assertDatabaseHas($parent->getTable(), ['id' => $parent->id]);
            Storage::disk('local')->assertMissing($proof);
        }
    }
}
