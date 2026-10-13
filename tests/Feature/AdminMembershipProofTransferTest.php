<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMembershipProofTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creation_requires_private_payment_proof(): void
    {
        Storage::fake('local');
        $this->withoutVite();
        $customer = User::factory()->create();
        $plan = MembershipPlan::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $data = ['user_id' => $customer->id, 'membership_plan_id' => $plan->id, 'start_date' => today()->toDateString()];
        $this->post(route('admin.memberships.store'), $data)->assertSessionHasErrors('payment_proof');
        $this->assertDatabaseCount('memberships', 0);
        $this->post(route('admin.memberships.store'), $data + ['payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors()->assertRedirect();
        $member = Membership::firstOrFail();
        Storage::disk('local')->assertExists($member->payment_proof);
        $this->get(route('admin.memberships.payment-proof', $member))->assertOk();
        $this->get(route('admin.memberships.edit', $member))->assertOk()->assertSee('Lihat bukti pembayaran');
        $this->actingAs($customer)->get(route('admin.memberships.payment-proof', $member))->assertForbidden();
    }

    public function test_admin_can_transfer_one_member_without_changing_payment_or_period(): void
    {
        $member = Membership::factory()->create(['status' => 'active', 'payment_status' => 'paid', 'payment_proof' => 'receipt.pdf']);
        $member->refresh();
        $other = Membership::factory()->create(['membership_plan_id' => $member->membership_plan_id]);
        $target = MembershipPlan::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.memberships.transfer', $member), ['target_plan_id' => $member->membership_plan_id])->assertSessionHasErrors('target_plan_id');
        $this->post(route('admin.memberships.transfer', $member), ['target_plan_id' => $target->id])->assertSessionHasNoErrors()->assertRedirect(route('admin.memberships.edit', $member));
        $this->assertSame($target->id, $member->fresh()->membership_plan_id);
        foreach (['price', 'start_date', 'end_date', 'status', 'payment_status', 'payment_proof', 'quota_minutes', 'free_classes', 'studio_id'] as $field) {
            $this->assertEquals($member->$field, $member->fresh()->$field);
        }
        $this->assertSame($other->membership_plan_id, $other->fresh()->membership_plan_id);
        $target->update(['status' => 'inactive']);
        $this->post(route('admin.memberships.transfer', $other), ['target_plan_id' => $target->id])->assertSessionHasErrors('target_plan_id');
        $this->actingAs($member->user)->post(route('admin.memberships.transfer', $member), ['target_plan_id' => $other->membership_plan_id])->assertForbidden();
    }
}
