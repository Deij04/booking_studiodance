<?php

namespace Tests\Feature;

use App\Models\Reservation;
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
            $booking = Reservation::factory()->create(['status' => $status]);
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
        foreach (array_keys(Reservation::STATUSES) as $status) {
            $records[$status] = Reservation::factory()->create(['status' => $status]);
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
        $full = Reservation::factory()->create(['status' => 'pending', 'payment_type' => 'full']);
        $dp = Reservation::factory()->create(['status' => 'pending', 'payment_type' => 'dp']);
        $this->get(route('admin.bookings.index', ['tab' => 'requests', 'payment_type' => 'full']))->assertOk()
            ->assertSee($full->booking_code)->assertDontSee($dp->booking_code);
        $this->get(route('admin.bookings.index', ['tab' => 'requests', 'payment_type' => 'dp']))->assertOk()
            ->assertSee($dp->booking_code)->assertDontSee($full->booking_code);
    }

    public function test_active_dp_action_tracks_settlement_verification(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Reservation::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'dp', 'total_price' => 300000]);
        $url = route('admin.bookings.index', ['tab' => 'active']);
        $action = route('admin.bookings.edit', $booking).'#pelunasan';
        $this->get($url)->assertOk()->assertSee('DP - Belum Lunas')->assertSee('Sisa: Rp 150.000')->assertSee($action);
        $settlement = $booking->settlement()->create(['amount' => 150000, 'payment_method' => 'bank_transfer', 'payment_proof' => 'settlement-proofs/test.jpg', 'status' => 'pending']);
        $this->get($url)->assertOk()->assertSee('Verifikasi Pelunasan')->assertSee($action);
        $settlement->update(['status' => 'verified']);
        $this->get($url)->assertOk()->assertDontSee('DP - Belum Lunas')->assertDontSee($action);
        $this->assertSame('dp', $booking->fresh()->payment_type);
    }

    public function test_accepting_request_unlocks_edit_in_active_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Reservation::factory()->create(['status' => 'pending']);
        $this->get(route('admin.bookings.index'))->assertOk()->assertSee('Terima')->assertSee('Tolak');
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertRedirect();
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()->assertSee(route('admin.bookings.edit', $booking));
        $this->get(route('admin.bookings.edit', $booking))->assertOk();
        $this->put(route('admin.bookings.update', $booking), ['status' => 'confirmed', 'payment_status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $booking->fresh()->payment_status);
    }
}
