<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creation_forces_verification_by_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $studio = Studio::factory()->create(['status' => 'active', 'price_per_hour' => 100000]);
        $this->actingAs($admin)->get(route('admin.bookings.create'))->assertOk()->assertDontSee('name="payment_status"', false);
        $this->post(route('admin.bookings.store'), [
            'user_id' => $customer->id, 'studio_id' => $studio->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '10:00', 'payment_method' => 'cash', 'payment_type' => 'dp',
            'payment_status' => 'unpaid', 'verified_by' => $customer->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.bookings.index', ['tab' => 'active']));
        $booking = Booking::sole();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame($admin->id, $booking->verified_by);
        $this->assertSame($admin->name, $booking->verified_by_name);
        $this->assertNotNull($booking->verified_at);
    }

    public function test_admin_and_customer_cannot_reserve_only_thirty_minutes(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create();
        $studio = Studio::factory()->create(['status' => 'active']);
        $data = ['user_id' => $customer->id, 'studio_id' => $studio->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '09:30', 'payment_method' => 'qris', 'payment_type' => 'full',
            'booking_name' => $customer->name, 'purpose' => 'Group Training',
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf')];
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.bookings.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('end_time');
        $this->actingAs($customer)->postJson(route('user.bookings.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('end_time');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get(route('user.bookings.create', ['studio_id' => $studio->id]))->assertOk()
            ->assertSee('data-minimum="60"', false)->assertSee('min="1"', false);
    }

    public function test_admin_settlement_verifies_actual_admin_and_moves_to_history(): void
    {
        Storage::fake('local');
        $creator = User::factory()->admin()->create();
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'dp', 'total_price' => 300000, 'verified_by' => $creator->id]);
        $url = route('admin.bookings.settle', $booking);
        $this->actingAs($admin)->post($url, ['payment_method' => 'bank_transfer'])->assertSessionHasErrors('payment_proof');
        $this->post($url, ['payment_method' => 'bank_transfer', 'amount' => 1, 'verified_by' => $creator->id,
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf')])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.bookings.index', ['tab' => 'active']));
        $settlement = $booking->settlement()->sole();
        $this->assertSame('150000.00', $settlement->amount);
        $this->assertSame('verified', $settlement->status);
        $this->assertSame($admin->id, $settlement->verified_by);
        $this->assertSame($admin->name, $settlement->verified_by_name);
        $this->assertSame($creator->id, $booking->fresh()->verified_by);
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->travelTo(\Carbon\Carbon::parse($booking->date.' '.$booking->end_time)->addMinute());
        app(\App\Services\BookingCompletion::class)->run();
        $this->assertSame('completed', $booking->fresh()->status);
        $this->get(route('admin.bookings.index', ['tab' => 'history']))->assertOk()->assertSee($booking->booking_code);
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()->assertDontSee($booking->booking_code);
        $this->post($url, ['payment_method' => 'cash'])->assertSessionHasErrors('payment_proof');
        $this->assertSame(1, \App\Models\Payment::where('payment_stage', 'settlement')->count());
    }

    public function test_unverified_dp_cannot_be_settled_and_uploaded_file_is_cleaned_up(): void
    {
        Storage::fake('local');
        $booking = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'unpaid', 'payment_type' => 'dp']);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.bookings.settle', $booking), [
            'payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
        ])->assertSessionHasErrors('payment_proof');
        $this->assertSame(0, \App\Models\Payment::where('payment_stage', 'settlement')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
