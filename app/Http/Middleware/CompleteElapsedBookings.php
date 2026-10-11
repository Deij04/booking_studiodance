<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Services\BookingCompletion;
use Closure;
use Illuminate\Http\Request;

class CompleteElapsedBookings
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->user() && $request->is('admin/*', 'history', 'history/*')) {
            app(BookingCompletion::class)->run();
            foreach ($request->route()->parameters() as $record) {
                if ($record instanceof Booking || $record instanceof \App\Models\ClassRegistration) $record->refresh();
            }
        }
        return $next($request);
    }
}
