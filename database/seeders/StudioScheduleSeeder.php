<?php

namespace Database\Seeders;

use App\Models\Studio;
use App\Models\StudioSchedule;
use Illuminate\Database\Seeder;

class StudioScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $studios = Studio::all();

        if ($studios->isEmpty()) {
            return;
        }

        StudioSchedule::factory()->create([
            'studio_id' => $studios[0]->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'available',
        ]);

        StudioSchedule::factory()->create([
            'studio_id' => $studios[0]->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'available',
        ]);

        StudioSchedule::factory()->create([
            'studio_id' => $studios[1]->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'booked',
        ]);
    }
}
