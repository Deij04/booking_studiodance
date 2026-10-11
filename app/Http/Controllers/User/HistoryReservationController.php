<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassRegistration;
use App\Models\Reservation;
use App\Services\ReservationPaymentSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HistoryReservationController extends Controller
{
    private function owned(Request $request, string $type, int $id): Reservation|ClassRegistration
    {
        abort_unless(in_array($type, ['studio', 'class']), 404);
        $query = $type === 'studio' ? Reservation::query() : ClassRegistration::query();
        return $query->where('user_id', $request->user()->id)->findOrFail($id);
    }

    public function show(Request $request, string $type, int $id)
    {
        $record = $this->owned($request, $type, $id);
        $record->load($type === 'studio' ? ['studioSchedule.studio', 'settlement'] : ['classSchedule.danceClass', 'classSchedule.studio', 'payment', 'settlement']);
        $schedule = $type === 'studio' ? $record->studioSchedule : $record->classSchedule;
        $title = $type === 'studio' ? $schedule?->studio?->name : $schedule?->danceClass?->name;
        $summary = app(ReservationPaymentSummary::class)->for($record);
        return view('user.account.reservation-detail', compact('record', 'type', 'schedule', 'title', 'summary'));
    }

    public function settle(Request $request, string $type, int $id)
    {
        $this->owned($request, $type, $id);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(Reservation::ONLINE_PAYMENT_METHODS))],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);
        $path = $request->file('payment_proof')->store('settlement-proofs', 'local');
        abort_if(!$path, 500, 'Bukti pembayaran gagal disimpan.');
        try {
            $oldPath = DB::transaction(function () use ($request, $type, $id, $data, $path) {
                $record = $this->owned($request, $type, $id);
                $record = $record->newQuery()->lockForUpdate()->findOrFail($record->id);
                $summary = app(ReservationPaymentSummary::class)->for($record);
                if (!$summary['canSettle']) throw ValidationException::withMessages(['payment_proof' => 'Pelunasan hanya dapat diajukan setelah DP diverifikasi dan reservasi dikonfirmasi. Periksa apakah pelunasan sudah diajukan.']);
                $previous = $record->settlement?->payment_proof;
                $record->settlement()->updateOrCreate([], [
                    'amount' => $summary['remaining'], 'payment_method' => $data['payment_method'], 'payment_proof' => $path,
                    'status' => 'pending', 'verified_by' => null, 'verified_by_name' => null, 'verified_at' => null,
                ]);
                return $previous;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($oldPath && str_starts_with($oldPath, 'settlement-proofs/')) Storage::disk('local')->delete($oldPath);
        return back()->with('success', 'Pelunasan diajukan. DP tetap tercatat, pelunasan menunggu verifikasi admin.');
    }
}
