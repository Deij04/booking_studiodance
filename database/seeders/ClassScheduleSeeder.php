<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Studio;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $danceClasses = DanceClass::all();
        $studios = Studio::all();

        if ($danceClasses->isEmpty() || $studios->isEmpty()) {
            return;
        }

        ClassSchedule::factory()->create([
            'dance_class_id' => $danceClasses[0]->id,
            'studio_id' => $studios[1]->id,
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
            'capacity' => 20,
            'status' => 'active',
        ]);

        ClassSchedule::factory()->create([
            'dance_class_id' => $danceClasses[1]->id,
            'studio_id' => $studios[2]->id,
            'date' => now()->addDays(4)->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '20:30:00',
            'capacity' => 15,
            'status' => 'active',
        ]);
    }
}
