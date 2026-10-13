<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan', 'completed' => 'Selesai'];

    public const PURPOSES = ['Group Training', 'Private Practice', 'Dance Rehearsal', 'Workshop', 'Other'];

    public const PAYMENT_TYPES = ['dp' => 'DP (50%)', 'full' => 'Lunas'];

    public const ONLINE_PAYMENT_METHODS = ['qris' => 'QRIS', 'bank_transfer' => 'Transfer Bank'];

    public const PAYMENT_METHODS = self::ONLINE_PAYMENT_METHODS + ['cash' => 'Tunai', 'membership' => 'Benefit Membership'];

    public const PAYMENT_STATUSES = ['unpaid' => 'Belum Terverifikasi', 'paid' => 'Terverifikasi'];

    protected $fillable = [
        'booking_code',
        'booking_name',
        'user_id', 'membership_id', 'membership_minutes',
        'studio_id',
        'date',
        'start_time',
        'end_time',
        'purpose',
        'total_price',
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

    public function scopeInBookingTab($query, string $tab)
    {
        return match ($tab) {
            'requests' => $query->where(fn ($q) => $q->where('status', 'pending')->orWhere(fn ($q) => $q->where('status', 'confirmed')->whereDoesntHave('payment', fn ($p) => $p->where('verification_status', 'verified')))),
            'active' => $query->where('status', 'confirmed')->whereHas('payment', fn ($p) => $p->where('verification_status', 'verified')),
            'history' => $query->whereIn('status', ['completed', 'cancelled']),
        };
    }

    public function getPaymentAmountAttribute(): float
    {
        return (float) ($this->payment?->amount ?? 0);
    }

    public function getRemainingAmountAttribute(): float
    {
        return round((float) $this->total_price - $this->payment_amount, 2);
    }

    public function settlement()
    {
        return $this->hasOne(Payment::class, 'booking_id')->where('payment_stage', 'settlement')->latestOfMany();
    }

    public function payment() { return $this->hasOne(Payment::class, 'booking_id')->where('payment_stage', 'initial'); }
    public function payments() { return $this->hasMany(Payment::class, 'booking_id'); }
    public function settlements() { return $this->payments()->where('payment_stage', 'settlement'); }
    public function getPaymentStatusAttribute(): string { return $this->payment?->verification_status === 'verified' ? 'paid' : 'unpaid'; }
    public function getVerifiedByAttribute() { return $this->payment?->verified_by; }
    public function getVerifiedByNameAttribute() { return $this->payment?->verified_by_name; }
    public function getVerifiedAtAttribute() { return $this->payment?->verified_at; }
    public function getPaymentMethodAttribute() { return $this->payment?->payment_method; }
    public function getPaymentTypeAttribute() { return $this->payment?->payment_type; }
    public function getPaymentProofAttribute() { return $this->payment?->payment_proof; }

    public function membership() { return $this->belongsTo(Membership::class); }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studio()
    {
        return $this->belongsTo(Studio::class);
    }
}
