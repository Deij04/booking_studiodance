<?php

namespace Database\Seeders;

use App\Models\DanceClass;
use Illuminate\Database\Seeder;

class DanceClassSeeder extends Seeder
{
    public function run(): void
    {
        DanceClass::factory()->createMany([
            [
                'name' => 'Hip Hop Beginner',
                'description' => 'Kelas dasar hip hop untuk pemula.',
                'level' => 'beginner',
                'price' => 100000,
                'capacity' => 20,
                'image' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Afro Fusion',
                'description' => 'Kelas dengan gaya gerak energik dan ritmis.',
                'level' => 'intermediate',
                'price' => 120000,
                'capacity' => 15,
                'image' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Zumba',
                'description' => 'Kelas cardio dance yang dinamis.',
                'level' => 'all_level',
                'price' => 90000,
                'capacity' => 25,
                'image' => null,
                'status' => 'active',
            ],
        ]);
    }
}
