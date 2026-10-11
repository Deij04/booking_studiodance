<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_staff_can_manage_plans_and_memberships_preserving_purchase_values(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.memberships.index'))->assertOk()->assertSee('Belum ada membership.')->assertSee('Tambah Membership');
        $this->get(route('admin.memberships.index', ['tab' => 'plans']))->assertOk()->assertSee('Tambah Paket')->assertDontSee('Tambah Membership');
        $this->get(route('admin.membership-plans.create'))->assertOk();
        $planData = ['name' => 'Paket Bulanan', 'price' => '150000.50', 'duration_days' => 30, 'status' => 'active', 'description' => 'Latihan rutin'];
        $this->post(route('admin.membership-plans.store'), $planData)->assertSessionHasNoErrors();
        $plan = MembershipPlan::firstOrFail();
        $this->get(route('admin.membership-plans.edit', $plan))->assertOk()->assertSee('Paket Bulanan');
        $this->get(route('admin.memberships.create'))->assertOk()->assertSee('Paket Bulanan');
        $user = User::factory()->create();
        $this->post(route('admin.memberships.store'), ['user_id' => $user->id, 'membership_plan_id' => $plan->id, 'start_date' => today()->toDateString(), 'price' => 1, 'end_date' => '2099-01-01', 'status' => 'active'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.memberships.index'));
        $member = Membership::firstOrFail();
        $this->assertSame('150000.50', $member->price);
        $this->assertSame('pending', $member->status);
        $this->assertSame(today()->addDays(29)->toDateString(), $member->end_date->toDateString());
        $this->get(route('admin.memberships.edit', $member))->assertOk()->assertSee($user->email);
        $this->put(route('admin.memberships.update', $member), ['status' => 'active', 'payment_status' => 'unpaid'])->assertSessionHasErrors('payment_status');
        $this->put(route('admin.memberships.update', $member), ['status' => 'active', 'payment_status' => 'paid'])->assertSessionHasNoErrors();
        $this->put(route('admin.membership-plans.update', $plan), array_replace($planData, ['price' => 200000, 'duration_days' => 60, 'status' => 'inactive']))->assertSessionHasNoErrors();
        $this->assertSame('150000.50', $member->fresh()->price);
        $this->assertSame(today()->addDays(29)->toDateString(), $member->fresh()->end_date->toDateString());
        $this->get(route('admin.memberships.index', ['search' => $user->email, 'status' => 'active']))->assertOk()->assertSee($user->name);
    }

    public function test_duplicate_periods_are_rejected_and_renewals_can_follow_previous_membership(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $plan = MembershipPlan::factory()->create(['duration_days' => 1, 'status' => 'active']);
        $data = ['user_id' => User::factory()->create()->id, 'membership_plan_id' => $plan->id, 'start_date' => today()->toDateString()];
        $this->post(route('admin.memberships.store'), $data)->assertSessionHasNoErrors();
        $first = Membership::firstOrFail();
        $this->assertSame(today()->toDateString(), $first->end_date->toDateString());
        $this->post(route('admin.memberships.store'), $data)->assertSessionHasErrors('start_date');
        $this->post(route('admin.memberships.store'), array_replace($data, ['start_date' => today()->addDay()->toDateString()]))->assertSessionHasNoErrors();
        $future = Membership::latest('id')->firstOrFail();
        $this->put(route('admin.memberships.update', $future), ['status' => 'active', 'payment_status' => 'paid'])->assertSessionHasErrors('status');
        $this->put(route('admin.memberships.update', $first), ['status' => 'cancelled', 'payment_status' => 'unpaid'])->assertSessionHasNoErrors();
        $this->post(route('admin.memberships.store'), $data)->assertSessionHasNoErrors();
        $this->put(route('admin.memberships.update', $first), ['status' => 'pending', 'payment_status' => 'unpaid'])->assertSessionHasErrors('start_date');
    }

    public function test_expired_memberships_are_displayed_and_filtered_consistently(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $old = Membership::factory()->create(['status' => 'active', 'payment_status' => 'paid', 'start_date' => today()->subDays(30), 'end_date' => today()->subDay()]);
        $this->assertSame('expired', $old->effective_status);
        $this->get(route('admin.memberships.index', ['status' => 'expired']))->assertOk()->assertSee($old->user->email);
        $this->get(route('admin.memberships.index', ['status' => 'active']))->assertOk()->assertDontSee($old->user->email);
        $this->put(route('admin.memberships.update', $old), ['status' => 'active', 'payment_status' => 'paid'])->assertSessionHasErrors('status');
        $this->put(route('admin.memberships.update', $old), ['status' => 'expired', 'payment_status' => 'paid'])->assertSessionHasNoErrors();
    }

    public function test_invalid_plans_and_ineligible_customers_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.membership-plans.store'), ['name' => '', 'price' => -1, 'duration_days' => 0, 'status' => 'other'])->assertSessionHasErrors(['name', 'price', 'duration_days', 'status']);
        $plan = MembershipPlan::factory()->create(['status' => 'inactive']);
        $user = User::factory()->create(['status' => 'inactive']);
        $this->post(route('admin.memberships.store'), ['user_id' => $user->id, 'membership_plan_id' => $plan->id, 'start_date' => today()->subDay()->toDateString()])->assertSessionHasErrors(['user_id', 'membership_plan_id', 'start_date']);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_guest_and_customer_cannot_access_management_routes(): void
    {
        $member = Membership::factory()->create();
        $requests = [
            ['get', route('admin.memberships.index')], ['get', route('admin.memberships.create')],
            ['post', route('admin.memberships.store')], ['get', route('admin.memberships.edit', $member)],
            ['put', route('admin.memberships.update', $member)], ['get', route('admin.membership-plans.create')],
            ['post', route('admin.membership-plans.store')], ['get', route('admin.membership-plans.edit', $member->membershipPlan)],
            ['put', route('admin.membership-plans.update', $member->membershipPlan)],
        ];
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertForbidden();
        }
    }
}
