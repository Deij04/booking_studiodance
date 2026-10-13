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
        $alreadyRegistered = $request->user()->classRegistrations()
            ->whereIn('registration_status', ['pending', 'confirmed'])
            ->whereHas('classSchedule', fn ($query) => $query->where('dance_class_id', $danceClass->id))->exists();
        $registeredIds = $alreadyRegistered ? $schedules->pluck('id')->all() : [];

        $classMembership = $request->user()->memberships()->validOn(today()->toDateString())->where('free_classes', true)->first();
        return view('user.classes.reservation', compact('danceClass', 'schedules', 'registeredIds', 'alreadyRegistered', 'classMembership'));
    }

    public function store(Request $request, DanceClass $danceClass)
    {
        $this->authorizeCustomer($request);
        $requestedSchedule = $danceClass->classSchedules()->find($request->input('class_schedule_id'));
        $membershipBenefit = $requestedSchedule && $request->user()->memberships()->validOn($requestedSchedule->date)->where('free_classes', true)->exists();
        $data = $request->validate([
            'class_schedule_id' => ['required', 'integer', Rule::exists('class_schedules', 'id')->where('dance_class_id', $danceClass->id)],
            'payment_method' => [$membershipBenefit ? 'nullable' : 'required', Rule::in(array_keys(Booking::ONLINE_PAYMENT_METHODS))],
            'payment_type' => ['required', Rule::in(['full'])],
            'payment_proof' => [$membershipBenefit ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $proof = $request->file('payment_proof')?->store('class-payment-proofs', 'local');
        abort_if($proof === false, 500, 'Bukti pembayaran gagal disimpan.');
        try {
            DB::transaction(function () use ($request, $danceClass, $data, $proof) {
                \App\Models\User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $schedule = ClassSchedule::where('dance_class_id', $danceClass->id)->lockForUpdate()->findOrFail($data['class_schedule_id']);
                $schedule->load(['danceClass', 'studio']);
                $membership = $request->user()->memberships()->validOn($schedule->date)->where('free_classes', true)->lockForUpdate()->first();
                if (!$membership && empty($proof)) throw ValidationException::withMessages(['payment_proof' => 'Membership tidak berlaku pada tanggal sesi ini. Silakan kirim bukti pembayaran.']);
                if ($schedule->status !== 'active' || $schedule->danceClass->status !== 'active' || $schedule->studio->status !== 'active'
                    || Carbon::parse($schedule->date.' '.$schedule->start_time)->lte(now())) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Sesi kelas sudah dimulai atau tidak tersedia.']);
                }
                if ($request->user()->classRegistrations()->whereIn('registration_status', ['pending', 'confirmed'])
                    ->whereHas('classSchedule', fn ($query) => $query->where('dance_class_id', $danceClass->id))->exists()) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Anda sudah memiliki reservasi pada class ini yang menunggu atau dikonfirmasi. Tidak dapat mendaftar lagi pada jadwal lain.']);
                }
                if ($schedule->classRegistrations()->where('registration_status', '!=', 'cancelled')->count() >= $schedule->capacity) {
                    throw ValidationException::withMessages(['class_schedule_id' => 'Kuota sesi kelas sudah penuh. Pilih sesi lain.']);
                }
                $registration = ClassRegistration::create([
                    'user_id' => $request->user()->id, 'class_schedule_id' => $schedule->id,
                    'registration_date' => today(), 'price' => $membership ? 0 : $schedule->danceClass->price,
                    'membership_id' => $membership?->id,
                    'registration_status' => $membership ? 'confirmed' : 'pending',
                ]);
                $registration->payment()->create([
                    'payment_method' => $membership ? 'membership' : $data['payment_method'], 'payment_type' => 'full',
                    'amount' => $membership ? 0 : $schedule->danceClass->price,
                    'verification_status' => $membership ? 'verified' : 'pending',
                    'verified_at' => $membership ? now() : null,
                    'verified_by_name' => $membership ? 'Otomatis - Benefit Membership' : null,
                    'payment_proof' => $proof, 'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($proof) Storage::disk('local')->delete($proof);
            throw $exception;
        }

        return to_route('user.history')->with('success', 'Reservasi class berhasil disimpan. Benefit membership yang berlaku otomatis dikonfirmasi; pembayaran reguler menunggu verifikasi admin.');
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
    }
}
