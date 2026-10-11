<?php
namespace Database\Seeders;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Seeder;
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get(); $studio = Studio::firstOrFail(); $admin = User::where('role', 'admin')->firstOrFail();
        foreach ([['confirmed', 'paid', '08:00', '10:00'], ['pending', 'unpaid', '10:00', '12:00']] as $index => [$status, $payment, $start, $end]) {
            Booking::factory()->create(['booking_code' => 'RES-'.now()->format('Ymd').'-00'.($index + 1),
                'user_id' => $customers[$index]->id, 'booking_name' => $customers[$index]->name, 'studio_id' => $studio->id,
                'date' => today()->addDay()->toDateString(), 'start_time' => $start, 'end_time' => $end,
                'purpose' => $index ? 'Group Training' : 'Private Practice', 'total_price' => (float) $studio->price_per_hour * 2,
                'status' => $status, 'payment_status' => $payment, 'payment_type' => 'full', 'payment_method' => $index ? 'bank_transfer' : 'cash',
                'verified_by' => $index ? null : $admin->id, 'verified_by_name' => $index ? null : $admin->name, 'verified_at' => $index ? null : now()]);
        }
    }
}
