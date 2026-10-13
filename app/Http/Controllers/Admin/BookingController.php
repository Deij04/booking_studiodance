<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingActivity;
use App\Models\Booking;
use App\Models\Studio;
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
            'status' => ['nullable', Rule::in(array_keys(Booking::STATUSES))],
            'payment_type' => ['nullable', Rule::in(['full', 'dp'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $filters['search'] = trim($filters['search'] ?? '');
        $tab = $filters['tab'] ?? 'requests';
        $tabs = self::TABS;
        $tabCounts = collect($tabs)->map(fn ($item, $key) => Booking::inBookingTab($key)->count());
        $bookings = Booking::with(['user', 'studio', 'payment', 'settlement'])
            ->inBookingTab($tab)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $query->where('booking_code', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('studio', fn ($studio) => $studio->where('name', 'like', $term));
                });
            })
            ->when($tab === 'history' ? ($filters['status'] ?? null) : null, fn ($query, $status) => $query->where('status', $status))
            ->when($tab === 'requests' ? ($filters['payment_type'] ?? null) : null, fn ($query, $type) => $query->whereHas('payment', fn ($p) => $p->where('payment_type', $type)))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('date', $date))
            ->when($tab === 'active', fn ($query) => $query
                ->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM payments WHERE payments.booking_id = bookings.id AND payments.payment_stage = 'settlement' AND payments.verification_status = 'pending') THEN 0 ELSE 1 END")
                ->orderBy('date')->orderBy('start_time'))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'filters', 'tab', 'tabs', 'tabCounts'));
    }

    public function destroy(Request $request, Booking $booking)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $proofs = DB::transaction(function () use ($booking) {
            $booking->studio()->lockForUpdate()->firstOrFail();
            $record = Booking::lockForUpdate()->findOrFail($booking->id);
            $proofs = $record->payments()->pluck('payment_proof')->filter()->all();
            $record->delete();
            return $proofs;
        });
        foreach ($proofs as $proof) {
            if (!str_contains($proof, '..') && preg_match('#^(booking-payment-proofs|payment-proofs|class-payment-proofs|settlement-proofs)/#', $proof)) {
                Storage::disk('local')->delete($proof);
            }
        }
        return to_route('admin.bookings.index')->with('success', 'Booking studio berhasil dihapus.');
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
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'booking_id' => ['nullable', 'integer', 'exists:bookings,id']]);
        $exclude = null;
        if (!empty($data['booking_id'])) {
            $record = Booking::findOrFail($data['booking_id']);
            abort_unless($record->status === 'confirmed' && $record->payment_status === 'paid', 403);
            $exclude = $record->id;
        }
        return response()->json([
            'days' => app(BookingAvailability::class)->month($studio, $data['month'], $exclude),
            'hours' => app(\App\Services\BookingHours::class)->current(),
            'step' => config('booking.step_minutes'), 'minimum' => config('booking.minimum_minutes'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['payment_status' => 'paid']);
        return $this->storeBooking($request);
    }

    protected function storeBooking(Request $request, bool $customer = false): RedirectResponse
    {
        if ($customer) {
            abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
            $request->merge(['user_id' => $request->user()->id, 'payment_status' => 'unpaid', 'booking_name' => $request->user()->name]);
        }
        $usingMembership = $request->input('payment_method') === 'membership';
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')],
            'studio_id' => ['required', 'integer', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'end_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:start_time'],
            'purpose' => ['nullable', Rule::in(Booking::PURPOSES)],
            'payment_proof' => [$customer && !$usingMembership ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'payment_status' => ['required', Rule::in(array_keys(Booking::PAYMENT_STATUSES))],
            'payment_method' => ['nullable', Rule::in(array_keys(Booking::PAYMENT_METHODS))],
            'payment_type' => ['nullable', Rule::in(array_keys(Booking::PAYMENT_TYPES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($customer) {
            $details = $request->validate([
                'payment_method' => ['required', Rule::in(array_merge(array_keys(Booking::ONLINE_PAYMENT_METHODS), ['membership']))],
                'booking_name' => ['required', 'string', 'max:255'],
                'purpose' => ['required', Rule::in(Booking::PURPOSES)],
            ]);
            $data = array_merge($data, $details);
        }
        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);
        if ($start->diffInMinutes($end) < config('booking.minimum_minutes')) {
            throw ValidationException::withMessages(['end_time' => 'Minimal durasi penyewaan studio adalah '.config('booking.minimum_minutes').' menit.']);
        }
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
            $membership = null;
            $membershipMinutes = (int) $start->diffInMinutes($end);
            if (($data['payment_method'] ?? null) === 'membership') {
                $membership = \App\Models\Membership::where('user_id', $data['user_id'])->validOn($data['date'])->where('quota_minutes', '>', 0)
                    ->where(fn ($query) => $query->whereNull('studio_id')->orWhere('studio_id', $studio->id))->lockForUpdate()->first();
                if (!$membership || $membership->remaining_minutes < $membershipMinutes) throw ValidationException::withMessages(['payment_method' => 'Membership tidak berlaku untuk studio/tanggal ini atau sisa kuota tidak cukup.']);
                $data['payment_type'] = 'full';
            }
            $price = round((float) $studio->price_per_hour * $start->diffInMinutes($end) / 60, 2);
            if ($price > 9999999999.99) {
                throw ValidationException::withMessages(['end_time' => 'Total biaya melebihi batas. Kurangi durasi booking.']);
            }
            if ($membership) $price = 0;
            $booking = Booking::create([
                'booking_code' => 'RES-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'user_id' => $data['user_id'], 'studio_id' => $studio->id,
                'date' => $data['date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
                'purpose' => $data['purpose'] ?? null, 'notes' => $data['notes'] ?? null,
                'total_price' => $price, 'status' => $customer && !$membership ? 'pending' : 'confirmed',
                'membership_id' => $membership?->id, 'membership_minutes' => $membership ? $membershipMinutes : 0,
                'booking_name' => $data['booking_name'] ?? null,
            ]);
            $booking->payment()->create([
                'payment_stage' => 'initial', 'amount' => round($price * (($data['payment_type'] ?? 'full') === 'dp' ? 0.5 : 1), 2),
                'payment_proof' => $proof, 'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_type' => $data['payment_type'] ?? 'full', 'verification_status' => $customer && !$membership ? 'pending' : 'verified',
                'verified_by' => $customer ? null : $request->user()->id,
                'verified_by_name' => $membership ? 'Otomatis - Benefit Membership' : ($customer ? null : $request->user()->name), 'verified_at' => $customer && !$membership ? null : now(),
            ]);
            $booking->unsetRelation('payment');
            $this->recordActivity($booking, 'created', []);
        });

        if ($customer) {
            return to_route('user.history')->with('success', 'Reservasi berhasil diajukan. Tunggu konfirmasi dari admin.');
        }

        return to_route('admin.bookings.index', ['tab' => 'active'])->with('success', 'Booking berhasil dibuat dan langsung dikonfirmasi.');
    }

    public function decide(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000', 'regex:/\S/u'],
        ]);
        DB::transaction(function () use ($booking, $data, $request) {
            Studio::whereKey($booking->studio_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($booking->status !== 'pending' && !($booking->status === 'confirmed' && $booking->payment_status === 'unpaid')) {
                throw ValidationException::withMessages(['decision' => 'Pengajuan ini sudah diproses. Muat ulang daftar booking.']);
            }
            $accept = $data['decision'] === 'accept';
            if ($accept && app(\App\Services\StudioConflicts::class)->exists($booking->studio, $booking->only(['date', 'start_time', 'end_time']), null, $booking->id)) {
                throw ValidationException::withMessages(['decision' => 'Jadwal booking bertabrakan dengan jadwal lain.']);
            }
            $before = $this->activityAttributes($booking);
            if ($booking->payment && $booking->payment_status !== 'paid') {
                $booking->payment->update(['verification_status' => $accept ? 'verified' : 'rejected',
                    'verified_by' => $request->user()->id, 'verified_by_name' => $request->user()->name, 'verified_at' => now(),
                    'rejection_reason' => $accept ? null : trim($data['rejection_reason'])]);
            }
            $booking->update(['status' => $accept ? 'confirmed' : 'cancelled',
                'rejection_reason' => $accept ? null : trim($data['rejection_reason'])]);
            $this->recordActivity($booking, $accept ? 'accepted' : 'rejected', $before);
        });

        app(\App\Services\BookingCompletion::class)->run($booking->id);
        return to_route('admin.bookings.index')->with('success', $data['decision'] === 'accept'
            ? ($booking->fresh()->status === 'completed' ? 'Pengajuan terverifikasi dan masuk Riwayat karena waktu penyewaan sudah berakhir.' : 'Pengajuan diterima dan masuk ke Booking Aktif.')
            : 'Pengajuan ditolak dan dipindahkan ke Riwayat.');
    }

    public function settle(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(Booking::PAYMENT_METHODS))],
            'payment_proof' => [Rule::requiredIf($request->input('payment_method') !== 'cash'), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);
        $path = $request->file('payment_proof')?->store('settlement-proofs', 'local');
        $oldPath = null;
        try {
            DB::transaction(function () use ($booking, $request, $data, $path, &$oldPath) {
                $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                $summary = app(\App\Services\PaymentSummary::class)->for($booking);
                if (!$summary['canSettle']) {
                    throw ValidationException::withMessages(['payment_proof' => 'Pelunasan hanya dapat dicatat untuk booking aktif dengan DP terverifikasi dan tanpa pengajuan pelunasan yang sedang diproses.']);
                }
                $oldPath = null;
                $booking->settlements()->create([
                    'amount' => $summary['remaining'], 'payment_method' => $data['payment_method'],
                    'payment_proof' => $path ?? '', 'verification_status' => 'verified', 'payment_stage' => 'settlement',
                    'verified_by' => $request->user()->id, 'verified_by_name' => $request->user()->name, 'verified_at' => now(),
                ]);
                $before = $this->activityAttributes($booking);
                app(\App\Services\BookingCompletion::class)->run($booking->id);
                $booking->refresh();
                $this->recordActivity($booking, 'updated', $before);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return to_route('admin.bookings.index', ['tab' => $booking->fresh()->status === 'completed' ? 'history' : 'active'])->with('success', 'Pelunasan terverifikasi oleh '.$request->user()->name.'. Booking selesai setelah waktu penyewaan berakhir.');
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'studio']);
        $activities = auth()->user()->role === 'super_admin'
            ? BookingActivity::where('booking_id', $booking->id)->latest('id')->paginate(10)
            : null;

        return view('admin.bookings.edit', ['booking' => $booking, 'activities' => $activities, 'readOnly' => true]);
    }

    public function edit(Booking $booking): View
    {
        abort_unless($booking->status === 'confirmed' && $booking->payment_status === 'paid', 403, 'Hanya booking aktif yang dapat diedit.');
        $booking->load(['user', 'studio']);

        $activities = auth()->user()->role === 'super_admin'
            ? BookingActivity::where('booking_id', $booking->id)->latest('id')->paginate(10)
            : null;

        return view('admin.bookings.edit', ['booking' => $booking, 'activities' => $activities, 'studios' => Studio::where('status', 'active')->orderBy('name')->get()]);
    }

    public function settlementForm(Booking $booking): View
    {
        abort_unless($booking->status === 'confirmed' && $booking->payment_status === 'paid' && $booking->payment_type === 'dp', 403);
        $booking->load(['user', 'studio', 'payment', 'settlement']);
        return view('admin.bookings.edit', ['booking' => $booking, 'activities' => null, 'paymentOnly' => true]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($booking) {
            Studio::whereKey($booking->studio_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'confirmed' && $booking->payment_status === 'paid', 403);
            $before = $this->activityAttributes($booking);
            $booking->update(['status' => 'cancelled']);
            $this->recordActivity($booking, 'updated', $before);
        });
        return to_route('admin.bookings.index', ['tab' => 'history'])->with('success', 'Booking dibatalkan. Pembayaran terverifikasi tetap tercatat.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === 'confirmed' && $booking->payment_status === 'paid', 403);
        $data = $request->validate([
            'studio_id' => ['required', 'integer', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'end_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:start_time'],
            'payment_status' => ['prohibited'], 'payment_type' => ['prohibited'], 'payment_method' => ['prohibited'],
            'payment_proof' => ['prohibited'], 'status' => ['prohibited'], 'notes' => ['prohibited'],
        ]);
        DB::transaction(function () use ($booking, $data) {
            // Lock studios in a stable order, as new bookings also lock their studio.
            $oldStudioId = $booking->studio_id;
            Studio::whereIn('id', [$oldStudioId, $data['studio_id']])->orderBy('id')->lockForUpdate()->get();
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'confirmed' && $booking->payment_status === 'paid', 403);
            $studio = Studio::whereKey($data['studio_id'])->where('status', 'active')->firstOrFail();
            $days = app(BookingAvailability::class)->month($studio, substr($data['date'], 0, 7), $booking->id);
            if (!in_array($data['end_time'], $days[$data['date']][$data['start_time']] ?? [], true)) {
                throw ValidationException::withMessages(['start_time' => 'Jadwal terisi, sudah lewat, atau durasi kurang dari satu jam. Pilih jadwal lain.']);
            }
            if ($booking->membership_id) {
                $membership = \App\Models\Membership::whereKey($booking->membership_id)->validOn($data['date'])->lockForUpdate()->first();
                $minutes = (int) Carbon::parse($data['date'].' '.$data['start_time'])->diffInMinutes(Carbon::parse($data['date'].' '.$data['end_time']));
                if (!$membership || ($membership->studio_id && $membership->studio_id != $studio->id) || $membership->remaining_minutes + $booking->membership_minutes < $minutes) throw ValidationException::withMessages(['date' => 'Studio, tanggal, atau durasi tidak sesuai benefit dan sisa kuota membership.']);
                $data['membership_minutes'] = $minutes;
            }
            $oldSchedule = $booking->only(['studio_id', 'date', 'start_time', 'end_time']);
            $booking->update($data);
            // Keep verified payment amounts and the agreed total unchanged.
            $booking->touch();
            BookingActivity::create(['booking_id' => $booking->id, 'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name, 'actor_email' => auth()->user()->email, 'actor_role' => auth()->user()->role,
                'action' => 'updated', 'changes' => ['schedule' => ['before' => 'Studio #'.$oldSchedule['studio_id'].' '.$oldSchedule['date'].' '.$oldSchedule['start_time'].' - '.$oldSchedule['end_time'], 'after' => $studio->name.' '.$data['date'].' '.$data['start_time'].' - '.$data['end_time']]]]);
        });
        return to_route('admin.bookings.index', ['tab' => 'active'])->with('success', 'Studio dan jadwal booking diperbarui.');
    }

    public function paymentProof(Booking $booking)
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

    private function activityAttributes(Booking $booking): array
    {
        return $booking->getAttributes() + collect(['payment_status', 'payment_method', 'payment_type', 'payment_proof', 'verified_by', 'verified_by_name', 'verified_at'])
            ->mapWithKeys(fn ($field) => [$field => $booking->{$field}])->all();
    }

    private function recordActivity(Booking $booking, string $action, array $before): void
    {
        $changes = [];
        foreach (['user_id', 'studio_id', 'purpose', 'total_price', 'status', 'payment_status', 'payment_method', 'payment_type', 'verified_by', 'verified_by_name', 'verified_at', 'notes', 'payment_proof', 'rejection_reason'] as $field) {
            $old = $before[$field] ?? null;
            $new = $this->activityAttributes($booking)[$field] ?? null;
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
            'booking_id' => $booking->id, 'actor_id' => $actor->id,
            'actor_name' => $actor->name, 'actor_email' => $actor->email, 'actor_role' => $actor->role,
            'action' => $action, 'changes' => $changes,
        ]);
    }
}
