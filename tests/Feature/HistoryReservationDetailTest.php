<?php

namespace Tests\Feature;

use App\Models\ClassPayment;
use App\Models\ClassRegistration;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoryReservationDetailTest extends TestCase
{
    use RefreshDatabase;

    public static function types(): array { return [['studio'], ['class']]; }

    private function booking(string $type, User $user)
    {
        if ($type === 'studio') return Reservation::factory()->create(['user_id' => $user->id, 'total_price' => 180000, 'payment_type' => 'dp', 'payment_status' => 'paid', 'status' => 'confirmed']);
        $record = ClassRegistration::factory()->create(['user_id' => $user->id, 'price' => 180000, 'payment_status' => 'paid', 'registration_status' => 'confirmed']);
        $record->payment()->create(['payment_method' => 'qris', 'payment_type' => 'dp']);
        return $record;
    }

    #[DataProvider('types')]
    public function test_dp_and_settlement_are_separate_and_owner_only(string $type): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $record = $this->booking($type, $user);
        $params = ['type' => $type, 'id' => $record->id];
        $this->actingAs($user)->get(route('user.history.detail', $params))->assertOk()->assertSee('DP (50%)')->assertSee('Belum diajukan')->assertSee('90.000,00');
        $this->post(route('user.history.settle', $params), ['payment_method' => 'bank_transfer', 'amount' => 1, 'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf')])->assertRedirect();
        $settlement = $record->settlement()->sole();
        $this->assertSame('90000.00', $settlement->amount);
        $this->assertSame('pending', $settlement->status);
        $this->assertSame('paid', $record->fresh()->payment_status);
        $this->get(route('user.history.detail', $params))->assertOk()->assertSee('Menunggu Verifikasi')->assertDontSee('Ajukan Pelunasan');
        $this->postJson(route('user.history.settle', $params), ['payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->create('duplicate.pdf', 12, 'application/pdf')])->assertUnprocessable();
        $this->assertCount(1, Storage::disk('local')->allFiles('settlement-proofs'));
        $this->actingAs(User::factory()->create())->get(route('user.history.detail', $params))->assertNotFound();
        $this->post(route('user.history.settle', $params), [])->assertNotFound();
        $this->get(route('admin.settlements.proof', $settlement))->assertForbidden();
        $admin = User::factory()->admin()->create(['name' => 'Settlement Reviewer']);
        $this->actingAs($admin)->get(route('admin.settlements.proof', $settlement))->assertOk();
        $this->patch(route('admin.settlements.decide', $settlement), ['decision' => 'verified'])->assertRedirect();
        $settlement->refresh();
        $this->assertSame('verified', $settlement->status);
        $this->assertSame($admin->id, $settlement->verified_by);
        $this->assertSame('Settlement Reviewer', $settlement->verified_by_name);
        $this->actingAs($user)->get(route('user.history.detail', $params))->assertOk()->assertSee('Lunas')->assertViewHas('summary', fn ($summary) => $summary['paid'] === 180000.0 && $summary['remaining'] === 0.0)->assertDontSee('Settlement Reviewer');
        $this->assertSame('confirmed', $type === 'studio' ? $record->fresh()->status : $record->fresh()->registration_status);
    }

    public function test_unverified_dp_cannot_settle_and_verified_dp_cannot_complete_without_settlement(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $record = $this->booking('studio', $user);
        $record->update(['payment_status' => 'unpaid']);
        $this->actingAs($user)->postJson(route('user.history.settle', ['type' => 'studio', 'id' => $record->id]), ['payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf')])->assertUnprocessable();
        $this->assertDatabaseCount('reservation_settlements', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $record->update(['payment_status' => 'paid']);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.bookings.update', $record), ['status' => 'completed', 'payment_status' => 'paid'])->assertSessionHasErrors('payment_status');
        $this->assertSame('confirmed', $record->fresh()->status);
    }
    #[DataProvider('types')]
    public function test_admin_details_are_read_only_and_edit_requires_separate_page(string $type): void
    {
        $record = $this->booking($type, User::factory()->create());
        $admin = User::factory()->admin()->create();
        $resource = $type === 'studio' ? 'admin.bookings' : 'admin.class-registrations';
        $this->actingAs($admin)->get(route($resource.'.show', $record))
            ->assertOk()->assertDontSee(route($resource.'.edit', $record))
            ->assertDontSee('<select', false)->assertDontSee('<textarea', false)
            ->assertDontSee('name="_method"', false);
        $this->get(route($resource.'.edit', $record))->assertOk()->assertSee('<select', false)->assertSee('name="_method"', false);
        $this->actingAs(User::factory()->create())->get(route($resource.'.show', $record))->assertForbidden();
    }

}
