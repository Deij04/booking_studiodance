<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'studio_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'block_reason',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }
}
