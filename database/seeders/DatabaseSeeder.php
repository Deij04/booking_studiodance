<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StudioSeeder::class,
            DanceClassSeeder::class,
            MembershipPlanSeeder::class,
            StudioScheduleSeeder::class,
            ClassScheduleSeeder::class,
            ReservationSeeder::class,
            ClassRegistrationSeeder::class,
            MembershipSeeder::class,
        ]);
    }
}
