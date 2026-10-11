<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEditAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_and_history_bookings_have_no_edit_and_cannot_be_modified(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['pending' => 'requests', 'cancelled' => 'history', 'completed' => 'history'] as $status => $tab) {
            $booking = Booking::factory()->create(['status' => $status, 'payment_status' => 'paid']);
            $this->get(route('admin.bookings.index', ['tab' => $tab]))->assertOk()
                ->assertSee(route('admin.bookings.show', $booking))->assertDontSee(route('admin.bookings.edit', $booking));
            $this->get(route('admin.bookings.show', $booking))->assertOk();
            $this->get(route('admin.bookings.edit', $booking))->assertForbidden();
            $this->put(route('admin.bookings.update', $booking), ['status' => 'confirmed', 'payment_status' => 'paid'])->assertForbidden();
            $this->assertSame($status, $booking->fresh()->status);
        }
    }

    public function test_active_and_history_are_separate_and_status_filter_is_scoped_to_history(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $records = [];
        foreach (array_keys(Booking::STATUSES) as $status) {
            $records[$status] = Booking::factory()->create(['status' => $status, 'payment_status' => 'paid']);
        }
        $response = $this->get(route('admin.bookings.index', ['tab' => 'history']))->assertOk()->assertSee('Riwayat Booking');
        foreach ($records as $record) {
            if (in_array($record->status, ['completed', 'cancelled'])) {
                $response->assertSee($record->booking_code);
            } else {
                $response->assertDontSee($record->booking_code);
            }
        }
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()
            ->assertSee($records['confirmed']->booking_code)->assertDontSee($records['cancelled']->booking_code)
            ->assertDontSee($records['pending']->booking_code)->assertDontSee($records['completed']->booking_code);
        $response = $this->get(route('admin.bookings.index', ['tab' => 'history', 'status' => 'cancelled']))->assertOk();
        $response->assertSee($records['cancelled']->booking_code)->assertDontSee($records['confirmed']->booking_code);
        $this->get(route('admin.bookings.index', ['tab' => 'requests', 'status' => 'confirmed']))->assertOk()
            ->assertSee($records['pending']->booking_code)->assertDontSee($records['confirmed']->booking_code);
    }

    public function test_requests_filter_full_payment_and_dp(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $full = Booking::factory()->create(['status' => 'pending', 'payment_type' => 'full']);
        $dp = Booking::factory()->create(['status' => 'pending', 'payment_type' => 'dp']);
        $this->get(route('admin.bookings.index', ['tab' => 'requests', 'payment_type' => 'full']))->assertOk()
            ->assertSee($full->booking_code)->assertDontSee($dp->booking_code);
        $this->get(route('admin.bookings.index', ['tab' => 'requests', 'payment_type' => 'dp']))->assertOk()
            ->assertSee($dp->booking_code)->assertDontSee($full->booking_code);
    }

    public function test_active_dp_action_tracks_settlement_verification(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'dp', 'total_price' => 300000]);
        $url = route('admin.bookings.index', ['tab' => 'active']);
        $action = route('admin.bookings.settlement-form', $booking);
        $this->get($url)->assertOk()->assertSee('DP - Belum Lunas')->assertSee('Sisa: Rp 150.000')->assertSee($action);
        $settlement = $booking->settlement()->create(['amount' => 150000, 'payment_method' => 'bank_transfer', 'payment_proof' => 'settlement-proofs/test.jpg', 'verification_status' => 'pending', 'payment_stage' => 'settlement']);
        $this->get($url)->assertOk()->assertSee('Verifikasi Pelunasan')->assertSee($action);
        $settlement->update(['verification_status' => 'verified', 'payment_stage' => 'settlement']);
        $this->get($url)->assertOk()->assertDontSee('DP - Belum Lunas')->assertDontSee($action);
        $this->assertSame('dp', $booking->fresh()->payment_type);
    }

    public function test_legacy_confirmed_unverified_booking_remains_in_requests_until_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $booking = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'unpaid']);
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()->assertDontSee($booking->booking_code);
        $this->get(route('admin.bookings.index', ['tab' => 'requests']))->assertOk()->assertSee($booking->booking_code);
        $this->get(route('admin.bookings.edit', $booking))->assertForbidden();
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertRedirect();
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame($admin->id, $booking->fresh()->verified_by);
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()->assertSee($booking->booking_code);
    }

    public function test_pending_settlement_is_first_then_returns_to_schedule_order(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $early = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'full']);
        $early->update(['date' => today()->addDay()->toDateString()]);
        $later = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'dp']);
        $later->update(['date' => today()->addDays(5)->toDateString()]);
        $settlement = $later->settlement()->create(['amount' => $later->remaining_amount, 'payment_method' => 'bank_transfer', 'payment_proof' => 'settlement-proofs/test.pdf', 'verification_status' => 'pending', 'payment_stage' => 'settlement']);
        $url = route('admin.bookings.index', ['tab' => 'active']);
        $this->get($url)->assertOk()->assertSeeInOrder([$later->booking_code, $early->booking_code]);
        $settlement->update(['verification_status' => 'verified', 'payment_stage' => 'settlement']);
        $this->get($url)->assertOk()->assertSeeInOrder([$early->booking_code, $later->booking_code]);
        $settlement->update(['verification_status' => 'rejected']);
        $this->get($url)->assertOk()->assertSeeInOrder([$early->booking_code, $later->booking_code]);
    }

    public function test_accepting_request_unlocks_edit_in_active_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Booking::factory()->create(['status' => 'pending']);
        $this->get(route('admin.bookings.index'))->assertOk()->assertSee('Terima')->assertSee('Tolak');
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertRedirect();
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()->assertSee(route('admin.bookings.edit', $booking));
        $this->get(route('admin.bookings.edit', $booking))->assertOk();
        $this->put(route('admin.bookings.update', $booking), [
            'studio_id' => $booking->studio_id,
            'date' => $booking->date, 'start_time' => '08:00', 'end_time' => '10:00',
        ])->assertRedirect();
        $this->assertSame('paid', $booking->fresh()->payment_status);
    }
}
