<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClassRegistration;
use App\Models\Membership;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function download(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('from') ? ['after_or_equal:from'] : []),
        ]);
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $range = fn ($query, $column) => $query
            ->when($from, fn ($query) => $query->whereDate($column, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($column, '<=', $to));
        $bookings = $range(Booking::with(['user', 'studio', 'payments'])->orderBy('id'), 'date');
        $classes = ClassRegistration::with(['user', 'classSchedule.danceClass', 'classSchedule.studio', 'payments'])
            ->whereHas('classSchedule', fn ($query) => $range($query, 'date'))->orderBy('id');
        $members = $range(Membership::with(['user', 'membershipPlan'])->orderBy('id'), 'created_at');
        return response()->streamDownload(function () use ($bookings, $classes, $members) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $write = function (array $row) use ($stream) {
                $row = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($stream, $row);
            };
            $write(['Jenis', 'ID / Kode', 'Customer', 'Email', 'Studio / Class / Paket', 'Tanggal penggunaan / pendaftaran (WITA)', 'Jam mulai (WITA)', 'Jam selesai (WITA)', 'Total biaya (IDR)', 'Total terverifikasi (IDR)', 'Sisa pembayaran (IDR)', 'Status reservasi / membership', 'Pembayaran', 'Diverifikasi oleh']);
            foreach ($bookings->lazy(200) as $record) {
                $paid = (float) $record->payments->where('verification_status', 'verified')->sum('amount');
                $write(['Booking Studio', $record->booking_code, $record->user->name, $record->user->email, $record->studio->name, $record->date, substr($record->start_time, 0, 5), substr($record->end_time, 0, 5), $record->total_price, $paid, max(0, (float) $record->total_price - $paid), Booking::STATUSES[$record->status], $record->payments->map(fn ($payment) => $payment->payment_stage.': '.$payment->verification_status)->implode('; '), $record->payments->pluck('verified_by_name')->filter()->unique()->implode('; ')]);
            }
            foreach ($classes->lazy(200) as $record) {
                $paid = (float) $record->payments->where('verification_status', 'verified')->sum('amount');
                $schedule = $record->classSchedule;
                $write(['Reservasi Class', $record->id, $record->user->name, $record->user->email, $schedule->danceClass->name.' / '.$schedule->studio->name, $schedule->date, substr($schedule->start_time, 0, 5), substr($schedule->end_time, 0, 5), $record->price, $paid, max(0, (float) $record->price - $paid), $record->display_status, $record->payments->map(fn ($payment) => $payment->payment_stage.': '.$payment->verification_status)->implode('; '), $record->payments->pluck('verified_by_name')->filter()->unique()->implode('; ')]);
            }
            foreach ($members->lazy(200) as $record) {
                $paid = $record->payment_status === 'paid' ? (float) $record->price : 0;
                $write(['Membership', $record->id, $record->user->name, $record->user->email, $record->membershipPlan->display_name, $record->created_at->format('Y-m-d'), '', '', $record->price, $paid, max(0, (float) $record->price - $paid), Membership::STATUSES[$record->effective_status], $record->payment_status, $record->verified_by_name ?? '']);
            }
            fclose($stream);
        }, 'laporan-reservasi-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
