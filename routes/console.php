<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:complete', function () {
    $this->info(app(\App\Services\BookingCompletion::class)->run().' booking selesai.');
})->purpose('Selesaikan booking lunas setelah waktu penyewaan berakhir');

\Illuminate\Support\Facades\Schedule::command('bookings:complete')->everyMinute()->withoutOverlapping();
