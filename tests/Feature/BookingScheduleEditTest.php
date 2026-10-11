<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingScheduleEditTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): Booking
    {
        return Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'dp', 'total_price' => 300000]);
    }

    private function data(Booking $booking): array
    {
        return ['studio_id' => $booking->studio_id, 'date' => today()->addDays(3)->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00'];
    }

    public function test_edit_and_settlement_forms_are_separate_and_payment_is_locked(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = $this->booking();
        $this->get(route('admin.bookings.edit', $booking))->assertOk()->assertSee('name="studio_id"', false)
            ->assertSee('data-booking-cancel', false)->assertDontSee('name="payment_type"', false)
            ->assertDontSee('name="payment_status"', false)->assertDontSee('Simpan Pelunasan');
        $this->get(route('admin.bookings.settlement-form', $booking))->assertOk()->assertSee('Simpan Pelunasan')
            ->assertDontSee('name="studio_id"', false)->assertDontSee('Simpan Jadwal');
        foreach (['payment_status' => 'unpaid', 'payment_type' => 'full', 'status' => 'completed', 'payment_method' => 'cash'] as $field => $value) {
            $this->put(route('admin.bookings.update', $booking), $this->data($booking) + [$field => $value])->assertSessionHasErrors($field);
        }
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('dp', $booking->fresh()->payment_type);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_booking_can_change_studio_schedule_without_changing_verified_amount(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = $this->booking();
        $studio = Studio::factory()->create(['status' => 'active']);
        $data = array_replace($this->data($booking), ['studio_id' => $studio->id]);
        $this->put(route('admin.bookings.update', $booking), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($studio->id, $booking->fresh()->studio_id);
        $this->assertSame($data['date'], $booking->fresh()->date);
        $this->assertSame('300000.00', $booking->fresh()->total_price);
        $this->assertSame('dp', $booking->fresh()->payment_type);
        $this->put(route('admin.bookings.update', $booking), $data)->assertSessionHasNoErrors();
    }

    public function test_own_schedule_is_available_but_conflicting_booking_cannot_be_selected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = $this->booking();
        $data = $this->data($booking);
        $booking->update($data);
        $response = $this->getJson(route('admin.bookings.availability', $data['studio_id']).'?month='.substr($data['date'], 0, 7).'&booking_id='.$booking->id)->assertOk();
        $this->assertContains('11:00', $response->json('days.'.$data['date'].'.10:00'));
        $studio = Studio::factory()->create(['status' => 'active']);
        Booking::factory()->create(['studio_id' => $studio->id, 'date' => $data['date'], 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'pending']);
        $this->put(route('admin.bookings.update', $booking), array_replace($data, ['studio_id' => $studio->id]))->assertSessionHasErrors('start_time');
        $this->assertSame($data['studio_id'], $booking->fresh()->studio_id);
    }

    public function test_cancellation_releases_schedule_and_keeps_verified_dp_revenue(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = $this->booking();
        $this->patch(route('admin.bookings.cancel', $booking))->assertRedirect(route('admin.bookings.index', ['tab' => 'history']));
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(150000.0, app(\App\Services\BookingDashboardMetrics::class)->get()['revenue']);
        $this->patch(route('admin.bookings.cancel', $booking))->assertForbidden();
    }
}
