<?php

namespace Tests\Feature;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassRegistrationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_render_create_search_and_update_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create();
        $user = User::factory()->create();
        $this->get(route('admin.class-registrations.index'))->assertOk()->assertSee('Belum ada reservasi class.');
        $this->get(route('admin.class-registrations.create'))->assertOk()->assertSee($schedule->danceClass->name);
        $this->post(route('admin.class-registrations.store'), ['user_id' => $user->id, 'class_schedule_id' => $schedule->id, 'price' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.class-registrations.index'));
        $registration = ClassRegistration::firstOrFail();
        $this->assertEquals($schedule->danceClass->price, $registration->price);
        $this->get(route('admin.class-registrations.edit', $registration))->assertOk()->assertSee($user->email);
        $this->get(route('admin.class-registrations.index', ['search' => $user->email]))->assertOk()->assertSee($user->name);
        $this->put(route('admin.class-registrations.update', $registration), ['registration_status' => 'confirmed', 'payment_status' => 'unpaid'])->assertSessionHasErrors('payment_status');
        $this->put(route('admin.class-registrations.update', $registration), ['registration_status' => 'confirmed', 'payment_status' => 'paid'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $registration->fresh()->registration_status);
        $this->put(route('admin.class-registrations.update', $registration), ['registration_status' => 'completed', 'payment_status' => 'paid'])->assertSessionHasErrors('registration_status');
    }

    public function test_capacity_duplicates_and_reactivation_are_checked(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['capacity' => 1]);
        $first = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id]);
        $data = ['class_schedule_id' => $schedule->id, 'user_id' => User::factory()->create()->id];
        $this->post(route('admin.class-registrations.store'), $data)->assertSessionHasErrors('class_schedule_id');
        $this->put(route('admin.class-registrations.update', $first), ['registration_status' => 'cancelled', 'payment_status' => 'unpaid'])->assertSessionHasNoErrors();
        $this->post(route('admin.class-registrations.store'), array_replace($data, ['user_id' => $first->user_id]))->assertSessionHasErrors('user_id');
        $this->post(route('admin.class-registrations.store'), $data)->assertSessionHasNoErrors();
        $this->put(route('admin.class-registrations.update', $first), ['registration_status' => 'pending', 'payment_status' => 'unpaid'])->assertSessionHasErrors('class_schedule_id');
        $this->assertSame('cancelled', $first->fresh()->registration_status);
    }

    public function test_unavailable_schedules_and_inactive_customers_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['date' => today()->subDay()->toDateString()]);
        $user = User::factory()->create();
        $data = ['user_id' => $user->id, 'class_schedule_id' => $schedule->id];
        $this->post(route('admin.class-registrations.store'), $data)->assertSessionHasErrors('class_schedule_id');
        $schedule->update(['date' => today()->addDay()->toDateString(), 'status' => 'inactive']);
        $this->post(route('admin.class-registrations.store'), $data)->assertSessionHasErrors('class_schedule_id');
        $user->update(['status' => 'inactive']);
        $this->post(route('admin.class-registrations.store'), $data)->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('class_registrations', 0);
    }

    public function test_management_requires_staff_access(): void
    {
        $registration = ClassRegistration::factory()->create();
        $requests = [['get', 'index'], ['get', 'create'], ['post', 'store'], ['get', 'edit'], ['put', 'update']];
        foreach ($requests as [$method, $action]) {
            $this->{$method}(route('admin.class-registrations.'.$action, in_array($action, ['edit', 'update']) ? $registration : []))->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($requests as [$method, $action]) {
            $this->{$method}(route('admin.class-registrations.'.$action, in_array($action, ['edit', 'update']) ? $registration : []))->assertForbidden();
        }
    }

    public function test_schedule_cards_include_empty_classes_and_group_participants_without_removing_table(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['date' => '2027-01-20']);
        $emptySchedule = ClassSchedule::factory()->create();
        $participant = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id]);
        ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'registration_status' => 'cancelled']);

        $this->get(route('admin.class-registrations.index', ['tab' => 'classes']))
            ->assertOk()->assertSee('Jadwal &amp; Peserta Class', false)
            ->assertSee('20 Januari 2027')->assertSee($participant->user->name)
            ->assertSee($emptySchedule->danceClass->name)->assertSee('Belum ada peserta yang ikut.')
            ->assertSee('Dibatalkan (1)')->assertSee('Daftar Reservasi')->assertDontSee('<table', false)
            ->assertViewHas('schedules', fn ($schedules) => $schedules->firstWhere('id', $schedule->id)->occupied === 1
                && $schedules->firstWhere('id', $schedule->id)->classRegistrations->count() === 2);
        $this->get(route('admin.class-registrations.index'))->assertOk()
            ->assertSee('<table', false)->assertDontSee('id="class-schedules-heading"', false);
    }
}
