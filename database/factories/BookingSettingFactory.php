<?php
namespace Database\Factories;
use App\Models\BookingSetting;
use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;
class BookingSettingFactory extends Factory
{
    protected $model = BookingSetting::class;
    public function definition(): array
    {
        return ['studio_id' => Studio::factory(), 'kind' => 'block', 'date' => today()->addDay()->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '12:00:00', 'status' => 'blocked', 'block_reason' => 'Pemeliharaan'];
    }
}
