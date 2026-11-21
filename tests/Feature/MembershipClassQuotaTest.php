<?php

namespace Tests\Feature;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipClassQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_admin_cannot_exceed_quota_and_cancellation_releases_it(): void
    {
        $user = User::factory()->create();
        $member = Membership::factory()->create(['user_id' => $user->id, 'class_quota' => 1, 'free_classes' => true,
            'status' => 'active', 'payment_status' => 'paid', 'start_date' => today(), 'end_date' => today()->addDays(29)]);
        $first = ClassSchedule::factory()->create(['date' => today()->addDays(2)->toDateString(), 'status' => 'active']);
        $second = ClassSchedule::factory()->create(['date' => today()->addDays(3)->toDateString(), 'status' => 'active']);
        $this->actingAs($user)->post(route('user.classes.store', $first->danceClass), [
            'class_schedule_id' => $first->id, 'payment_type' => 'full',
        ])->assertSessionHasNoErrors()->assertRedirect(route('user.history'));
        $this->assertSame(0, $member->remaining_classes, 'quota consumed');
        $this->postJson(route('user.classes.store', $second->danceClass), [
            'class_schedule_id' => $second->id, 'payment_type' => 'full',
        ])->assertUnprocessable()->assertJsonValidationErrors(['payment_method', 'payment_proof']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.class-registrations.store'), [
            'user_id' => $user->id, 'class_schedule_id' => $second->id, 'payment_type' => 'full', 'payment_method' => 'membership',
        ])->assertUnprocessable();
        $record = ClassRegistration::sole();
        $this->assertTrue(Membership::withClassQuota($record->id)->whereKey($member->id)->exists());
        $record->update(['registration_status' => 'cancelled']);
        $this->assertSame(1, $member->remaining_classes, 'quota restored after cancellation');
        $this->post(route('admin.class-registrations.store'), [
            'user_id' => $user->id, 'class_schedule_id' => $second->id, 'payment_type' => 'full', 'payment_method' => 'membership',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $member->remaining_classes, 'quota consumed');
        ClassRegistration::where('registration_status', 'confirmed')->update(['registration_status' => 'completed']);
        $this->assertSame(0, $member->remaining_classes, 'quota consumed');
    }

    public function test_admin_sets_duration_and_class_quota_and_enrollment_keeps_quota_snapshot(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
        $data = ['name' => 'Paket 8 kelas', 'price' => 300000, 'duration_days' => 45, 'class_quota' => 8, 'status' => 'active'];
        $this->post(route('admin.membership-plans.store'), $data)->assertSessionHasNoErrors();
        $plan = MembershipPlan::sole();
        $member = app(MembershipEnrollment::class)->create(['user_id' => User::factory()->create()->id,
            'membership_plan_id' => $plan->id, 'start_date' => today()->toDateString()]);
        $this->assertSame(8, $member->class_quota);
        $this->assertSame(today()->addDays(44)->toDateString(), $member->end_date->toDateString());
        $this->put(route('admin.membership-plans.update', $plan), array_replace($data, ['class_quota' => 4, 'duration_days' => 14]))->assertSessionHasNoErrors();
        $this->assertSame(8, $member->fresh()->class_quota);
        $this->assertSame(today()->addDays(44)->toDateString(), $member->fresh()->end_date->toDateString());
        $this->get(route('admin.membership-plans.edit', $plan))->assertOk()->assertSee('Maksimal kelas per periode')->assertSee('Durasi membership (hari)');
        foreach ([-1, 1.5, 100001] as $quota) {
            $this->putJson(route('admin.membership-plans.update', $plan), array_replace($data, ['class_quota' => $quota]))
                ->assertUnprocessable()->assertJsonValidationErrors('class_quota');
        }
        $member->update(['class_quota' => 0]);
        $this->assertFalse(Membership::withClassQuota()->whereKey($member->id)->exists());
        $member->update(['class_quota' => null]);
        $this->assertNull($member->remaining_classes);
        $this->assertTrue(Membership::withClassQuota()->whereKey($member->id)->exists());
    }
}
