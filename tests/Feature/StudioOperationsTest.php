<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\BookingSetting;
use App\Models\User;
use App\Services\BookingAvailability;
use App\Services\BookingHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_hours_are_persisted_and_used_by_calendar_booking_and_class_forms(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $studio = Studio::factory()->create();
        $this->get(route('admin.studio-operations.index'))->assertRedirect(route('admin.studios.index', ['tab' => 'operations']));
        $this->get(route('admin.studios.index', ['tab' => 'operations']))->assertOk()->assertSee('Jam Operasional')->assertDontSee('+ Tambah Studio');
        $this->get(route('admin.studios.index'))->assertOk()->assertSee('+ Tambah Studio')->assertDontSee('Simpan Jam Operasional');
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '10:00', 'closes_at' => '17:00'])->assertSessionHasNoErrors();
        $this->assertSame(['opens_at' => '10:00', 'closes_at' => '17:00'], app(BookingHours::class)->current());
        $date = today()->addDay()->toDateString();
        $slots = app(BookingAvailability::class)->month($studio, substr($date, 0, 7))[$date];
        $this->assertArrayHasKey('10:00', $slots);
        $this->assertArrayNotHasKey('09:30', $slots);
        $this->assertArrayNotHasKey('16:30', $slots);
        $this->get(route('admin.bookings.create'))->assertOk()->assertSee('10:00')->assertSee('17:00');
        $this->get(route('admin.class-schedules.create'))->assertOk()->assertSee('value="10:00"', false)->assertDontSee('value="09:30"', false);
        $this->post(route('admin.bookings.store'), ['studio_id' => $studio->id, 'user_id' => User::factory()->create()->id, 'date' => $date, 'start_time' => '09:00', 'end_time' => '10:00', 'payment_status' => 'unpaid'])->assertSessionHasErrors('start_time');
    }

    public function test_hours_cannot_exclude_future_classes_or_bookings_and_allow_exact_boundary(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['start_time' => '18:00:00', 'end_time' => '19:00:00']);
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '08:00', 'closes_at' => '18:00'])->assertSessionHasErrors('opens_at');
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '08:00', 'closes_at' => '19:00'])->assertSessionHasNoErrors();
        $schedule->update(['status' => 'inactive']);
        Booking::factory()->create(['studio_id' => $schedule->studio_id, 'date' => $schedule->date, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'status' => 'pending']);
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '09:00', 'closes_at' => '19:00'])->assertSessionHasErrors('opens_at');
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '20:00', 'closes_at' => '08:00'])->assertSessionHasErrors('closes_at');
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '08:00', 'closes_at' => '08:30'])->assertSessionHasErrors('closes_at');
    }

    public function test_blocking_and_unblocking_changes_availability_without_touching_other_studios(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $studio = Studio::factory()->create();
        $other = Studio::factory()->create();
        $date = today()->addDay()->toDateString();
        $data = ['studio_id' => $studio->id, 'date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'block_reason' => 'Perbaikan lantai'];
        $this->post(route('admin.studio-operations.block'), $data)->assertSessionHasNoErrors();
        $block = BookingSetting::blocked()->firstOrFail();
        $this->get(route('admin.studios.index', ['tab' => 'operations']))->assertOk()->assertSee('Perbaikan lantai')->assertSee('Buka Blokir');
        $slots = fn ($target) => app(BookingAvailability::class)->month($target, substr($date, 0, 7))[$date];
        $this->assertArrayNotHasKey('10:00', $slots($studio));
        $this->assertArrayHasKey('10:00', $slots($other));
        $this->post(route('admin.studio-operations.block'), $data)->assertSessionHasErrors('start_time');
        $this->patch(route('admin.studio-operations.unblock', $block))->assertSessionHasNoErrors();
        $this->assertArrayHasKey('10:00', $slots($studio));
        $this->assertSame('available', $block->fresh()->status);
        $this->assertSame('Perbaikan lantai', $block->fresh()->block_reason);
        $this->patch(route('admin.studio-operations.unblock', $block))->assertSessionHasErrors('block');
    }

    public function test_blocks_reject_class_and_booking_conflicts_and_cannot_release_bookings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create(['start_time' => '18:00:00', 'end_time' => '19:00:00']);
        $data = ['studio_id' => $schedule->studio_id, 'date' => $schedule->date, 'start_time' => '18:30', 'end_time' => '20:00', 'block_reason' => 'Maintenance'];
        $this->post(route('admin.studio-operations.block'), $data)->assertSessionHasErrors('start_time');
        $schedule->update(['status' => 'inactive']);
        $slot = Booking::factory()->create(['studio_id' => $schedule->studio_id, 'date' => $schedule->date, 'start_time' => '18:00:00', 'end_time' => '19:00:00']);
        $this->post(route('admin.studio-operations.block'), $data)->assertSessionHasErrors('start_time');
        $this->assertSame('pending', $slot->fresh()->status);
        $this->post(route('admin.studio-operations.block'), array_replace($data, ['date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('date');
    }

    public function test_management_is_restricted_to_staff(): void
    {
        $slot = BookingSetting::factory()->create(['status' => 'blocked']);
        $requests = [['get', 'index'], ['put', 'hours'], ['post', 'block'], ['patch', 'unblock']];
        foreach ($requests as [$method, $action]) {
            $this->{$method}(route('admin.studio-operations.'.$action, $action === 'unblock' ? $slot : []))->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($requests as [$method, $action]) {
            $this->{$method}(route('admin.studio-operations.'.$action, $action === 'unblock' ? $slot : []))->assertForbidden();
        }
    }

    public function test_time_fields_reject_minutes_outside_half_hour_intervals(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ClassSchedule::factory()->create();
        $data = ['studio_id' => $schedule->studio_id, 'date' => today()->addDay()->toDateString(), 'start_time' => '10:15', 'end_time' => '11:45'];
        $this->put(route('admin.studio-operations.hours'), ['opens_at' => '08:15', 'closes_at' => '22:45'])->assertSessionHasErrors(['opens_at', 'closes_at']);
        $this->post(route('admin.studio-operations.block'), $data + ['block_reason' => 'Maintenance'])->assertSessionHasErrors(['start_time', 'end_time']);
        $this->post(route('admin.class-schedules.store'), $data + ['dance_class_id' => $schedule->dance_class_id, 'capacity' => 5])->assertSessionHasErrors(['start_time', 'end_time']);
        $this->post(route('admin.bookings.store'), $data + ['user_id' => User::factory()->create()->id, 'payment_status' => 'unpaid'])->assertSessionHasErrors(['start_time', 'end_time']);
        $this->get(route('admin.studios.index', ['tab' => 'operations']))->assertOk()
            ->assertSee('value="08:30"', false)->assertDontSee('value="08:15"', false);
    }

    public function test_booking_setting_blocks_studio_rentals_without_changing_classes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $studio = Studio::factory()->create(['status' => 'active']);
        $class = ClassSchedule::factory()->create(['studio_id' => $studio->id, 'date' => today()->addDays(2)->toDateString()]);
        $originalClass = $class->getAttributes();
        $date = today()->addDay()->toDateString();
        $this->get(route('admin.studios.index', ['tab' => 'operations']))->assertOk()->assertSee('Booking Setting')->assertSee('Blokir Jam Penyewaan Studio');
        $this->post(route('admin.studio-operations.block'), ['studio_id' => $studio->id, 'date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'block_reason' => 'Acara internal'])->assertSessionHasNoErrors();
        $booking = ['studio_id' => $studio->id, 'user_id' => User::factory()->create()->id, 'date' => $date, 'start_time' => '10:00', 'end_time' => '11:00', 'purpose' => 'Group Training', 'payment_method' => 'cash', 'payment_type' => 'full'];
        $this->post(route('admin.bookings.store'), $booking)->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('bookings', 0);
        $block = BookingSetting::where('status', 'blocked')->sole();
        $this->patch(route('admin.studio-operations.unblock', $block))->assertSessionHasNoErrors();
        $this->post(route('admin.bookings.store'), $booking)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals($originalClass, $class->fresh()->getAttributes());
    }
    public function test_manual_block_must_be_within_operating_hours(): void
    {
        config(['booking.opens_at' => '08:00', 'booking.closes_at' => '22:00']);
        $this->actingAs(User::factory()->admin()->create());
        $data = ['studio_id' => Studio::factory()->create()->id, 'date' => today()->addDay()->toDateString(), 'start_time' => '07:00', 'end_time' => '09:00', 'block_reason' => 'Maintenance'];
        $this->post(route('admin.studio-operations.block'), $data)->assertSessionHasErrors('start_time');
        $this->post(route('admin.studio-operations.block'), array_replace($data, ['start_time' => '21:00', 'end_time' => '23:00']))->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('booking_settings', 0);
    }
}
