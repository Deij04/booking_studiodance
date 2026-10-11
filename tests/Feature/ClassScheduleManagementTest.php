<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\BookingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function data(): array
    {
        return [
            'dance_class_id' => DanceClass::factory()->create(['capacity' => 20])->id,
            'studio_id' => Studio::factory()->create(['capacity' => 15])->id,
            'date' => today()->addDay()->toDateString(),
            'start_time' => '18:00', 'end_time' => '19:00', 'capacity' => 10,
        ];
    }

    public function test_staff_can_create_a_schedule_and_use_it_for_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->data();
        $this->get(route('admin.class-registrations.index', ['tab' => 'classes']))->assertOk()->assertSee('Tambah Jadwal Class');
        $this->get(route('admin.class-schedules.create'))->assertOk()->assertSee('Simpan Jadwal');
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.class-registrations.index', ['tab' => 'classes']));
        $schedule = ClassSchedule::firstOrFail();
        $this->assertSame('active', $schedule->status);
        $this->get(route('admin.class-registrations.create'))->assertOk()->assertSee($schedule->danceClass->name);
        $this->post(route('admin.class-registrations.store'), ['class_schedule_id' => $schedule->id, 'user_id' => User::factory()->create()->id])->assertSessionHasNoErrors();
    }

    public function test_overlaps_are_rejected_but_adjacent_classes_are_allowed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->data();
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasErrors('start_time');
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['start_time' => '18:30', 'end_time' => '20:00']))->assertSessionHasErrors('start_time');
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['start_time' => '19:00', 'end_time' => '20:00']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_schedules', 2);
    }

    public function test_bookings_and_blocked_studio_times_prevent_creation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->data();
        $slot = BookingSetting::factory()->create([
            'studio_id' => $data['studio_id'], 'date' => $data['date'],
            'start_time' => '18:00:00', 'end_time' => '19:00:00', 'status' => 'available',
        ]);
        $booking = Booking::factory()->create(['studio_id' => $slot->studio_id, 'date' => $slot->date, 'start_time' => $slot->start_time, 'end_time' => $slot->end_time, 'status' => 'pending']);
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasErrors('start_time');
        $booking->update(['status' => 'cancelled']);
        $slot->update(['status' => 'blocked']);
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasErrors('start_time');
        $slot->update(['status' => 'available']);
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasNoErrors();
    }

    public function test_invalid_capacity_times_and_inactive_classes_are_rejected(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $data = $this->data();
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['capacity' => 16]))->assertSessionHasErrors('capacity');
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('date');
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['end_time' => '17:00']))->assertSessionHasErrors('end_time');
        $this->post(route('admin.class-schedules.store'), array_replace($data, ['start_time' => '06:00']))->assertSessionHasErrors('start_time');
        DanceClass::findOrFail($data['dance_class_id'])->update(['status' => 'inactive']);
        $this->post(route('admin.class-schedules.store'), $data)->assertSessionHasErrors('dance_class_id');
        $this->assertDatabaseCount('class_schedules', 0);
    }

    public function test_guests_and_customers_cannot_manage_schedules(): void
    {
        $this->get(route('admin.class-schedules.create'))->assertRedirect(route('login'));
        $this->post(route('admin.class-schedules.store'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.class-schedules.create'))->assertForbidden();
        $this->post(route('admin.class-schedules.store'))->assertForbidden();
    }
}
