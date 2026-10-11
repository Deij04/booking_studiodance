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
        'user_id',
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
            'price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function membershipPlan()
    {
        return $this->belongsTo(MembershipPlan::class);
    }
}
