<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClassRegistration;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipDeletionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_owner_must_transfer_members_before_deleting_plans(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $plan = MembershipPlan::factory()->create();
            $member = Membership::factory()->create(['membership_plan_id' => $plan->id, 'status' => 'active', 'start_date' => today(), 'end_date' => today()->addDays(29)]);
            $this->actingAs(User::factory()->create(['role' => $role]))->delete(route('admin.membership-plans.destroy', $plan))->assertSessionHasErrors('membership_plan');
            $this->assertNotSoftDeleted('membership_plans', ['id' => $plan->id]);
            $this->assertSame('active', $plan->fresh()->status);
            $target = MembershipPlan::factory()->create(['status' => 'active']);
            $this->post(route('admin.membership-plans.transfer', $plan), ['target_plan_id' => $target->id])->assertRedirect(route('admin.memberships.index', ['tab' => 'plans']))->assertSessionHas('success');
            $this->assertDatabaseHas('memberships', ['id' => $member->id, 'membership_plan_id' => $target->id, 'price' => $member->price, 'status' => 'active', 'payment_status' => $member->payment_status]);
            $this->delete(route('admin.membership-plans.destroy', $plan))->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSoftDeleted('membership_plans', ['id' => $plan->id]);
            $this->assertDatabaseHas('memberships', ['id' => $member->id, 'status' => 'active']);
            $this->assertSame($target->id, $member->fresh()->membershipPlan->id);
            $this->assertNull(MembershipPlan::find($plan->id));
        }
        $plan = MembershipPlan::factory()->create();
        $this->actingAs(User::factory()->create())->delete(route('admin.membership-plans.destroy', $plan))->assertForbidden();
        $this->post(route('admin.membership-plans.transfer', $plan), ['target_plan_id' => $target->id])->assertForbidden();
    }

    public function test_transfer_rejects_same_inactive_and_deleted_target_plans(): void
    {
        $plan = MembershipPlan::factory()->create();
        $inactive = MembershipPlan::factory()->create(['status' => 'inactive']);
        $deleted = MembershipPlan::factory()->create(['status' => 'active']);
        $deleted->delete();
        $member = Membership::factory()->create(['membership_plan_id' => $plan->id, 'status' => 'expired']);
        $this->actingAs(User::factory()->admin()->create());
        foreach ([$plan->id, $inactive->id, $deleted->id] as $target) {
            $this->post(route('admin.membership-plans.transfer', $plan), ['target_plan_id' => $target])->assertSessionHasErrors('target_plan_id');
        }
        $this->assertSame($plan->id, $member->fresh()->membership_plan_id);
        $this->delete(route('admin.membership-plans.destroy', $plan))->assertSessionHasErrors('membership_plan');
        $this->assertNotSoftDeleted('membership_plans', ['id' => $plan->id]);
        MembershipPlan::factory()->create(['status' => 'active']);
        $this->withoutVite()->get(route('admin.memberships.index', ['tab' => 'plans']))->assertOk()->assertSee('Alihkan Member');
    }

    public function test_only_owner_downloads_csv_with_paid_amounts_and_date_filters(): void
    {
        $booking = Booking::factory()->create(['date' => today()->toDateString(), 'payment_status' => 'paid', 'payment_type' => 'dp', 'total_price' => 300000]);
        ClassRegistration::factory()->create();
        Membership::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reports.download'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $response = $this->get(route('admin.reports.download'))->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString($booking->booking_code, $csv);
        $this->assertStringContainsString('150000', $csv);
        $this->assertStringContainsString('Reservasi Class', $csv);
        $this->assertStringContainsString('Membership', $csv);
        $filtered = $this->get(route('admin.reports.download', ['from' => today()->addDays(100)->toDateString(), 'to' => today()->addDays(101)->toDateString()]))->assertOk()->streamedContent();
        $this->assertStringNotContainsString($booking->booking_code, $filtered);
        $this->get(route('admin.reports.download', ['to' => today()->toDateString()]))->assertOk();
        $this->getJson(route('admin.reports.download', ['from' => '2026-10-20', 'to' => '2026-10-10']))->assertUnprocessable();
    }
}
