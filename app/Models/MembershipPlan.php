<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'name', 'studio_id', 'quota_hours', 'free_classes',
        'price',
        'duration_days',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2', 'free_classes' => 'boolean', 'quota_hours' => 'integer',
        ];
    }

    public function getDisplayNameAttribute(): string { return $this->quota_hours ? (($this->studio?->name ?? 'Semua Studio').' - Membership '.$this->quota_hours) : $this->name; }

    public function studio() { return $this->belongsTo(Studio::class); }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }
}
