<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingActivity;
use App\Models\Reservation;
use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
use App\Services\BookingAvailability;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public const TABS = [
        'requests' => ['label' => 'Pengajuan Booking', 'statuses' => ['pending']],
        'active' => ['label' => 'Booking Aktif', 'statuses' => ['confirmed']],
        'history' => ['label' => 'Riwayat Booking', 'statuses' => ['completed', 'cancelled']],
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['requests', 'history', 'active'])],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Reservation::STATUSES))],
            'payment_type' => ['nullable', Rule::in(['full', 'dp'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $filters['search'] = trim($filters['search'] ?? '');
        $tab = $filters['tab'] ?? 'requests';
        $tabs = self::TABS;
        $counts = Reservation::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $tabCounts = collect($tabs)->map(fn ($item) => collect($item['statuses'])->sum(fn ($status) => $counts[$status] ?? 0));
        $bookings = Reservation::with(['user', 'studioSchedule.studio', 'settlement'])
            ->whereIn('status', $tabs[$tab]['statuses'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $query->where('booking_code', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('studioSchedule.studio', fn ($studio) => $studio->where('name', 'like', $term));
                });
            })
            ->when($tab === 'history' ? ($filters['status'] ?? null) : null, fn ($query, $status) => $query->where('status', $status))
            ->when($tab === 'requests' ? ($filters['payment_type'] ?? null) : null, fn ($query, $type) => $query->where('payment_type', $type))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereHas('studioSchedule', fn ($schedule) => $schedule->whereDate('date', $date)))
            ->when($tab === 'active', fn ($query) => $query
                ->orderBy(StudioSchedule::select('date')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id'))
                ->orderBy(StudioSchedule::select('start_time')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id')))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'filters', 'tab', 'tabs', 'tabCounts'));
    }

    public function create(): View|RedirectResponse
    {
        return view('admin.bookings.create', [
            'hours' => app(\App\Services\BookingHours::class)->current(),
            'customers' => User::where('role', 'customer')->where('status', 'active')->orderBy('name')->get(),
            'studios' => Studio::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function availability(Request $request, Studio $studio)
    {
        abort_unless($studio->status === 'active', 404);
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);

        return response()->json(['days' => app(BookingAvailability::class)->month($studio, $data['month'])])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->storeBooking($request);
    }

    protected function storeBooking(Request $request, bool $customer = false): RedirectResponse
    {
        if ($customer) {
            abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
            $request->merge(['user_id' => $request->user()->id, 'payment_status' => 'unpaid', 'booking_name' => $request->user()->name]);
        }
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')],
            'studio_id' => ['required', 'integer', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'end_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:start_time'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'payment_proof' => [$customer ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'payment_status' => ['required', Rule::in(array_keys(Reservation::PAYMENT_STATUSES))],
            'payment_method' => ['nullable', Rule::in(array_keys(Reservation::PAYMENT_METHODS))],
            'payment_type' => ['nullable', Rule::in(array_keys(Reservation::PAYMENT_TYPES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($customer) {
            $details = $request->validate([
                'payment_method' => ['required', Rule::in(array_keys(Reservation::ONLINE_PAYMENT_METHODS))],
                'booking_name' => ['required', 'string', 'max:255'],
                'purpose' => ['required', Rule::in(['Group Training', 'Private Practice', 'Dance Rehearsal', 'Workshop', 'Other'])],
            ]);
            $data = array_merge($data, $details);
        }
        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);
        if ($start->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Jam mulai booking harus di masa mendatang.']);
        }
        $this->saveWithProof($request, function ($proof) use ($data, $start, $end, $customer, $request) {
            $studio = Studio::whereKey($data['studio_id'])->where('status', 'active')->lockForUpdate()->first();
            if (! $studio) {
                throw ValidationException::withMessages(['studio_id' => 'Studio sudah tidak tersedia.']);
            }
            $available = app(BookingAvailability::class)->month($studio, substr($data['date'], 0, 7));
            if (! in_array($data['end_time'], $available[$data['date']][$data['start_time']] ?? [], true)) {
                throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih sudah terisi atau di luar jam operasional. Pilih ulang tanggal dan jam.']);
            }
            $overlap = fn ($query) => $query->whereDate('date', $data['date'])
                ->whereTime('start_time', '<', $data['end_time'].':00')->whereTime('end_time', '>', $data['start_time'].':00');
            $occupied = $overlap($studio->studioSchedules())->where(fn ($query) => $query
                ->whereIn('status', ['booked', 'blocked'])
                ->orWhereHas('reservation', fn ($booking) => $booking->where('status', '!=', 'cancelled')))->exists();
            if ($occupied || $overlap($studio->classSchedules())->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['start_time' => 'Jadwal bentrok dengan booking, kelas, atau jadwal yang diblokir. Pilih waktu lain.']);
            }
            $price = round((float) $studio->price_per_hour * $start->diffInMinutes($end) / 60, 2);
            if ($price > 9999999999.99) {
                throw ValidationException::withMessages(['end_time' => 'Total biaya melebihi batas. Kurangi durasi booking.']);
            }
            $schedule = $studio->studioSchedules()->create([
                'date' => $data['date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'status' => 'booked',
            ]);
            $booking = Reservation::create([
                'booking_code' => 'RES-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'user_id' => $data['user_id'], 'studio_schedule_id' => $schedule->id,
                'purpose' => $data['purpose'] ?? null, 'notes' => $data['notes'] ?? null,
                'total_price' => $price, 'payment_status' => $data['payment_status'], 'status' => $customer ? 'pending' : 'confirmed',
                'payment_proof' => $proof,
                'booking_name' => $data['booking_name'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_type' => $data['payment_type'] ?? 'full',
                'verified_by' => !$customer && $data['payment_status'] === 'paid' ? $request->user()->id : null,
                'verified_by_name' => !$customer && $data['payment_status'] === 'paid' ? $request->user()->name : null,
                'verified_at' => !$customer && $data['payment_status'] === 'paid' ? now() : null,
            ]);
            $this->recordActivity($booking, 'created', []);
        });

        if ($customer) {
            return to_route('user.history')->with('success', 'Reservasi berhasil diajukan. Tunggu konfirmasi dari admin.');
        }

        return to_route('admin.bookings.index', ['tab' => 'active'])->with('success', 'Booking berhasil dibuat dan langsung dikonfirmasi.');
    }

    public function decide(Request $request, Reservation $booking): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000', 'regex:/\S/u'],
        ]);
        DB::transaction(function () use ($booking, $data) {
            $booking = Reservation::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($booking->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Pengajuan ini sudah diproses. Muat ulang daftar booking.']);
            }
            $schedule = StudioSchedule::whereKey($booking->studio_schedule_id)->lockForUpdate()->firstOrFail();
            $accept = $data['decision'] === 'accept';
            if ($accept && $schedule->status === 'blocked') {
                throw ValidationException::withMessages(['decision' => 'Jadwal studio sedang diblokir.']);
            }
            $before = $booking->getAttributes();
            $booking->update([
                'status' => $accept ? 'confirmed' : 'cancelled',
                'rejection_reason' => $accept ? null : trim($data['rejection_reason']),
            ]);
            $this->recordActivity($booking, $accept ? 'accepted' : 'rejected', $before);
            if ($schedule->status !== 'blocked') {
                $schedule->update(['status' => $accept ? 'booked' : 'available']);
            }
        });

        return to_route('admin.bookings.index')->with('success', $data['decision'] === 'accept'
            ? 'Pengajuan diterima dan masuk ke Booking Aktif.'
            : 'Pengajuan ditolak dan dipindahkan ke Riwayat.');
    }

    public function show(Reservation $booking): View
    {
        $booking->load(['user', 'studioSchedule.studio']);
        $activities = auth()->user()->role === 'super_admin'
            ? BookingActivity::where('reservation_id', $booking->id)->latest('id')->paginate(10)
            : null;

        return view('admin.bookings.edit', ['booking' => $booking, 'activities' => $activities, 'readOnly' => true]);
    }

    public function edit(Reservation $booking): View
    {
        abort_unless($booking->status === 'confirmed', 403, 'Hanya booking aktif yang dapat diedit.');
        $booking->load(['user', 'studioSchedule.studio']);

        $activities = auth()->user()->role === 'super_admin'
            ? BookingActivity::where('reservation_id', $booking->id)->latest('id')->paginate(10)
            : null;

        return view('admin.bookings.edit', compact('booking', 'activities'));
    }

    public function update(Request $request, Reservation $booking): RedirectResponse
    {
        abort_unless($booking->status === 'confirmed', 403, 'Hanya booking aktif yang dapat diedit.');
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'status' => ['required', Rule::in(array_keys(Reservation::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Reservation::PAYMENT_STATUSES))],
            'payment_method' => ['nullable', Rule::in(array_keys(Reservation::PAYMENT_METHODS))],
            'payment_type' => ['nullable', Rule::in(array_keys(Reservation::PAYMENT_TYPES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        unset($data['payment_proof']);
        $this->saveWithProof($request, function ($proof) use ($booking, $data) {
            $booking = Reservation::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'confirmed', 403, 'Hanya booking aktif yang dapat diedit.');
            $schedule = StudioSchedule::whereKey($booking->studio_schedule_id)->lockForUpdate()->firstOrFail();
            if ($booking->status === 'pending' && $data['status'] === 'cancelled') {
                if (! preg_match('/\S/u', $data['rejection_reason'] ?? '')) {
                    throw ValidationException::withMessages(['rejection_reason' => 'Alasan penolakan wajib diisi.']);
                }
                $data['rejection_reason'] = trim($data['rejection_reason']);
            } else {
                unset($data['rejection_reason']);
            }
            if ($booking->status === 'confirmed' && $data['status'] === 'pending') {
                throw ValidationException::withMessages(['status' => 'Booking yang sudah diterima tidak dapat dikembalikan ke pengajuan.']);
            }
            if (in_array($booking->status, ['cancelled', 'completed'], true) && $data['status'] !== $booking->status) {
                throw ValidationException::withMessages(['status' => 'Booking yang dibatalkan atau selesai tidak dapat diaktifkan kembali.']);
            }
            if ($data['status'] === 'completed' && $data['payment_status'] !== 'paid') {
                throw ValidationException::withMessages(['payment_status' => 'Pembayaran harus terverifikasi sebelum booking diselesaikan.']);
            }
            $paymentType = $data['payment_type'] ?? $booking->payment_type;
            if ($data['status'] === 'completed' && $paymentType === 'dp' && $booking->settlement?->status !== 'verified') {
                throw ValidationException::withMessages(['payment_status' => 'Pelunasan harus terverifikasi sebelum penyewaan diselesaikan.']);
            }
            if ($booking->settlement && (($data['payment_type'] ?? $booking->payment_type) !== $booking->payment_type || $data['payment_status'] !== 'paid')) {
                throw ValidationException::withMessages(['payment_status' => 'DP dengan pengajuan pelunasan tidak boleh diubah. Proses pelunasan secara terpisah.']);
            }
            if ($data['status'] !== 'cancelled' && $schedule->status === 'blocked') {
                throw ValidationException::withMessages(['status' => 'Jadwal studio sedang diblokir.']);
            }
            $oldProof = $booking->payment_proof;
            $before = $booking->getAttributes();
            if ($proof) {
                $data['payment_proof'] = $proof;
            }
            if ($data['payment_status'] === 'unpaid') {
                $data += ['verified_by' => null, 'verified_by_name' => null, 'verified_at' => null];
            } elseif ($booking->payment_status !== 'paid' || $proof) {
                $data += ['verified_by' => auth()->id(), 'verified_by_name' => auth()->user()->name, 'verified_at' => now()];
            }
            $booking->update($data);
            $this->recordActivity($booking, 'updated', $before);
            if ($schedule->status !== 'blocked') {
                $schedule->update(['status' => $data['status'] === 'cancelled' ? 'available' : 'booked']);
            }

            return $proof ? $oldProof : null;
        });

        return to_route('admin.bookings.index')->with('success', 'Booking berhasil diperbarui.');
    }

    public function paymentProof(Reservation $booking)
    {
        $path = $booking->payment_proof;
        abort_unless($path && str_starts_with($path, 'payment-proofs/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function saveWithProof(Request $request, \Closure $save): void
    {
        $path = $request->file('payment_proof')?->store('payment-proofs', 'local');
        abort_if($path === false, 500, 'Bukti transfer gagal disimpan.');
        try {
            $oldPath = DB::transaction(fn () => $save($path));
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($oldPath && str_starts_with($oldPath, 'payment-proofs/') && ! str_contains($oldPath, '..')) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    private function recordActivity(Reservation $booking, string $action, array $before): void
    {
        $changes = [];
        foreach (['user_id', 'studio_schedule_id', 'purpose', 'total_price', 'status', 'payment_status', 'payment_method', 'payment_type', 'verified_by', 'verified_by_name', 'verified_at', 'notes', 'payment_proof', 'rejection_reason'] as $field) {
            $old = $before[$field] ?? null;
            $new = $booking->getAttributes()[$field] ?? null;
            if ((string) $old !== (string) $new) {
                $changes[$field] = $field === 'payment_proof'
                    ? ['before' => $old ? 'Bukti sebelumnya' : null, 'after' => $new ? 'Bukti baru diunggah' : null]
                    : ['before' => $old, 'after' => $new];
            }
        }
        if ($changes === []) {
            return;
        }
        $actor = auth()->user();
        BookingActivity::create([
            'reservation_id' => $booking->id, 'actor_id' => $actor->id,
            'actor_name' => $actor->name, 'actor_email' => $actor->email, 'actor_role' => $actor->role,
            'action' => $action, 'changes' => $changes,
        ]);
    }
}
