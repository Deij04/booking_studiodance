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
        $admin = User::where('role', 'admin')->firstOrFail();

        if ($customers->count() < 4 || $schedules->count() < 2) {
            return;
        }

        $confirmed = ClassRegistration::factory()->create([
            'user_id' => $customers[2]->id,
            'class_schedule_id' => $schedules[0]->id,
            'registration_date' => now()->toDateString(),
            'price' => $schedules[0]->danceClass->price,
            'payment_status' => 'paid',
            'registration_status' => 'confirmed',
        ]);

        $pending = ClassRegistration::factory()->create([
            'user_id' => $customers[3]->id,
            'class_schedule_id' => $schedules[1]->id,
            'registration_date' => now()->toDateString(),
            'price' => $schedules[1]->danceClass->price,
            'payment_status' => 'unpaid',
            'registration_status' => 'pending',
        ]);
        $confirmed->payment()->updateOrCreate([], [
            'payment_method' => 'cash', 'payment_type' => 'full', 'verification_status' => 'verified',
            'verified_by' => $admin->id, 'verified_by_name' => $admin->name, 'verified_at' => now(),
        ]);
        $pending->payment()->updateOrCreate([], ['payment_method' => 'bank_transfer', 'payment_type' => 'full', 'verification_status' => 'pending']);
    }
}
