<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_reserves_for_self_with_server_price_and_cannot_book_occupied_time(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->travelTo(now()->startOfDay());
        config(['booking.opens_at' => '08:00', 'booking.closes_at' => '20:00']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $other = User::factory()->create();
        $studio = Studio::factory()->create(['status' => 'active', 'price_per_hour' => 100000, 'capacity' => 20]);
        $data = [
            'payment_proof' => \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
            'payment_method' => 'bank_transfer', 'booking_name' => 'Practice Team', 'purpose' => 'Group Training',
            'studio_id' => $studio->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '09:00', 'end_time' => '10:30',
            'user_id' => $other->id, 'payment_status' => 'paid', 'status' => 'confirmed', 'total_price' => 1,
        ];
        $this->actingAs($customer)->get(route('user.bookings.create', ['studio_id' => $studio->id]))
            ->assertOk()->assertSee('Ajukan Reservasi');
        $withoutProof = $data; unset($withoutProof['payment_proof']);
        $this->postJson(route('user.bookings.store'), $withoutProof)->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $this->post(route('user.bookings.store'), array_replace($data, ['payment_method' => 'cash']))->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('bookings', 0);
        $this->postJson(route('user.bookings.store'), array_replace($data, [
            'payment_proof' => \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 2049, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $this->assertDatabaseCount('bookings', 0);
        $this->post(route('user.bookings.store'), $data)->assertRedirect(route('user.history'));
        $booking = Booking::sole();
        $this->assertSame($customer->id, $booking->user_id);
        $this->assertSame($customer->name, $booking->booking_name);
        $this->assertSame('bank_transfer', $booking->payment_method);
        $this->assertSame('pending', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertSame('150000.00', $booking->total_price);
        $this->post(route('user.bookings.store'), $data)->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_settings', 0);
    }

    public function test_reservation_requires_an_active_customer(): void
    {
        $this->get(route('user.bookings.create'))->assertRedirect(route('login'));
    }

    public function test_inactive_customer_cannot_submit_reservation(): void
    {
        $inactive = User::factory()->create(['role' => 'customer', 'status' => 'inactive']);
        $this->actingAs($inactive)->post(route('user.bookings.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_staff_cannot_use_customer_reservation_form(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('user.bookings.create'))->assertForbidden();
    }
    public function test_verification_records_admin_and_does_not_show_verifier_in_customer_history(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create(['name' => 'Verifier Unique Name']);
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'status' => 'confirmed', 'payment_status' => 'unpaid']);
        $this->actingAs($admin)->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertRedirect();
        $booking->refresh();
        $this->assertSame($admin->id, $booking->verified_by);
        $this->assertSame($admin->name, $booking->verified_by_name);
        $this->assertNotNull($booking->verified_at);
        $this->get(route('admin.bookings.edit', $booking))->assertOk()->assertSee('Diverifikasi oleh')->assertSee($admin->name);
        $this->actingAs($customer)->get(route('user.history'))->assertOk()->assertDontSee('Status verifikasi')->assertDontSee('Terverifikasi')->assertDontSee($admin->name);
        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => 'confirmed', 'payment_status' => 'unpaid',
        ])->assertSessionHasErrors('payment_status');
        $this->assertSame($admin->id, $booking->fresh()->verified_by);
        $this->assertNotNull($booking->fresh()->verified_at);
    }

}
