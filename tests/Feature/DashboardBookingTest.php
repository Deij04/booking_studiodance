<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_all_customers_and_orders_upcoming_bookings(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->setTime(12, 0));
        $studio = Studio::factory()->create();
        $pending = Booking::factory()->recycle($studio)->create();
        Booking::factory()->recycle($studio)->create(['status' => 'cancelled']);
        Booking::factory()->recycle($studio)->create(['status' => 'completed']);
        $later = Booking::factory()->recycle($studio)->create(['status' => 'confirmed', 'studio_schedule_id' => StudioSchedule::factory()->recycle($studio)->create(['date' => today()->addDays(2)->toDateString()])->id]);
        $near = Booking::factory()->recycle($studio)->create(['status' => 'confirmed', 'studio_schedule_id' => StudioSchedule::factory()->recycle($studio)->create(['date' => today()->addDay()->toDateString()])->id]);
        Booking::factory()->recycle($studio)->create(['status' => 'confirmed', 'studio_schedule_id' => StudioSchedule::factory()->recycle($studio)->create(['date' => today()->toDateString(), 'start_time' => '08:00:00', 'end_time' => '10:00:00'])->id]);
        $ongoing = Booking::factory()->recycle($studio)->create(['status' => 'confirmed', 'studio_schedule_id' => StudioSchedule::factory()->recycle($studio)->create(['date' => today()->toDateString(), 'start_time' => '11:00:00', 'end_time' => '13:00:00'])->id]);
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('admin.dashboard'))->assertOk()
                ->assertViewHas('reservationCount', 7)->assertViewHas('pendingCount', 1)
                ->assertViewHas('confirmedCount', 4)->assertViewHas('historyCount', 2)
                ->assertViewHas('pendingReservations', fn ($items) => $items->pluck('id')->all() === [$pending->id])
                ->assertViewHas('upcomingReservations', fn ($items) => $items->pluck('id')->all() === [$ongoing->id, $near->id, $later->id])
                ->assertSee($pending->user->name)->assertSee(route('admin.bookings.create'))
                ->assertSee(route('admin.bookings.edit', $near));
        }
        $this->patch(route('admin.bookings.decide', $pending), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('pendingCount', 0)->assertViewHas('confirmedCount', 5);
    }

    public function test_empty_dashboard_displays_booking_empty_states(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('reservationCount', 0)
            ->assertSee('Semua pengajuan sudah diurus.')->assertSee('Belum ada jadwal booking mendatang.');
    }
}
