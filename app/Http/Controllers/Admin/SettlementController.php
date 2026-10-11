<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettlementController extends Controller
{
    public function decide(Request $request, Payment $settlement)
    {
        abort_unless($settlement->payment_stage === 'settlement', 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['verified', 'rejected'])]]);
        DB::transaction(function () use ($settlement, $data, $request) {
            $parent = $settlement->booking ?? $settlement->classRegistration;
            abort_unless($parent, 404);
            $parent = $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $settlement = Payment::lockForUpdate()->findOrFail($settlement->id);
            if ($settlement->status !== 'pending') throw ValidationException::withMessages(['decision' => 'Pelunasan ini sudah diproses.']);
            $status = $parent->status ?? $parent->registration_status;
            if ($data['decision'] === 'verified' && ($parent->payment_status !== 'paid' || $status !== 'confirmed')) {
                throw ValidationException::withMessages(['decision' => 'DP harus terverifikasi dan reservasi harus dikonfirmasi sebelum memverifikasi pelunasan.']);
            }
            if ($data['decision'] === 'verified') {
                $summary = app(\App\Services\PaymentSummary::class)->for($parent);
                if (abs($summary['remaining'] - (float) $settlement->amount) > 0.01) throw ValidationException::withMessages(['decision' => 'Nominal pelunasan tidak sesuai sisa pembayaran.']);
            }
            $settlement->update(['verification_status' => $data['decision'], 'verified_by' => $request->user()->id,
                'verified_by_name' => $request->user()->name, 'verified_at' => now()]);
        });
        app(\App\Services\BookingCompletion::class)->run();
        return back()->with('success', 'Status pelunasan diperbarui.');
    }

    public function proof(Payment $settlement)
    {
        abort_unless($settlement->payment_stage === 'settlement', 404);
        $path = $settlement->payment_proof;
        abort_unless($path && str_starts_with($path, 'settlement-proofs/') && !str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
