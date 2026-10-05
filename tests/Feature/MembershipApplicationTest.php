<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_customer_applies_as_self_and_admin_can_approve_after_checking_payment(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $plan = MembershipPlan::factory()->create(['status' => 'active', 'duration_days' => 30, 'price' => 150000]);
        $this->actingAs($customer)->get(route('memberships.index'))->assertOk()->assertSee('Ajukan Membership');
        $data = ['membership_plan_id' => $plan->id, 'start_date' => today()->toDateString(), 'user_id' => $other->id, 'price' => 1, 'payment_status' => 'paid', 'status' => 'active'];
        $this->post(route('memberships.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('memberships.index'));
        $member = Membership::firstOrFail();
        $this->assertSame($customer->id, $member->user_id);
        $this->assertSame('150000.00', $member->price);
        $this->assertSame('pending', $member->status);
        $this->assertSame('unpaid', $member->payment_status);
        $this->post(route('memberships.store'), $data)->assertSessionHasErrors('start_date');
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.memberships.index'))->assertOk()->assertSee($customer->email)->assertSee('Setujui')->assertSee('Tolak')->assertSee('Tambah Membership');
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'approve'])->assertSessionHasErrors('payment_confirmed');
        $this->assertSame('pending', $member->fresh()->status);
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'approve', 'payment_confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('active', $member->fresh()->status);
        $this->assertSame('paid', $member->fresh()->payment_status);
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'reject'])->assertSessionHasErrors('status');
        $this->actingAs($customer)->get(route('memberships.index'))->assertOk()->assertSee('Aktif')->assertSee('Lunas');
    }

    public function test_rejection_preserves_payment_and_customer_history_is_private(): void
    {
        $member = Membership::factory()->create(['status' => 'pending', 'payment_status' => 'paid']);
        $other = Membership::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'reject'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $member->fresh()->status);
        $this->assertSame('paid', $member->fresh()->payment_status);
        $this->actingAs($member->user)->get(route('memberships.index'))->assertOk()
            ->assertSee('Ditolak / Dibatalkan')
            ->assertViewHas('memberships', fn ($memberships) => $memberships->count() === 1 && $memberships->first()->id === $member->id);
        $this->patch(route('admin.memberships.decide', $other), ['decision' => 'approve', 'payment_confirmed' => 1])->assertForbidden();
    }

    public function test_customer_routes_require_login_and_active_plan(): void
    {
        $this->get(route('memberships.index'))->assertRedirect(route('login'));
        $this->post(route('memberships.store'))->assertRedirect(route('login'));
        $member = Membership::factory()->create();
        $this->patch(route('admin.memberships.decide', $member), ['decision' => 'reject'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get(route('memberships.index'))->assertForbidden();
        $plan = MembershipPlan::factory()->create(['status' => 'inactive']);
        $this->actingAs(User::factory()->create());
        $this->post(route('memberships.store'), ['membership_plan_id' => $plan->id, 'start_date' => today()->toDateString()])->assertSessionHasErrors('membership_plan_id');
        $this->get(route('memberships.index'))->assertOk()->assertDontSee($plan->name);
    }

    public function test_approval_cannot_activate_future_or_expired_applications(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $future = Membership::factory()->create(['start_date' => today()->addDay(), 'end_date' => today()->addDays(30)]);
        $expired = Membership::factory()->create(['start_date' => today()->subDays(30), 'end_date' => today()->subDay()]);
        foreach ([$future, $expired] as $member) {
            $this->patch(route('admin.memberships.decide', $member), ['decision' => 'approve', 'payment_confirmed' => 1])->assertSessionHasErrors('status');
            $this->assertSame('pending', $member->fresh()->status);
            $this->assertSame('unpaid', $member->fresh()->payment_status);
        }
    }
}
