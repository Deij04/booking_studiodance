<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MembershipPaymentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_page_does_not_submit_application_and_proof_is_required(): void
    {
        Storage::fake('local');
        $this->withoutVite();
        $plan = MembershipPlan::factory()->create(['status' => 'active']);
        $this->get(route('memberships.payment', $plan))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('memberships.payment', $plan))->assertOk()->assertSee('Bukti pembayaran (wajib)')->assertSee('multipart/form-data');
        $this->assertDatabaseCount('memberships', 0);
        $this->postJson(route('memberships.store'), ['membership_plan_id' => $plan->id])
            ->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        foreach ([UploadedFile::fake()->create('bad.txt', 10, 'text/plain'), UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf')] as $proof) {
            $this->postJson(route('memberships.store'), ['membership_plan_id' => $plan->id, 'payment_proof' => $proof])
                ->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        }
        $this->assertDatabaseCount('memberships', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $plan->update(['status' => 'inactive']);
        $this->get(route('memberships.payment', $plan))->assertNotFound();
    }

    public function test_proof_is_private_and_available_for_admin_review_before_activation(): void
    {
        Storage::fake('local');
        $this->withoutVite();
        $customer = User::factory()->create();
        $plan = MembershipPlan::factory()->create(['status' => 'active']);
        $this->actingAs($customer)->post(route('memberships.store'), [
            'membership_plan_id' => $plan->id,
            'payment_proof' => UploadedFile::fake()->create('paid.pdf', 100, 'application/pdf'),
            'status' => 'active', 'payment_status' => 'paid',
        ])->assertSessionHasNoErrors()->assertRedirect(route('memberships.index'));
        $member = Membership::sole();
        $this->assertSame('pending', $member->status);
        $this->assertSame('unpaid', $member->payment_status);
        Storage::disk('local')->assertExists($member->payment_proof);
        $this->get(route('admin.memberships.payment-proof', $member))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.memberships.index'))->assertOk()->assertSee('Periksa bukti pembayaran')->assertSee(route('admin.memberships.payment-proof', $member));
        $this->get(route('admin.memberships.payment-proof', $member))->assertOk();
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'approve', 'payment_confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('active', $member->fresh()->status);
        $this->assertSame('paid', $member->fresh()->payment_status);
    }
}
