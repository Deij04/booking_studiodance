<?php
namespace Tests\Feature;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ClassVerificationTransferTest extends TestCase {
    use RefreshDatabase;
    private function record(): ClassRegistration {
        $schedule = ClassSchedule::factory()->create(['date' => today()->addDays(2)->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'active', 'capacity' => 2]);
        $record = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'registration_status' => 'pending', 'payment_status' => 'unpaid']);
        $record->payment()->updateOrCreate([], ['payment_type' => 'full', 'payment_method' => 'qris', 'payment_proof' => 'class-payment-proofs/proof.pdf']);
        return $record;
    }
    public function test_admin_class_reservation_saves_full_payment_method_and_proof(): void {
        Storage::fake('local');
        $schedule = $this->record()->classSchedule;
        $customer = User::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $data = ['user_id' => $customer->id, 'class_schedule_id' => $schedule->id, 'payment_method' => 'bank_transfer', 'payment_type' => 'full'];
        $this->postJson(route('admin.class-registrations.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $this->post(route('admin.class-registrations.store'), $data + ['payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf')])->assertSessionHasNoErrors()->assertRedirect();
        $record = ClassRegistration::where('user_id', $customer->id)->sole();
        $this->assertSame('full', $record->payment->payment_type);
        $this->assertSame('bank_transfer', $record->payment->payment_method);
        Storage::disk('local')->assertExists($record->payment->payment_proof);
    }
    public function test_verification_has_only_decisions_and_records_actual_admin(): void {
        $record = $this->record(); $admin = User::factory()->admin()->create(); $this->actingAs($admin);
        $this->get(route('admin.class-registrations.verify', $record))->assertOk()->assertSee('Verifikasi')->assertSee('Tolak')
            ->assertDontSee('name="payment_status"', false)->assertDontSee('name="registration_status"', false);
        $this->patch(route('admin.class-registrations.decide', $record), ['decision' => 'verified'])->assertRedirect();
        $this->assertSame('paid', $record->fresh()->payment_status);
        $this->assertSame('confirmed', $record->fresh()->registration_status);
        $this->assertSame($admin->id, $record->payment->fresh()->verified_by);
        $this->patch(route('admin.class-registrations.decide', $record), ['decision' => 'rejected', 'rejection_reason' => 'bad'])->assertSessionHasErrors('decision');
        $this->get(route('admin.class-registrations.verify', $record))->assertRedirect(route('admin.class-registrations.show', $record));
        $this->put(route('admin.class-registrations.update', $record), ['payment_status' => 'unpaid', 'registration_status' => 'cancelled'])->assertSessionHasErrors(['payment_status', 'registration_status']);
        $this->assertSame('confirmed', $record->fresh()->registration_status);
    }
    public function test_rejected_application_releases_seat_and_customer_can_register_again(): void {
        Storage::fake('local'); $record = $this->record();
        $schedule = $record->classSchedule; $customer = $record->user;
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.class-registrations.decide', $record), ['decision' => 'rejected', 'rejection_reason' => 'Nominal salah'])->assertRedirect();
        $this->assertSame('cancelled', $record->fresh()->registration_status);
        $this->assertSame('Ditolak', $record->fresh()->display_status);
        $this->assertSame(0, $schedule->classRegistrations()->where('registration_status', '!=', 'cancelled')->count());
        $this->actingAs($customer)->get(route('user.history.detail', ['type' => 'class', 'id' => $record->id]))->assertOk()
            ->assertSee('Pengajuan Reservasi Ditolak')->assertSee('Daftar Class Lagi')->assertDontSee('Unggah Ulang');
        $this->get(route('user.classes.reserve', $schedule->danceClass))->assertOk()->assertViewHas('registeredIds', fn ($ids) => !in_array($schedule->id, $ids));
        $this->post(route('user.classes.store', $schedule->danceClass), ['class_schedule_id' => $schedule->id, 'payment_method' => 'qris', 'payment_type' => 'full',
            'payment_proof' => UploadedFile::fake()->create('new.pdf', 12, 'application/pdf')])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('class_registrations', 2);
        $this->assertSame('cancelled', $record->fresh()->registration_status);
        $this->assertSame(1, $schedule->classRegistrations()->where('registration_status', 'pending')->where('user_id', $customer->id)->count());
    }
    public function test_transfer_preserves_participant_payment_and_moves_seat(): void {
        $record = $this->record(); $record->payment->update(['verification_status' => 'verified']); $record->update(['registration_status' => 'confirmed']);
        $source = $record->classSchedule; $target = ClassSchedule::factory()->create(['dance_class_id' => $source->dance_class_id, 'studio_id' => $source->studio_id, 'date' => today()->addDays(3)->toDateString(), 'status' => 'active', 'capacity' => 2]);
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.class-registrations.edit', $record))->assertOk()->assertSee('Edit Reservasi Class')->assertDontSee('name="registration_status"', false);
        $price = $record->price; $payment = $record->payment->id; $user = $record->user_id;
        $this->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $target->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($target->id, $record->fresh()->class_schedule_id); $this->assertSame($source->danceClass->price, $record->fresh()->price); $this->assertEquals($price, $record->fresh()->payment_amount);
        $this->assertSame($user, $record->fresh()->user_id); $this->assertSame($payment, $record->fresh()->payment->id);
        $this->assertSame(0, $source->classRegistrations()->count()); $this->assertSame(1, $target->classRegistrations()->count());
    }
    public function test_full_schedule_cannot_receive_transfer(): void {
        $record = $this->record(); $record->payment->update(['verification_status' => 'verified']); $record->update(['registration_status' => 'confirmed']);
        $source = $record->classSchedule; $target = ClassSchedule::factory()->create(['dance_class_id' => $source->dance_class_id, 'studio_id' => $source->studio_id, 'date' => today()->addDays(3)->toDateString(), 'status' => 'active', 'capacity' => 1]);
        ClassRegistration::factory()->create(['class_schedule_id' => $target->id, 'registration_status' => 'confirmed']);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $target->id])->assertSessionHasErrors('class_schedule_id');
        $this->assertSame($source->id, $record->fresh()->class_schedule_id);
    }
    public function test_class_completes_automatically_after_end_and_started_class_cannot_transfer(): void {
        $record = $this->record(); $record->payment->update(['verification_status' => 'verified']); $record->update(['registration_status' => 'confirmed']);
        $schedule = $record->classSchedule;
        $this->actingAs(User::factory()->admin()->create());
        $this->travelTo(\Carbon\Carbon::parse($schedule->date.' '.$schedule->start_time));
        $this->get(route('admin.class-registrations.edit', $record))->assertOk();
        $this->assertSame('confirmed', $record->fresh()->registration_status);
        $this->travelTo(\Carbon\Carbon::parse($schedule->date.' '.$schedule->end_time));
        $this->get(route('admin.class-registrations.show', $record))->assertOk();
        $this->assertSame('completed', $record->fresh()->registration_status);
    }

    public function test_edit_can_correct_participant_on_same_full_schedule_without_changing_payment(): void {
        $record = $this->record(); $record->classSchedule->update(['capacity' => 1]);
        $customer = User::factory()->create(); $admin = User::factory()->admin()->create();
        $paymentId = $record->payment->id;
        $this->actingAs($admin)->get(route('admin.class-registrations.edit', $record))->assertOk()->assertSee('name="user_id"', false);
        $this->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $record->class_schedule_id, 'user_id' => $customer->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($customer->id, $record->fresh()->user_id);
        $this->assertSame($paymentId, $record->fresh()->payment->id);
        $this->assertSame('pending', $record->fresh()->registration_status);
        $this->assertSame('unpaid', $record->fresh()->payment_status);
        $this->assertSame(1, $record->classSchedule->classRegistrations()->count());
    }
    public function test_edit_cannot_duplicate_existing_participant(): void {
        $record = $this->record(); $other = User::factory()->create();
        ClassRegistration::factory()->create(['class_schedule_id' => $record->class_schedule_id, 'user_id' => $other->id, 'registration_status' => 'confirmed']);
        $original = $record->user_id;
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $record->class_schedule_id, 'user_id' => $other->id])->assertSessionHasErrors('class_schedule_id');
        $this->assertSame($original, $record->fresh()->user_id);
    }

    public function test_reservation_tabs_separate_requests_active_and_history(): void {
        $pending = $this->record(); $pending->user->update(['name' => 'Participant Pending Unique']);
        $schedule = $pending->classSchedule;
        $active = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'user_id' => User::factory()->create(['name' => 'Participant Active Unique'])->id, 'registration_status' => 'confirmed', 'payment_status' => 'paid']);
        $completed = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'user_id' => User::factory()->create(['name' => 'Participant Completed Unique'])->id, 'registration_status' => 'completed', 'payment_status' => 'paid']);
        $rejected = ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'user_id' => User::factory()->create(['name' => 'Participant Rejected Unique'])->id, 'registration_status' => 'cancelled', 'payment_status' => 'unpaid']);
        $rejected->payment()->updateOrCreate([], ['payment_method' => 'qris', 'payment_type' => 'full', 'verification_status' => 'rejected']);
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.class-registrations.index'))->assertOk()->assertSee('Jadwal &amp; Peserta Class', false)->assertDontSee('Pengajuan Reservasi')
            ->assertSee(route('admin.class-registrations.verify', $pending));
        $this->get(route('admin.class-registrations.index', ['tab' => 'active']))->assertOk()->assertSee('Participant Active Unique')->assertDontSee('Participant Pending Unique')->assertDontSee('Participant Completed Unique');
        $this->get(route('admin.class-registrations.index', ['tab' => 'history']))->assertOk()->assertSee('Participant Completed Unique')->assertSee('Participant Rejected Unique')
            ->assertDontSee('Participant Active Unique')->assertDontSee('Participant Pending Unique')->assertDontSee(route('admin.class-registrations.edit', $rejected));
        $this->get(route('admin.class-registrations.index', ['tab' => 'history', 'status' => 'cancelled']))->assertOk()->assertSee('Participant Rejected Unique')->assertDontSee('Participant Completed Unique');
        $this->get(route('admin.class-registrations.index', ['tab' => 'classes']))->assertOk()->assertSee('Jadwal &amp; Peserta Class', false)
            ->assertSee(route('admin.class-registrations.verify', $pending))
            ->assertDontSee(route('admin.class-registrations.verify', $active))
            ->assertDontSee(route('admin.class-registrations.verify', $rejected));
    }


    public function test_cross_class_transfer_requires_difference_and_preserves_multiple_payments(): void {
        $record = $this->record();
        $record->payment->update(['amount' => 50000, 'verification_status' => 'verified']); $record->update(['price' => 50000, 'registration_status' => 'confirmed']);
        $record->payment->update(['verification_status' => 'verified']);
        $target = ClassSchedule::factory()->create(['date' => today()->addDays(3)->toDateString(), 'status' => 'active']);
        $target->danceClass->update(['price' => 70000]);
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $target->id])->assertSessionHasNoErrors();
        $summary = app(\App\Services\PaymentSummary::class)->for($record->fresh());
        $this->assertEquals(50000, $summary['paid']); $this->assertEquals(20000, $summary['remaining']); $this->assertTrue($summary['canSettle']);
        $this->get(route('admin.class-registrations.index', ['tab' => 'active']))->assertOk()->assertSee('Belum Lunas')->assertSee('Pelunasan');
        $this->post(route('admin.class-registrations.settle', $record), ['payment_method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertEquals(0, app(\App\Services\PaymentSummary::class)->for($record->fresh())['remaining']);
        $other = ClassSchedule::factory()->create(['date' => today()->addDays(4)->toDateString(), 'status' => 'active']);
        $other->danceClass->update(['price' => 90000]);
        $this->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertEquals(20000, app(\App\Services\PaymentSummary::class)->for($record->fresh())['remaining']);
        $this->post(route('admin.class-registrations.settle', $record), ['payment_method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(2, $record->settlements()->count());
        $this->assertEquals(90000, app(\App\Services\PaymentSummary::class)->for($record->fresh())['paid']);
        $this->get(route('admin.class-registrations.show', $record))->assertOk()->assertSee('Lunas');
    }
    public function test_transfer_can_target_running_class_but_not_ended_class(): void {
        $record = $this->record();
        $this->travelTo(today()->setTime(10, 30));
        $target = ClassSchedule::factory()->create(['date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'active']);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $target->id])->assertSessionHasNoErrors();
        $ended = ClassSchedule::factory()->create(['date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'active']);
        $this->put(route('admin.class-registrations.update', $record), ['class_schedule_id' => $ended->id])->assertSessionHasErrors('class_schedule_id');
    }

    public function test_customer_transfer_balance_is_verified_separately_and_prevents_early_completion(): void {
        Storage::fake('local');
        $record = $this->record(); $record->payment->update(['amount' => 50000, 'verification_status' => 'verified']); $record->update(['price' => 70000, 'registration_status' => 'confirmed']);
        $record->payment->update(['verification_status' => 'verified']);
        $record->classSchedule->update(['date' => today()->subDay()->toDateString()]);
        app(\App\Services\BookingCompletion::class)->run();
        $this->assertSame('confirmed', $record->fresh()->registration_status);
        $params = ['type' => 'class', 'id' => $record->id];
        $this->actingAs($record->user)->get(route('user.history.detail', $params))->assertOk()->assertSee('Ajukan Pelunasan')->assertSee('20.000');
        $this->post(route('user.history.settle', $params), ['payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->create('balance.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $settlement = $record->fresh()->settlement;
        $this->assertEquals(20000, $settlement->amount);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.settlements.decide', $settlement), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $settlement->fresh()->verified_by);
        $this->assertSame('completed', $record->fresh()->registration_status);
    }
}
