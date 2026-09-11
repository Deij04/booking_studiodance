<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        MembershipPlan::factory()->createMany([
            [
                'name' => 'Basic',
                'price' => 150000,
                'duration_days' => 30,
                'description' => 'Akses membership dasar selama 30 hari.',
                'status' => 'active',
            ],
            [
                'name' => 'Premium',
                'price' => 300000,
                'duration_days' => 90,
                'description' => 'Akses membership premium selama 90 hari.',
                'status' => 'active',
            ],
            [
                'name' => 'VIP',
                'price' => 500000,
                'duration_days' => 180,
                'description' => 'Akses membership VIP selama 180 hari.',
                'status' => 'active',
            ],
        ]);
    }
}
