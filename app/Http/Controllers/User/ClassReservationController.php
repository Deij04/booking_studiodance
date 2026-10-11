<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassReservationController extends Controller
{
    public function create(Request $request, DanceClass $danceClass)
    {
        $this->authorizeCustomer($request);
        abort_unless($danceClass->status === 'active', 404);
        $schedules = $danceClass->classSchedules()->where('status', 'active')
            ->whereHas('studio', fn ($query) => $query->where('status', 'active'))
            ->where(fn ($query) => $query->where('date', '>', today()->toDateString())
                ->orWhere(fn ($query) => $query->where('date', today()->toDateString())->where('start_time', '>', now()->format('H:i:s'))))
            ->with('studio')->withCount(['classRegistrations as occupied' => fn ($query) => $query->where('registration_status', '!=', 'cancelled')])
            ->orderBy('date')->orderBy('start_time')->get();
        $registeredIds = $request->user()->classRegistrations()->where('registration_status', '!=', 'cancelled')->pluck('class_schedule_id')->all();

        return view('user.classes.reservation', compact('danceClass', 'schedules', 'registeredIds'));
    }

    public function store(Request $request, DanceClass $danceClass)
    {
        $this->authorizeCustomer($request);
        $data = $request->validate([
            'class_schedule_id' => ['required', 'integer', Rule::exists('class_schedules', 'id')->where('dance_class_id', $danceClass->id)],
            'payment_method' => ['required', Rule::in(array_keys(Booking::ONLINE_PAYMENT_METHODS))],
            'payment_type' => ['required', Rule::in(['full'])],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $proof = $request->file('payment_proof')?->store('class-payment-proofs', 'local');
        abort_if($proof === false, 500, 'Bukti pembayaran gagal disimpan.');
        try {
            DB::transaction(function () use ($request, $danceClass, $data, $proof) {
                $schedule = ClassSchedule::where('dance_class_id', $danceClass->id)->lockForUpdate()->findOrFail($data['class_schedule_id']);
                $schedule->load(['danceClass', 'studio']);
                if ($schedule->status !== 'active' || $schedule->danceClass->status !== 'active' || $schedule->studio->status !== 'active'
                    || Carbon::parse($schedule->date.' '.$schedule->start_time)->lte(now())) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Sesi kelas sudah dimulai atau tidak tersedia.']);
                }
                if ($schedule->classRegistrations()->where('user_id', $request->user()->id)->where('registration_status', '!=', 'cancelled')->exists()) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Anda sudah mendaftar sesi ini.']);
                }
                if ($schedule->classRegistrations()->where('registration_status', '!=', 'cancelled')->count() >= $schedule->capacity) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Kuota sesi kelas sudah penuh. Pilih sesi lain.']);
                }
                $registration = ClassRegistration::create([
                    'user_id' => $request->user()->id, 'class_schedule_id' => $schedule->id,
                    'registration_date' => today(), 'price' => $schedule->danceClass->price,
                    'registration_status' => 'pending',
                ]);
                $registration->payment()->create([
                    'payment_method' => $data['payment_method'], 'payment_type' => 'full',
                    'payment_proof' => $proof, 'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($proof) Storage::disk('local')->delete($proof);
            throw $exception;
        }

        return to_route('user.history')->with('success', 'Reservasi kelas berhasil diajukan. Tunggu verifikasi pembayaran dan konfirmasi admin.');
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
    }
}
