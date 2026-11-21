<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCustomQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_custom_hours_and_existing_members_keep_their_quota(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
        $data = ['name' => 'Paket Bulanan 20 Jam', 'price' => 500000, 'duration_days' => 30, 'quota_hours' => 20, 'status' => 'active'];
        $this->post(route('admin.membership-plans.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $plan = MembershipPlan::sole();
        $member = app(MembershipEnrollment::class)->create([
            'user_id' => User::factory()->create()->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today()->toDateString(),
        ]);
        $this->assertEquals(1200, $member->quota_minutes);
        $this->assertSame(today()->addDays(29)->toDateString(), $member->end_date->toDateString());
        $this->put(route('admin.membership-plans.update', $plan), array_replace($data, ['quota_hours' => 24]))->assertSessionHasNoErrors();
        $this->assertSame(24, $plan->fresh()->quota_hours);
        $this->assertEquals(1200, $member->fresh()->quota_minutes);
        $this->get(route('admin.membership-plans.edit', $plan))->assertOk()->assertSee('Total kuota sewa (jam)');
        $this->get(route('admin.memberships.index', ['tab' => 'plans']))->assertOk()->assertSee('24 jam per 30 hari');
    }

    public function test_quota_rejects_invalid_hours_and_can_be_cleared(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $plan = MembershipPlan::factory()->create(['quota_hours' => 20]);
        $data = ['name' => $plan->name, 'price' => $plan->price, 'duration_days' => 30, 'status' => 'active'];
        foreach ([-1, 0, 1.5, 87841] as $quota) {
            $this->putJson(route('admin.membership-plans.update', $plan), $data + ['quota_hours' => $quota])
                ->assertUnprocessable()->assertJsonValidationErrors('quota_hours');
        }
        $this->assertSame(20, $plan->fresh()->quota_hours);
        $this->put(route('admin.membership-plans.update', $plan), $data + ['quota_hours' => ''])->assertSessionHasNoErrors();
        $this->assertNull($plan->fresh()->quota_hours);
    }
}
