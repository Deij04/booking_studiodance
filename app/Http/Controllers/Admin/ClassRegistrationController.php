<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['requests', 'active', 'history', 'classes', 'bookings'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(ClassRegistration::STATUSES))],
        ]);
        $search = trim($filters['search'] ?? '');
        $activeTab = in_array($filters['tab'] ?? 'classes', ['requests', 'bookings']) ? 'classes' : ($filters['tab'] ?? 'classes');
        $status = $activeTab === 'history' ? ($filters['status'] ?? '') : '';
        $date = $filters['date'] ?? '';
        $tabs = ['classes' => 'Jadwal Class', 'active' => 'Reservasi Aktif', 'history' => 'Riwayat Reservasi'];
        $tabCounts = collect(['active', 'history'])->mapWithKeys(fn ($key) => [$key => ClassRegistration::inReservationTab($key)->count()]);
        $tabCounts['classes'] = ClassSchedule::count();
        $registrations = ClassRegistration::inReservationTab($activeTab)->with(['user', 'payment', 'classSchedule.danceClass', 'classSchedule.studio'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                ->orWhereHas('classSchedule.danceClass', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when($status !== '', fn ($q) => $q->where('registration_status', $status))
            ->when($date !== '', fn ($q) => $q->whereHas('classSchedule', fn ($s) => $s->whereDate('date', $date)))
            ->latest('id')->paginate(10)->withQueryString();

        $schedules = ClassSchedule::with(['danceClass', 'studio', 'classRegistrations.user', 'classRegistrations.payment'])
            ->withCount(['classRegistrations as occupied' => fn ($q) => $q->where('registration_status', '!=', 'cancelled')])
            ->orderByRaw('CASE WHEN date >= ? THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->paginate(6, ['*'], 'classes_page')->withQueryString();

        return view('admin.class-registrations.index', compact('registrations', 'schedules', 'search', 'status', 'activeTab', 'tabs', 'tabCounts', 'date'));
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
            if ($schedule->classRegistrations()->where('user_id', $data['user_id'])->where('registration_status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['user_id' => 'Peserta sudah terdaftar pada jadwal ini.']);
            }
            $record = ClassRegistration::create($data + [
                'registration_date' => today(), 'price' => $schedule->danceClass->price,
                'registration_status' => 'pending',
            ]);
            $record->payment()->create(['payment_stage' => 'initial', 'payment_method' => 'cash', 'amount' => $schedule->danceClass->price]);
        });

        return to_route('admin.class-registrations.index')->with('success', 'Reservasi class berhasil ditambahkan.');
    }

    public function show(ClassRegistration $classRegistration)
    {
        return view('admin.class-registrations.show', [
            'registration' => $classRegistration->load(['user', 'payment', 'classSchedule.danceClass', 'classSchedule.studio']),
        ]);
    }

    public function verification(ClassRegistration $classRegistration)
    {
        abort_unless($classRegistration->payment_status === 'unpaid' && $classRegistration->registration_status === 'pending', 403);
        return view('admin.class-registrations.verify', ['registration' => $classRegistration->load(['user', 'payment', 'classSchedule.danceClass', 'classSchedule.studio'])]);
    }

    public function decide(Request $request, ClassRegistration $classRegistration)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['verified', 'rejected'])],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000', 'regex:/\S/u']]);
        DB::transaction(function () use ($classRegistration, $data, $request) {
            $schedule = ClassSchedule::lockForUpdate()->findOrFail($classRegistration->class_schedule_id);
            $record = ClassRegistration::lockForUpdate()->findOrFail($classRegistration->id);
            abort_unless($record->payment_status === 'unpaid' && $record->registration_status === 'pending', 403);
            $payment = $record->payment;
            if ($payment?->verification_status === 'rejected') {
                throw ValidationException::withMessages(['decision' => 'Pengajuan ini sudah ditolak. Pelanggan perlu mendaftar lagi.']);
            }
            if ($data['decision'] === 'verified' && ($schedule->status !== 'active' || ($payment && $payment->payment_type !== 'full'))) {
                throw ValidationException::withMessages(['decision' => 'Verifikasi hanya untuk pembayaran lunas pada jadwal aktif.']);
            }
            $record->payment()->updateOrCreate([], [
                'payment_method' => $payment?->payment_method ?? 'cash', 'payment_type' => 'full',
                'verification_status' => $data['decision'], 'rejection_reason' => $data['decision'] === 'rejected' ? trim($data['rejection_reason']) : null,
                'verified_by' => $request->user()->id, 'verified_by_name' => $request->user()->name, 'verified_at' => now(),
            ]);
            if ($data['decision'] === 'verified') {
                $record->unsetRelation('payment');
                $record->update(['registration_status' => 'confirmed']);
            } else {
                $record->update(['registration_status' => 'cancelled']);
            }
        });
        app(\App\Services\BookingCompletion::class)->run();
        return to_route('admin.class-registrations.index', ['tab' => $data['decision'] === 'rejected' || $classRegistration->fresh()->registration_status === 'completed' ? 'history' : 'active'])->with('success', $data['decision'] === 'verified' ? 'Pembayaran class terverifikasi dan reservasi dikonfirmasi.' : 'Pengajuan reservasi class ditolak. Pelanggan dapat mendaftar class lagi.');
    }

    public function edit(ClassRegistration $classRegistration)
    {
        abort_unless(in_array($classRegistration->registration_status, ['pending', 'confirmed'], true), 403);
        $classRegistration->load(['user', 'payment', 'classSchedule.danceClass', 'classSchedule.studio']);
        abort_unless(Carbon::parse($classRegistration->classSchedule->date.' '.$classRegistration->classSchedule->end_time)->gt(now()), 403, 'Jadwal class sudah selesai.');
        $schedules = ClassSchedule::with(['danceClass', 'studio'])
            ->where('status', 'active')
            ->whereHas('studio', fn ($q) => $q->where('status', 'active'))
            ->whereHas('danceClass', fn ($q) => $q->where('status', 'active'))
            ->where(fn ($q) => $q->where('date', '>', today()->toDateString())->orWhere(fn ($q) => $q->whereDate('date', today())->whereTime('end_time', '>', now()->format('H:i:s'))))
            ->withCount(['classRegistrations as occupied' => fn ($q) => $q->where('registration_status', '!=', 'cancelled')])
            ->orderBy('date')->orderBy('start_time')->get();
        return view('admin.class-registrations.transfer', ['registration' => $classRegistration, 'schedules' => $schedules, 'users' => User::where('role', 'customer')->where('status', 'active')->orderBy('name')->get()]);
    }

    public function update(Request $request, ClassRegistration $classRegistration)
    {
        $data = $request->validate(['class_schedule_id' => ['required', 'integer', 'exists:class_schedules,id'],
            'payment_status' => ['prohibited'], 'registration_status' => ['prohibited'], 'user_id' => ['sometimes', 'required', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')]]);
        DB::transaction(function () use ($classRegistration, $data) {
            $schedules = ClassSchedule::whereIn('id', [$classRegistration->class_schedule_id, $data['class_schedule_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $record = ClassRegistration::lockForUpdate()->findOrFail($classRegistration->id);
            abort_unless(in_array($record->registration_status, ['pending', 'confirmed'], true), 403);
            $source = $schedules->get($record->class_schedule_id);
            $target = $schedules->get($data['class_schedule_id']);
            if (!$source || !$target || Carbon::parse($source->date.' '.$source->end_time)->lte(now())) {
                throw ValidationException::withMessages(['class_schedule_id' => 'Jadwal asal sudah selesai atau jadwal tujuan tidak tersedia.']);
            }
            $this->ensureAvailable($target, $record->id, true);
            if ($record->settlement?->status === 'pending') throw ValidationException::withMessages(['class_schedule_id' => 'Periksa pelunasan yang menunggu verifikasi sebelum mengalihkan kelas.']);
            $userId = $data['user_id'] ?? $record->user_id;
            if ($target->classRegistrations()->where('user_id', $userId)->where('id', '!=', $record->id)->where('registration_status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['class_schedule_id' => 'Peserta sudah terdaftar pada jadwal tujuan.']);
            }
            $record->update(['class_schedule_id' => $target->id, 'user_id' => $userId,
                'price' => $target->id === $source->id ? $record->price : $target->danceClass->price]);
        });
        return to_route('admin.class-registrations.index', ['tab' => $classRegistration->fresh()->registration_status === 'pending' ? 'classes' : 'active'])->with('success', 'Peserta dan jadwal diperbarui. Pembayaran tetap tercatat pada reservasi yang sama.');
    }

    public function settlementForm(ClassRegistration $classRegistration)
    {
        $summary = app(\App\Services\PaymentSummary::class)->for($classRegistration);
        abort_unless($summary['canSettle'], 403);
        return view('admin.class-registrations.settlement', ['registration' => $classRegistration, 'summary' => $summary]);
    }

    public function settle(Request $request, ClassRegistration $classRegistration)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(\App\Models\Booking::PAYMENT_METHODS))],
            'payment_proof' => ['required_unless:payment_method,cash', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);
        $path = $request->file('payment_proof')?->store('settlement-proofs', 'local');
        try {
            DB::transaction(function () use ($request, $classRegistration, $data, $path) {
                $record = ClassRegistration::lockForUpdate()->findOrFail($classRegistration->id);
                $summary = app(\App\Services\PaymentSummary::class)->for($record);
                if (!$summary['canSettle']) throw ValidationException::withMessages(['payment_method' => 'Tidak ada pelunasan yang dapat dicatat.']);
                $record->settlements()->create(['amount' => $summary['remaining'], 'payment_method' => $data['payment_method'],
                    'payment_proof' => $path ?? '', 'verification_status' => 'verified', 'payment_stage' => 'settlement', 'verified_by' => $request->user()->id,
                    'verified_by_name' => $request->user()->name, 'verified_at' => now()]);
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }
        app(\App\Services\BookingCompletion::class)->run();
        return to_route('admin.class-registrations.show', $classRegistration)->with('success', 'Pelunasan class tercatat dan terverifikasi.');
    }

    public function paymentProof(ClassRegistration $classRegistration)
    {
        $path = $classRegistration->payment?->payment_proof;
        abort_unless($path && str_starts_with($path, 'class-payment-proofs/') && !str_contains($path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureAvailable(ClassSchedule $schedule, ?int $ignoreId = null, bool $allowStarted = false): void
    {
        if ($schedule->status !== 'active' || $schedule->danceClass->status !== 'active' || $schedule->studio->status !== 'active'
            || Carbon::parse($schedule->date.' '.($allowStarted ? $schedule->end_time : $schedule->start_time))->lte(now())) {
            throw ValidationException::withMessages(['class_schedule_id' => 'Jadwal class sudah dimulai atau tidak aktif.']);
        }
        if ($schedule->classRegistrations()->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->where('registration_status', '!=', 'cancelled')->count() >= $schedule->capacity) {
            throw ValidationException::withMessages(['class_schedule_id' => 'Kuota class sudah penuh.']);
        }
    }
}
