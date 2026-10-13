<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\Membership;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class MembershipBenefitsTest extends TestCase {
    use RefreshDatabase;
    private function member(User $user, ?Studio $studio = null): Membership {
        return Membership::factory()->create(['user_id' => $user->id, 'studio_id' => $studio?->id, 'quota_minutes' => 900, 'free_classes' => true, 'start_date' => today(), 'end_date' => today()->addDays(29), 'status' => 'active', 'payment_status' => 'paid']);
    }
    public function test_active_member_registers_for_class_free_without_proof(): void {
        Storage::fake('local');
        $user = User::factory()->create(); $member = $this->member($user);
        $schedule = ClassSchedule::factory()->create(['date' => today()->addDays(2)->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'active']);
        $this->actingAs($user)->get(route('user.classes.reserve', $schedule->danceClass))->assertOk()->assertViewHas('classMembership');
        $this->post(route('user.classes.store', $schedule->danceClass), ['class_schedule_id' => $schedule->id, 'payment_type' => 'full'])->assertSessionHasNoErrors()->assertRedirect();
        $record = ClassRegistration::sole();
        $this->assertSame($member->id, $record->membership_id);
        $this->assertSame('confirmed', $record->registration_status);
        $this->assertEquals(0, $record->price); $this->assertEquals(0, $record->payment->amount);
        $this->assertSame('verified', $record->payment->verification_status);
        $this->assertSame(900, $member->remaining_minutes);
    }
    public function test_admin_can_register_member_free_and_studio_name_follows_id(): void {
        Storage::fake('local');
        $studio = Studio::factory()->create(['name' => 'Studio Awal']); $user = User::factory()->create(); $member = $this->member($user, $studio);
        $plan = $member->membershipPlan; $plan->update(['studio_id' => $studio->id, 'quota_hours' => 15]);
        $studio->update(['name' => 'Studio Baru']); $this->assertSame('Studio Baru - Membership 15', $plan->fresh()->display_name);
        $schedule = ClassSchedule::factory()->create(['date' => today()->addDays(2)->toDateString(), 'status' => 'active']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.class-registrations.store'), ['user_id' => $user->id, 'class_schedule_id' => $schedule->id, 'payment_method' => 'membership', 'payment_type' => 'full'])->assertSessionHasNoErrors()->assertRedirect();
        $record = ClassRegistration::sole(); $this->assertEquals(0, $record->price); $this->assertSame('confirmed', $record->registration_status); $this->assertSame($admin->id, $record->payment->verified_by);
    }
    public function test_expired_or_out_of_period_membership_does_not_waive_class_payment(): void {
        $user = User::factory()->create(); $this->member($user);
        $schedule = ClassSchedule::factory()->create(['date' => today()->addDays(40)->toDateString(), 'status' => 'active']);
        $this->actingAs($user)->postJson(route('user.classes.store', $schedule->danceClass), ['class_schedule_id' => $schedule->id, 'payment_type' => 'full'])->assertUnprocessable()->assertJsonValidationErrors(['payment_method','payment_proof']);
        $this->assertDatabaseCount('class_registrations', 0);
    }
    public function test_studio_quota_is_reserved_and_released_on_cancellation(): void {
        Storage::fake('local');
        $studio = Studio::factory()->create(); $user = User::factory()->create(); $member = $this->member($user, $studio);
        $data = ['studio_id' => $studio->id, 'date' => today()->addDay()->toDateString(), 'start_time' => '08:00', 'end_time' => '10:00', 'purpose' => 'Private Practice', 'payment_method' => 'membership'];
        $this->actingAs($user)->post(route('user.bookings.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $booking = Booking::sole();
        $this->assertSame('confirmed', $booking->status); $this->assertEquals(0, $booking->total_price); $this->assertEquals(0, $booking->payment->amount);
        $this->assertSame(780, $member->remaining_minutes);
        $booking->update(['status' => 'cancelled']); $this->assertSame(900, $member->remaining_minutes);
        $member->update(['quota_minutes' => 60]);
        $this->postJson(route('user.bookings.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->assertDatabaseCount('bookings', 1);
    }
}
