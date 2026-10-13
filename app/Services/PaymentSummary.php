<?php
namespace App\Services;
use App\Models\ClassRegistration;
use App\Models\Booking;
class PaymentSummary
{
    public function for(Booking|ClassRegistration $record): array
    {
        $class = $record instanceof ClassRegistration;
        $initial = $record->payment;
        $total = (float) ($class ? $record->price : $record->total_price);
        $dp = $initial?->payment_type === 'dp';
        $firstAmount = (float) ($initial?->amount ?? 0);
        $verified = $initial?->verification_status === 'verified';
        $settlement = $record->settlement;
        $paid = (float) $record->payments()->where('verification_status', 'verified')->sum('amount');
        $remaining = max(0.0, round($total - $paid, 2));
        $rentalStatus = $class ? $record->registration_status : $record->status;
        return compact('total', 'dp', 'firstAmount', 'verified', 'settlement', 'paid', 'remaining', 'initial', 'rentalStatus') + [
            'label' => $record->membership_id && $total <= 0 ? 'Gratis - Benefit Membership' : ($class && !$verified && $initial?->verification_status === 'rejected' ? 'Belum Terverifikasi' : ($remaining <= 0 ? 'Lunas' : ($verified ? ($dp ? 'DP Terverifikasi - Belum Lunas' : 'Belum Lunas') : 'Menunggu Pembayaran / Verifikasi'))),
            'canSettle' => $verified && $remaining > 0 && $rentalStatus === 'confirmed' && !$record->settlements()->where('verification_status', 'pending')->exists(),
        ];
    }
}
