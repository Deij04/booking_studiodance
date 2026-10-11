<?php

return [
    'qris_image' => env('BOOKING_QRIS_IMAGE', 'images/logoorange.png'),
    'bank_name' => env('BOOKING_BANK_NAME', 'Bank Contoh'),
    'bank_account_number' => env('BOOKING_BANK_ACCOUNT_NUMBER', '0000000000'),
    'bank_account_name' => env('BOOKING_BANK_ACCOUNT_NAME', 'Orange Dance Academy (Dummy)'),
    'opens_at' => env('BOOKING_OPENS_AT', '08:00'),
    'closes_at' => env('BOOKING_CLOSES_AT', '22:00'),
    'minimum_minutes' => (int) env('BOOKING_MINIMUM_MINUTES', 60),
    'step_minutes' => (int) env('BOOKING_STEP_MINUTES', 30),
];
