<?php

namespace Database\Seeders;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $schedules = ClassSchedule::all();

        if ($customers->count() < 4 || $schedules->count() < 2) {
            return;
        }

        ClassRegistration::factory()->create([
            'user_id' => $customers[2]->id,
            'class_schedule_id' => $schedules[0]->id,
            'registration_date' => now()->toDateString(),
            'price' => 100000,
            'payment_status' => 'paid',
            'registration_status' => 'confirmed',
        ]);

        ClassRegistration::factory()->create([
            'user_id' => $customers[3]->id,
            'class_schedule_id' => $schedules[1]->id,
            'registration_date' => now()->toDateString(),
            'price' => 120000,
            'payment_status' => 'unpaid',
            'registration_status' => 'pending',
        ]);
    }
}
