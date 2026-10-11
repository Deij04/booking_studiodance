<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationSettlement extends Model
{
    public const STATUSES = ['pending' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
    protected $fillable = ['amount', 'payment_method', 'payment_proof', 'status', 'verified_by', 'verified_by_name', 'verified_at'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'verified_at' => 'datetime']; }
    public function reservation() { return $this->belongsTo(Reservation::class); }
    public function classRegistration() { return $this->belongsTo(ClassRegistration::class); }
}
