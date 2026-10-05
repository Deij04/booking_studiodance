<?php

return [
    'opens_at' => env('BOOKING_OPENS_AT', '08:00'),
    'closes_at' => env('BOOKING_CLOSES_AT', '22:00'),
    'minimum_minutes' => (int) env('BOOKING_MINIMUM_MINUTES', 60),
    'step_minutes' => (int) env('BOOKING_STEP_MINUTES', 30),
];
