<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReservationSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettlementController extends Controller
{
    public function decide(Request $request, ReservationSettlement $settlement)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['verified', 'rejected'])]]);
        DB::transaction(function () use ($settlement, $data, $request) {
            $parent = $settlement->reservation ?? $settlement->classRegistration;
            abort_unless($parent, 404);
            $parent = $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $settlement = ReservationSettlement::lockForUpdate()->findOrFail($settlement->id);
            if ($settlement->status !== 'pending') throw ValidationException::withMessages(['decision' => 'Pelunasan ini sudah diproses.']);
            $status = $parent->status ?? $parent->registration_status;
            if ($data['decision'] === 'verified' && ($parent->payment_status !== 'paid' || $status !== 'confirmed')) {
                throw ValidationException::withMessages(['decision' => 'DP harus terverifikasi dan reservasi harus dikonfirmasi sebelum memverifikasi pelunasan.']);
            }
            $settlement->update(['status' => $data['decision'], 'verified_by' => $request->user()->id,
                'verified_by_name' => $request->user()->name, 'verified_at' => now()]);
        });
        return back()->with('success', 'Status pelunasan diperbarui.');
    }

    public function proof(ReservationSettlement $settlement)
    {
        $path = $settlement->payment_proof;
        abort_unless(str_starts_with($path, 'settlement-proofs/') && !str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
