<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'dance_class_id',
        'studio_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'status',
    ];

    public function danceClass()
    {
        return $this->belongsTo(DanceClass::class);
    }

    public function studio()
    {
        return $this->belongsTo(Studio::class);
    }

    public function classRegistrations()
    {
        return $this->hasMany(ClassRegistration::class);
    }
}
