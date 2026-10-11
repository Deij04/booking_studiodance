<?php

namespace Tests\Feature;

use App\Models\ClassPayment;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerClassReservationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchedule(array $attributes = []): ClassSchedule
    {
        return ClassSchedule::factory()->create($attributes + [
            'dance_class_id' => DanceClass::factory()->create(['status' => 'active', 'price' => 85000])->id,
            'studio_id' => Studio::factory()->create(['status' => 'active'])->id,
            'date' => today()->addDays(2)->toDateString(), 'start_time' => '10:00:00', 'end_time' => '11:00:00',
            'status' => 'active', 'capacity' => 1,
        ]);
    }

    private function data(ClassSchedule $schedule): array
    {
        return ['payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'), 'class_schedule_id' => $schedule->id, 'payment_method' => 'qris', 'payment_type' => 'full', 'notes' => 'First session'];
    }

    public function test_customer_reserves_self_and_admin_can_verify_private_payment(): void
    {
        Storage::fake('local');
        $schedule = $this->makeSchedule();
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($customer)->get(route('user.classes.reserve', $schedule->danceClass))
            ->assertOk()->assertSee('Participant Name')->assertSee('Ajukan Reservasi Class');
        $this->post(route('user.classes.store', $schedule->danceClass), array_replace($this->data($schedule), [
            'user_id' => $other->id, 'price' => 1, 'payment_status' => 'paid',
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
        ]))->assertRedirect(route('user.history'));
        $registration = ClassRegistration::sole();
        $this->assertSame($customer->id, $registration->user_id);
        $this->assertSame('85000.00', $registration->price);
        $this->assertSame('pending', $registration->registration_status);
        $this->assertSame('unpaid', $registration->payment_status);
        $this->assertSame('qris', $registration->payment->payment_method);
        Storage::disk('local')->assertExists($registration->payment->payment_proof);
        $this->get(route('admin.class-registrations.payment-proof', $registration))->assertForbidden();
        $this->get(route('user.history'))->assertOk()->assertDontSee('Status verifikasi')->assertSee('Status Reservasi Kelas');
        $admin = User::factory()->admin()->create(['name' => 'Class Verifier']);
        $this->actingAs($admin)->get(route('admin.class-registrations.payment-proof', $registration))->assertOk();
        $this->get(route('admin.class-registrations.edit', $registration))->assertOk()->assertSee('Lihat bukti pembayaran');
        $this->put(route('admin.class-registrations.update', $registration), ['registration_status' => 'confirmed', 'payment_status' => 'paid'])->assertRedirect();
        $this->assertSame($admin->id, $registration->payment->fresh()->verified_by);
        $this->actingAs($customer)->get(route('user.history'))->assertOk()->assertDontSee('Status verifikasi')->assertDontSee('Terverifikasi')->assertDontSee('Class Verifier');
    }

    public function test_duplicate_and_full_sessions_are_rejected_and_failed_proofs_are_cleaned(): void
    {
        Storage::fake('local');
        $schedule = $this->makeSchedule();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->postJson(route('user.classes.store', $schedule->danceClass), $this->data($schedule))->assertRedirect();
        $this->postJson(route('user.classes.store', $schedule->danceClass), $this->data($schedule))->assertUnprocessable()->assertJsonValidationErrors('class_schedule_id');
        $this->actingAs($second)->postJson(route('user.classes.store', $schedule->danceClass), array_replace($this->data($schedule), [
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('class_schedule_id');
        $this->assertDatabaseCount('class_registrations', 1);
        $this->assertDatabaseCount('class_payments', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('class-payment-proofs'));
    }

    public function test_invalid_payment_wrong_class_and_inactive_sessions_do_not_create_registration(): void
    {
        Storage::fake('local');
        $schedule = $this->makeSchedule();
        $other = $this->makeSchedule();
        $this->actingAs(User::factory()->create());
        $withoutProof = $this->data($schedule); unset($withoutProof['payment_proof']);
        $this->postJson(route('user.classes.store', $schedule->danceClass), $withoutProof)->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $this->postJson(route('user.classes.store', $schedule->danceClass), array_replace($this->data($schedule), ['payment_method' => 'cash']))->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->postJson(route('user.classes.store', $schedule->danceClass), $this->data($other))->assertUnprocessable()->assertJsonValidationErrors('class_schedule_id');
        $this->postJson(route('user.classes.store', $schedule->danceClass), array_replace($this->data($schedule), [
            'payment_proof' => UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $schedule->update(['status' => 'inactive']);
        $this->postJson(route('user.classes.store', $schedule->danceClass), $this->data($schedule))->assertUnprocessable()->assertJsonValidationErrors('class_schedule_id');
        $this->assertDatabaseCount('class_registrations', 0);
        $this->assertDatabaseCount('class_payments', 0);
    }

    public function test_customer_flow_requires_login_and_disallows_staff(): void
    {
        $schedule = $this->makeSchedule();
        $this->get(route('user.classes.reserve', $schedule->danceClass))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get(route('user.classes.reserve', $schedule->danceClass))->assertForbidden();
    }
}
