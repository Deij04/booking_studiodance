<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan', 'completed' => 'Selesai'];

    public const PAYMENT_TYPES = ['dp' => 'DP (50%)', 'full' => 'Lunas'];

    public const ONLINE_PAYMENT_METHODS = ['qris' => 'QRIS', 'bank_transfer' => 'Transfer Bank'];

    public const PAYMENT_METHODS = self::ONLINE_PAYMENT_METHODS + ['cash' => 'Tunai'];

    public const PAYMENT_STATUSES = ['unpaid' => 'Belum Terverifikasi', 'paid' => 'Terverifikasi'];

    protected $fillable = [
        'booking_code',
        'booking_name',
        'user_id',
        'studio_schedule_id',
        'purpose',
        'total_price',
        'payment_status',
        'verified_by',
        'verified_by_name',
        'verified_at',
        'payment_method',
        'payment_type',
        'payment_proof',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function getPaymentAmountAttribute(): float
    {
        return round((float) $this->total_price * ($this->payment_type === 'dp' ? 0.5 : 1), 2);
    }

    public function getRemainingAmountAttribute(): float
    {
        return round((float) $this->total_price - $this->payment_amount, 2);
    }

    public function settlement()
    {
        return $this->hasOne(ReservationSettlement::class, 'reservation_id');
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
