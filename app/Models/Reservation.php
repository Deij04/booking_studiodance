<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan', 'completed' => 'Selesai'];

    public const PAYMENT_STATUSES = ['unpaid' => 'Belum Dibayar', 'paid' => 'Lunas'];

    protected $fillable = [
        'booking_code',
        'user_id',
        'studio_schedule_id',
        'purpose',
        'total_price',
        'payment_status',
        'payment_proof',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studioSchedule()
    {
        return $this->belongsTo(StudioSchedule::class);
    }
}
