<?php

namespace App\Services;

use App\Models\ClassRegistration;
use App\Models\Reservation;

class ReservationPaymentSummary
{
    public function for(Reservation|ClassRegistration $record): array
    {
        $class = $record instanceof ClassRegistration;
        $initial = $class ? $record->payment : $record;
        $total = (float) ($class ? $record->price : $record->total_price);
        $dp = $initial?->payment_type === 'dp';
        $firstAmount = round($total * ($dp ? 0.5 : 1), 2);
        $verified = $record->payment_status === 'paid';
        $settlement = $record->settlement;
        $paid = $verified ? $firstAmount : 0;
        if ($verified && $dp && $settlement?->status === 'verified') $paid += (float) $settlement->amount;
        $remaining = max(0.0, round($total - $paid, 2));
        $rentalStatus = $class ? $record->registration_status : $record->status;
        return compact('total', 'dp', 'firstAmount', 'verified', 'settlement', 'paid', 'remaining', 'initial', 'rentalStatus') + [
            'label' => $remaining <= 0 ? 'Lunas' : ($verified && $dp ? 'DP Terverifikasi - Belum Lunas' : 'Menunggu Pembayaran / Verifikasi'),
            'canSettle' => $dp && $verified && $remaining > 0 && $rentalStatus === 'confirmed' && (!$settlement || $settlement->status === 'rejected'),
        ];
    }
}
