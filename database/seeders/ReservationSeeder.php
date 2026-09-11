<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $schedules = StudioSchedule::all();

        if ($customers->count() < 2 || $schedules->count() < 2) {
            return;
        }

        Reservation::factory()->create([
            'booking_code' => 'RES-' . now()->format('Ymd') . '-001',
            'user_id' => $customers[0]->id,
            'studio_schedule_id' => $schedules[0]->id,
            'purpose' => 'Latihan pribadi',
            'total_price' => 150000,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'notes' => 'Sudah dibayar dan dikonfirmasi admin.',
        ]);

        Reservation::factory()->create([
            'booking_code' => 'RES-' . now()->format('Ymd') . '-002',
            'user_id' => $customers[1]->id,
            'studio_schedule_id' => $schedules[1]->id,
            'purpose' => 'Latihan kelompok kecil',
            'total_price' => 150000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'notes' => 'Menunggu pembayaran.',
        ]);
    }
}
