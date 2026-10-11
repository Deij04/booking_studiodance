<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BookingHours
{
    public function current(): array
    {
        $settings = DB::table('booking_settings')->where('id', 1)->first();

        return [
            'opens_at' => $settings ? substr($settings->opens_at, 0, 5) : config('booking.opens_at'),
            'closes_at' => $settings ? substr($settings->closes_at, 0, 5) : config('booking.closes_at'),
        ];
    }
}
