<?php

namespace Tests\Feature;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function data(ClassSchedule $schedule, array $overrides = []): array
    {
        return array_replace([
            'dance_class_id' => $schedule->dance_class_id, 'studio_id' => $schedule->studio_id,
            'date' => $schedule->date, 'start_time' => '18:00', 'end_time' => '19:00',
            'capacity' => 5, 'status' => 'active',
        ], $overrides);
    }

    public function test_schedule_can_be_edited_deactivated_and_reactivated_without_self_conflict(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create();
        $this->get(route('admin.class-schedules.edit', $schedule))->assertOk()->assertSee('Edit Jadwal Class');
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertSessionHasNoErrors();
        $this->assertEquals(5, $schedule->fresh()->capacity);
        $this->patch(route('admin.class-schedules.deactivate', $schedule))->assertSessionHasNoErrors();
        $this->assertSame('inactive', $schedule->fresh()->status);
        $this->get(route('admin.class-registrations.create'))->assertOk()->assertViewHas('schedules', fn ($schedules) => $schedules->isEmpty());
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertSessionHasNoErrors();
        $this->assertSame('active', $schedule->fresh()->status);
    }

    public function test_participants_prevent_deactivation_capacity_reduction_and_class_replacement(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create();
        ClassRegistration::factory()->count(2)->create(['class_schedule_id' => $schedule->id]);
        $this->patch(route('admin.class-schedules.deactivate', $schedule))->assertSessionHasErrors('status');
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule, ['status' => 'inactive']))->assertSessionHasErrors('status');
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule, ['capacity' => 1]))->assertSessionHasErrors('capacity');
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule, ['dance_class_id' => DanceClass::factory()->create()->id]))->assertSessionHasErrors('dance_class_id');
        $schedule->classRegistrations()->update(['registration_status' => 'cancelled']);
        $this->patch(route('admin.class-schedules.deactivate', $schedule))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_registrations', 2);
    }

    public function test_reactivation_and_edits_check_blocks_classes_and_new_operating_hours(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['status' => 'inactive']);
        $block = StudioSchedule::factory()->create(['studio_id' => $schedule->studio_id, 'date' => $schedule->date, 'start_time' => '18:00:00', 'end_time' => '19:00:00', 'status' => 'blocked']);
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertSessionHasErrors('start_time');
        $block->update(['status' => 'available']);
        $other = ClassSchedule::factory()->create(['studio_id' => $schedule->studio_id, 'date' => $schedule->date, 'start_time' => '18:00:00', 'end_time' => '19:00:00']);
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertSessionHasErrors('start_time');
        $other->update(['status' => 'inactive']);
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '08:00', 'closes_at' => '17:00'])->assertSessionHasNoErrors();
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertSessionHasErrors('start_time');
    }

    public function test_past_schedule_cannot_be_rescheduled_and_customer_cannot_edit(): void
    {
        $schedule = ClassSchedule::factory()->create(['date' => today()->subDay()->toDateString()]);
        $this->get(route('admin.class-schedules.edit', $schedule))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.class-schedules.edit', $schedule))->assertForbidden();
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule))->assertForbidden();
        $this->patch(route('admin.class-schedules.deactivate', $schedule))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.class-schedules.update', $schedule), $this->data($schedule, ['date' => today()->addDay()->toDateString()]))->assertSessionHasErrors('date');
        $this->patch(route('admin.class-schedules.deactivate', $schedule))->assertSessionHasNoErrors();
    }
}
