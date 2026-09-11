<?php

namespace Database\Seeders;

use App\Models\Studio;
use Illuminate\Database\Seeder;

class StudioSeeder extends Seeder
{
    public function run(): void
    {
        Studio::factory()->createMany([
            [
                'name' => 'Studio A',
                'description' => 'Studio utama untuk latihan reguler.',
                'capacity' => 20,
                'price_per_hour' => 150000,
                'image' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Studio B',
                'description' => 'Studio menengah untuk kelas dan reservasi.',
                'capacity' => 15,
                'price_per_hour' => 120000,
                'image' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Studio C',
                'description' => 'Studio kecil untuk sesi privat.',
                'capacity' => 10,
                'price_per_hour' => 100000,
                'image' => null,
                'status' => 'active',
            ],
        ]);
    }
}
