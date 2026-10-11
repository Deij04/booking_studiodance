<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Studio;
use App\Models\BookingSetting;
use App\Services\StudioConflicts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudioOperationController extends Controller
{
    public function index()
    {
        return to_route('admin.studios.index', ['tab' => 'operations']);
    }

    public function updateHours(Request $request)
    {
        $data = $request->validate([
            'opens_at' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'closes_at' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:opens_at'],
        ]);
        if (Carbon::parse($data['opens_at'])->diffInMinutes(Carbon::parse($data['closes_at'])) < config('booking.minimum_minutes')) {
            throw ValidationException::withMessages(['closes_at' => 'Rentang jam operasional minimal '.config('booking.minimum_minutes').' menit.']);
        }
        DB::transaction(function () use ($data) {
            // Serialize with studio booking, blocking, and class schedule mutations.
            Studio::orderBy('id')->lockForUpdate()->get();
            $upcoming = fn ($q) => $q->where('date', '>', today()->toDateString())
                ->orWhere(fn ($q) => $q->whereDate('date', today())->whereTime('end_time', '>', now()->format('H:i:s')));
            $outside = fn ($q) => $q->whereTime('start_time', '<', $data['opens_at'].':00')
                ->orWhereTime('end_time', '>', $data['closes_at'].':00');
            $classes = ClassSchedule::where('status', 'active')->where($upcoming)->where($outside)->exists();
            $bookings = \App\Models\Booking::where($upcoming)->where($outside)->where('status', '!=', 'cancelled')->exists();
            if ($classes || $bookings) {
                throw ValidationException::withMessages(['opens_at' => 'Masih ada kelas atau booking mendatang di luar jam baru. Sesuaikan jadwal tersebut sebelum mengubah jam operasional.']);
            }
            DB::table('booking_settings')->updateOrInsert(['kind' => 'operating_hours'], $data + ['created_at' => now(), 'updated_at' => now()]);
        });

        return to_route('admin.studios.index', ['tab' => 'operations'])->with('success', 'Jam operasional semua studio berhasil diperbarui.');
    }

    public function block(Request $request)
    {
        $data = $request->validate([
            'studio_id' => ['required', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'end_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:start_time'],
            'block_reason' => ['required', 'string', 'max:500', 'regex:/\S/u'],
        ]);
        if (Carbon::parse($data['date'].' '.$data['start_time'])->lte(now())) {
            throw ValidationException::withMessages(['start_time' => 'Waktu mulai blokir harus di masa mendatang.']);
        }
        DB::transaction(function () use ($data) {
            $studio = Studio::lockForUpdate()->findOrFail($data['studio_id']);
            if ($studio->status !== 'active') {
                throw ValidationException::withMessages(['studio_id' => 'Studio sudah tidak aktif.']);
            }
            $hours = app(\App\Services\BookingHours::class)->current();
            if ($data['start_time'] < $hours['opens_at'] || $data['end_time'] > $hours['closes_at']) {
                throw ValidationException::withMessages(['start_time' => 'Blokir harus berada dalam jam operasional '.$hours['opens_at'].' - '.$hours['closes_at'].' WITA.']);
            }
            if (app(StudioConflicts::class)->exists($studio, $data)) {
                throw ValidationException::withMessages(['start_time' => 'Waktu blokir bertabrakan dengan kelas, booking, atau blokir lain.']);
            }
            $data['block_reason'] = trim($data['block_reason']);
            BookingSetting::create($data + ['status' => 'blocked']);
        });

        return to_route('admin.studios.index', ['tab' => 'operations'])->with('success', 'Jadwal studio berhasil diblokir.');
    }

    public function unblock(BookingSetting $bookingSetting)
    {
        DB::transaction(function () use ($bookingSetting) {
            Studio::whereKey($bookingSetting->studio_id)->lockForUpdate()->firstOrFail();
            $slot = BookingSetting::lockForUpdate()->findOrFail($bookingSetting->id);
            if ($slot->kind !== 'block' || $slot->status !== 'blocked') {
                throw ValidationException::withMessages(['block' => 'Jadwal ini bukan blokir mandiri yang dapat dibuka.']);
            }
            $slot->update(['status' => 'available']);
        });

        return to_route('admin.studios.index', ['tab' => 'operations'])->with('success', 'Blokir dibuka. Waktu tersebut kembali mengikuti ketersediaan studio.');
    }
}
