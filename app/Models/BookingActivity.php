<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingActivity extends Model
{
    protected $fillable = ['booking_id', 'actor_id', 'actor_name', 'actor_email', 'actor_role', 'action', 'changes'];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }
}
