<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Reservation;
use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createPayload(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'studio_id' => Studio::factory()->create(['price_per_hour' => 100000])->id,
            'date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:30',
            'payment_status' => 'unpaid', 'purpose' => 'Latihan',
        ];
    }

    public function test_staff_create_confirmed_booking_with_server_calculated_price(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $data = $this->createPayload();
            $this->get(route('admin.bookings.create'))->assertOk()->assertSee('Tambah Booking');
            $this->post(route('admin.bookings.store'), $data + ['status' => 'pending', 'total_price' => 1])
                ->assertRedirect(route('admin.bookings.index', ['tab' => 'active']))->assertSessionHasNoErrors();
            $booking = Reservation::where('user_id', $data['user_id'])->sole();
            $this->assertSame('confirmed', $booking->status);
            $this->assertSame('150000.00', $booking->total_price);
            $this->assertSame('unpaid', $booking->payment_status);
            $this->assertSame('booked', $booking->studioSchedule->status);
            $this->post(route('admin.bookings.store'), $data)->assertSessionHasErrors('start_time');
        }
    }

    public function test_creation_rejects_conflicts_and_allows_adjacent_times(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->createPayload();
        $schedule = StudioSchedule::factory()->create([
            'studio_id' => $data['studio_id'], 'date' => $data['date'],
            'start_time' => '09:00', 'end_time' => '10:30', 'status' => 'blocked',
        ]);
        $this->post(route('admin.bookings.store'), $data)->assertSessionHasErrors('start_time');
        $schedule->update(['status' => 'available']);
        Reservation::factory()->create(['studio_schedule_id' => $schedule->id, 'status' => 'pending']);
        $this->post(route('admin.bookings.store'), $data)->assertSessionHasErrors('start_time');
        $schedule->reservation->update(['status' => 'cancelled']);
        $class = ClassSchedule::factory()->create([
            'studio_id' => $data['studio_id'], 'date' => $data['date'],
            'start_time' => '11:00', 'end_time' => '12:00', 'status' => 'active',
        ]);
        $this->post(route('admin.bookings.store'), $data)->assertSessionHasErrors('start_time');
        $class->update(['start_time' => '11:30']);
        $this->post(route('admin.bookings.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_creation_access_and_validation(): void
    {
        $this->get(route('admin.bookings.create'))->assertRedirect(route('login'));
        $this->post(route('admin.bookings.store'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.bookings.create'))->assertForbidden();
        $this->post(route('admin.bookings.store'), [])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->createPayload();
        $this->post(route('admin.bookings.store'), array_replace($data, ['end_time' => '09:00']))->assertSessionHasErrors('end_time');
        $this->post(route('admin.bookings.store'), array_replace($data, ['date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('date');
        Studio::find($data['studio_id'])->update(['status' => 'inactive']);
        User::find($data['user_id'])->update(['status' => 'inactive']);
        $this->post(route('admin.bookings.store'), $data)->assertSessionHasErrors(['studio_id', 'user_id']);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_active_bookings_are_sorted_by_schedule_date_and_time(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $bookings = collect();
        foreach ([['+3 days', '09:00'], ['+1 day', '15:00'], ['+1 day', '08:00']] as [$date, $time]) {
            $bookings->push(Reservation::factory()->create([
                'status' => 'confirmed',
                'studio_schedule_id' => StudioSchedule::factory()->create(['date' => today()->modify($date)->toDateString(), 'start_time' => $time])->id,
            ]));
        }
        $this->get(route('admin.bookings.index', ['tab' => 'active']))->assertOk()
            ->assertViewHas('bookings', fn ($items) => $items->pluck('id')->all() === $bookings->reverse()->pluck('id')->all());
    }

    public function test_staff_can_list_filter_and_view_booking_details(): void
    {
        $booking = Reservation::factory()->create(['booking_code' => 'RES-FIND-ME']);
        Reservation::factory()->create(['status' => 'cancelled']);
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('admin.bookings.index'))->assertOk()->assertSee('Kelola Booking');
            foreach ([['search' => 'FIND-ME'], ['status' => 'pending', 'payment_status' => 'unpaid', 'date' => $booking->studioSchedule->date]] as $filters) {
                $this->get(route('admin.bookings.index', $filters))->assertOk()
                    ->assertViewHas('bookings', fn ($items) => $items->total() === 1 && $items->first()->is($booking));
            }
            $this->get(route('admin.bookings.edit', $booking))->assertOk()->assertSee($booking->booking_code)->assertSee($booking->user->email);
        }
    }

    public function test_guest_and_customer_cannot_manage_bookings(): void
    {
        $booking = Reservation::factory()->create();
        $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
        $this->get(route('admin.bookings.edit', $booking))->assertRedirect(route('login'));
        $this->put(route('admin.bookings.update', $booking), [])->assertRedirect(route('login'));
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.bookings.index'))->assertForbidden();
        $this->get(route('admin.bookings.edit', $booking))->assertForbidden();
        $this->put(route('admin.bookings.update', $booking), [])->assertForbidden();
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertForbidden();
    }

    public function test_tabs_separate_unprocessed_active_and_history_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status) {
            Reservation::factory()->create(['status' => $status]);
        }
        foreach (['requests' => ['pending'], 'active' => ['confirmed'], 'history' => ['completed', 'cancelled']] as $tab => $statuses) {
            $this->get(route('admin.bookings.index', ['tab' => $tab]))->assertOk()
                ->assertViewHas('bookings', fn ($items) => $items->total() === count($statuses) && $items->every(fn ($booking) => in_array($booking->status, $statuses)))
                ->assertViewHas('tabCounts', fn ($counts) => $counts->all() === ['requests' => 1, 'active' => 1, 'history' => 2]);
        }
        $this->get(route('admin.bookings.index'))->assertViewHas('tab', 'requests');
    }

    public function test_decisions_move_requests_without_changing_payment_or_notes(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (['accept' => 'confirmed', 'reject' => 'cancelled'] as $decision => $status) {
                $booking = Reservation::factory()->create(['payment_status' => 'paid', 'notes' => 'Catatan awal']);
                $this->patch(route('admin.bookings.decide', $booking), ['decision' => $decision, 'payment_status' => 'unpaid'])
                    ->assertRedirect(route('admin.bookings.index'))->assertSessionHasNoErrors();
                $this->assertSame($status, $booking->fresh()->status);
                $this->assertSame('paid', $booking->fresh()->payment_status);
                $this->assertSame('Catatan awal', $booking->fresh()->notes);
                $this->assertSame($decision === 'accept' ? 'booked' : 'available', $booking->studioSchedule->fresh()->status);
                $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'reject'])->assertSessionHasErrors('decision');
                $this->assertSame($status, $booking->fresh()->status);
            }
        }
    }

    public function test_decisions_validate_input_and_blocked_schedules(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Reservation::factory()->create();
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'invalid'])->assertSessionHasErrors('decision');
        $booking->studioSchedule->update(['status' => 'blocked']);
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'accept'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $booking->fresh()->status);
        $this->patch(route('admin.bookings.decide', $booking), ['decision' => 'reject'])->assertSessionHasNoErrors();
        $this->assertSame('blocked', $booking->studioSchedule->fresh()->status);
    }

    public function test_confirmation_and_completion_update_booking_and_schedule(): void
    {
        $booking = Reservation::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        foreach (['confirmed', 'completed'] as $status) {
            $this->put(route('admin.bookings.update', $booking), ['status' => $status, 'payment_status' => 'paid', 'notes' => 'Pembayaran diterima'])
                ->assertRedirect(route('admin.bookings.index'))->assertSessionHasNoErrors();
            $this->assertSame($status, $booking->fresh()->status);
            $this->assertSame('paid', $booking->fresh()->payment_status);
            $this->assertSame('booked', $booking->studioSchedule->fresh()->status);
        }
    }

    public function test_cancellation_keeps_history_and_cannot_be_reactivated(): void
    {
        $booking = Reservation::factory()->create();
        $booking->studioSchedule->update(['status' => 'booked']);
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->put(route('admin.bookings.update', $booking), ['status' => 'cancelled', 'payment_status' => 'unpaid'])
            ->assertSessionHasNoErrors();
        $this->assertModelExists($booking);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('available', $booking->studioSchedule->fresh()->status);
        $this->put(route('admin.bookings.update', $booking), ['status' => 'confirmed', 'payment_status' => 'paid'])
            ->assertSessionHasErrors('status');
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_invalid_status_and_unpaid_completion_do_not_change_booking(): void
    {
        $booking = Reservation::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.bookings.update', $booking), ['status' => 'invalid', 'payment_status' => 'invalid'])
            ->assertSessionHasErrors(['status', 'payment_status']);
        $this->put(route('admin.bookings.update', $booking), ['status' => 'completed', 'payment_status' => 'unpaid'])
            ->assertSessionHasErrors('payment_status');
        $this->assertSame('pending', $booking->fresh()->status);
        $booking->studioSchedule->update(['status' => 'blocked']);
        $this->put(route('admin.bookings.update', $booking), ['status' => 'confirmed', 'payment_status' => 'paid'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $booking->fresh()->status);
    }
}
