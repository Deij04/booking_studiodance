<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'Menunggu', 'active' => 'Aktif', 'expired' => 'Berakhir', 'cancelled' => 'Dibatalkan'];

    public function getEffectiveStatusAttribute(): string
    {
        return $this->status === 'active' && $this->end_date?->lt(today()) ? 'expired' : $this->status;
    }

    protected $fillable = [
        'payment_proof',
        'user_id', 'studio_id', 'quota_minutes', 'free_classes', 'verified_by', 'verified_by_name', 'verified_at',
        'membership_plan_id',
        'start_date',
        'end_date',
        'price',
        'payment_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2', 'free_classes' => 'boolean', 'verified_at' => 'datetime',
        ];
    }

    public function studio() { return $this->belongsTo(Studio::class); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function getRemainingMinutesAttribute(): int { return max(0, $this->quota_minutes - (int) $this->bookings()->where('status', '!=', 'cancelled')->sum('membership_minutes')); }
    public function scopeValidOn($query, string $date) { return $query->where('status', 'active')->where('payment_status', 'paid')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date); }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function membershipPlan()
    {
        return $this->belongsTo(MembershipPlan::class)->withTrashed();
    }
}
