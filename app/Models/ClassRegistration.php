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
        'registration_status',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function scopeInReservationTab($query, string $tab)
    {
        return match ($tab) {
            'requests', 'classes' => $query->where(fn ($q) => $q->where('registration_status', 'pending')->orWhere(fn ($q) => $q->where('registration_status', 'confirmed')->whereDoesntHave('payment', fn ($p) => $p->where('verification_status', 'verified')))),
            'active' => $query->where('registration_status', 'confirmed'),
            'history' => $query->whereIn('registration_status', ['completed', 'cancelled']),
        };
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->registration_status === 'cancelled' && $this->payment?->verification_status === 'rejected'
            ? 'Ditolak' : (self::STATUSES[$this->registration_status] ?? $this->registration_status);
    }

    public function getPaymentAmountAttribute(): float
    {
        return (float) ($this->payment?->amount ?? 0);
    }

    public function getRemainingAmountAttribute(): float
    {
        return round((float) $this->price - $this->payment_amount, 2);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'class_registration_id')->where('payment_stage', 'initial');
    }

    public function settlement()
    {
        return $this->hasOne(Payment::class, 'class_registration_id')->where('payment_stage', 'settlement')->latestOfMany();
    }

    public function settlements()
    {
        return $this->payments()->where('payment_stage', 'settlement');
    }

    public function payments() { return $this->hasMany(Payment::class, 'class_registration_id'); }
    public function getPaymentStatusAttribute(): string { return $this->payment?->verification_status === 'verified' ? 'paid' : 'unpaid'; }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }
}
