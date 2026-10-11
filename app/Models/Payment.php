<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{
    public const STATUSES = ['pending' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Pembayaran Ditolak'];
    public const STAGES = ['initial' => 'Pembayaran Awal', 'settlement' => 'Pelunasan'];

    protected $fillable = ['booking_id', 'class_registration_id', 'payment_stage', 'payment_type', 'amount',
        'payment_method', 'payment_proof', 'notes', 'verification_status', 'rejection_reason',
        'verified_by', 'verified_by_name', 'verified_at'];
    protected $attributes = ['payment_stage' => 'initial', 'payment_type' => 'full', 'verification_status' => 'pending'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'verified_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if (isset($payment->booking_id) === isset($payment->class_registration_id)) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran harus terkait ke satu booking studio atau reservasi class.']);
            }
            if ($payment->payment_stage === 'initial' && static::where('payment_stage', 'initial')
                ->where($payment->booking_id ? 'booking_id' : 'class_registration_id', $payment->booking_id ?? $payment->class_registration_id)
                ->when($payment->exists, fn ($q) => $q->whereKeyNot($payment->id))->exists()) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran awal reservasi sudah tercatat.']);
            }
            if ($payment->amount === null) {
                $parent = $payment->booking ?? $payment->classRegistration;
                $payment->amount = round((float) ($parent->total_price ?? $parent->price) * ($payment->payment_type === 'dp' ? 0.5 : 1), 2);
            }
            if ((float) $payment->amount <= 0) throw ValidationException::withMessages(['payment' => 'Nominal pembayaran harus lebih dari nol.']);
        });
    }

    // Presentation alias retained for settlement views; only verification_status is stored.
    public function getStatusAttribute(): string { return $this->verification_status; }
    public function booking() { return $this->belongsTo(Booking::class); }
    public function classRegistration() { return $this->belongsTo(ClassRegistration::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}
