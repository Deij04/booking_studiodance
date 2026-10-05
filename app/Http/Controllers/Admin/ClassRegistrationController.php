<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(ClassRegistration::STATUSES))],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $registrations = ClassRegistration::with(['user', 'classSchedule.danceClass', 'classSchedule.studio'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                ->orWhereHas('classSchedule.danceClass', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when($status !== '', fn ($q) => $q->where('registration_status', $status))
            ->latest('id')->paginate(10)->withQueryString();

        $schedules = ClassSchedule::with(['danceClass', 'studio', 'classRegistrations.user'])
            ->withCount(['classRegistrations as occupied' => fn ($q) => $q->where('registration_status', '!=', 'cancelled')])
            ->orderByRaw('CASE WHEN date >= ? THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->paginate(6, ['*'], 'classes_page')->withQueryString();

        return view('admin.class-registrations.index', compact('registrations', 'schedules', 'search', 'status'));
    }

    public function create()
    {
        $users = User::where('role', 'customer')->where('status', 'active')->orderBy('name')->get();
        $schedules = ClassSchedule::with(['danceClass', 'studio'])
            ->withCount(['classRegistrations as occupied' => fn ($q) => $q->where('registration_status', '!=', 'cancelled')])
            ->where('status', 'active')->whereHas('danceClass', fn ($q) => $q->where('status', 'active'))
            ->whereHas('studio', fn ($q) => $q->where('status', 'active'))
            ->where(fn ($q) => $q->where('date', '>', today()->toDateString())->orWhere(fn ($q) => $q
                ->where('date', today()->toDateString())->where('start_time', '>', now()->format('H:i:s'))))
            ->orderBy('date')->orderBy('start_time')->get();

        return view('admin.class-registrations.form', ['registration' => new ClassRegistration, 'users' => $users, 'schedules' => $schedules]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')],
            'class_schedule_id' => ['required', 'exists:class_schedules,id'],
        ]);
        DB::transaction(function () use ($data) {
            $schedule = ClassSchedule::lockForUpdate()->findOrFail($data['class_schedule_id']);
            $this->ensureAvailable($schedule);
            if ($schedule->classRegistrations()->where('user_id', $data['user_id'])->exists()) {
                throw ValidationException::withMessages(['user_id' => 'Peserta sudah terdaftar pada jadwal ini. Gunakan Edit untuk mengaktifkan kembali reservasi yang dibatalkan.']);
            }
            ClassRegistration::create($data + [
                'registration_date' => today(), 'price' => $schedule->danceClass->price,
                'payment_status' => 'unpaid', 'registration_status' => 'pending',
            ]);
        });

        return to_route('admin.class-registrations.index')->with('success', 'Reservasi class berhasil ditambahkan.');
    }

    public function edit(ClassRegistration $classRegistration)
    {
        return view('admin.class-registrations.form', ['registration' => $classRegistration->load(['user', 'classSchedule.danceClass', 'classSchedule.studio'])]);
    }

    public function update(Request $request, ClassRegistration $classRegistration)
    {
        $data = $request->validate([
            'registration_status' => ['required', Rule::in(array_keys(ClassRegistration::STATUSES))],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
        ]);
        DB::transaction(function () use ($classRegistration, $data) {
            $schedule = ClassSchedule::lockForUpdate()->findOrFail($classRegistration->class_schedule_id);
            $registration = ClassRegistration::lockForUpdate()->findOrFail($classRegistration->id);
            if (in_array($data['registration_status'], ['pending', 'confirmed']) && $schedule->status !== 'active') {
                throw ValidationException::withMessages(['registration_status' => 'Jadwal class nonaktif. Aktifkan jadwal sebelum mengaktifkan reservasi.']);
            }
            if ($registration->registration_status === 'cancelled' && $data['registration_status'] !== 'cancelled') {
                $this->ensureAvailable($schedule);
            }
            if (in_array($data['registration_status'], ['confirmed', 'completed']) && $data['payment_status'] !== 'paid') {
                throw ValidationException::withMessages(['payment_status' => 'Reservasi harus lunas sebelum dikonfirmasi atau diselesaikan.']);
            }
            if ($data['registration_status'] === 'completed' && Carbon::parse($schedule->date.' '.$schedule->end_time)->isFuture()) {
                throw ValidationException::withMessages(['registration_status' => 'Class belum selesai.']);
            }
            $registration->update($data);
        });

        return to_route('admin.class-registrations.index')->with('success', 'Reservasi class berhasil diperbarui.');
    }

    private function ensureAvailable(ClassSchedule $schedule): void
    {
        if ($schedule->status !== 'active' || $schedule->danceClass->status !== 'active' || $schedule->studio->status !== 'active'
            || Carbon::parse($schedule->date.' '.$schedule->start_time)->lte(now())) {
            throw ValidationException::withMessages(['class_schedule_id' => 'Jadwal class sudah dimulai atau tidak aktif.']);
        }
        if ($schedule->classRegistrations()->where('registration_status', '!=', 'cancelled')->count() >= $schedule->capacity) {
            throw ValidationException::withMessages(['class_schedule_id' => 'Kuota class sudah penuh.']);
        }
    }
}
