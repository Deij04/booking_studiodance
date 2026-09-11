<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $plans = MembershipPlan::all();

        if ($customers->count() < 2 || $plans->count() < 2) {
            return;
        }

        Membership::factory()->create([
            'user_id' => $customers[0]->id,
            'membership_plan_id' => $plans[0]->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'price' => 150000,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        Membership::factory()->create([
            'user_id' => $customers[1]->id,
            'membership_plan_id' => $plans[1]->id,
            'start_date' => null,
            'end_date' => null,
            'price' => 300000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
    }
}
