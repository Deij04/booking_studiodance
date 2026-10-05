<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRegistration extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];

    protected $fillable = [
        'user_id',
        'class_schedule_id',
        'registration_date',
        'price',
        'payment_status',
        'registration_status',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }
}
