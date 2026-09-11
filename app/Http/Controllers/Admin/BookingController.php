<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
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
        'history' => ['label' => 'Riwayat', 'statuses' => ['completed', 'cancelled']],
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(array_keys(self::TABS))],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Reservation::STATUSES))],
            'payment_status' => ['nullable', Rule::in(array_keys(Reservation::PAYMENT_STATUSES))],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $filters['search'] = trim($filters['search'] ?? '');
        $tab = $filters['tab'] ?? 'requests';
        $tabs = self::TABS;
        $counts = Reservation::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $tabCounts = collect($tabs)->map(fn ($item) => collect($item['statuses'])->sum(fn ($status) => $counts[$status] ?? 0));
        $bookings = Reservation::with(['user', 'studioSchedule.studio'])
            ->whereIn('status', $tabs[$tab]['statuses'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $query->where('booking_code', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('studioSchedule.studio', fn ($studio) => $studio->where('name', 'like', $term));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereHas('studioSchedule', fn ($schedule) => $schedule->whereDate('date', $date)))
            ->when($tab === 'active', fn ($query) => $query
                ->orderBy(StudioSchedule::select('date')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id'))
                ->orderBy(StudioSchedule::select('start_time')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id')))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'filters', 'tab', 'tabs', 'tabCounts'));
    }

    public function create(): View
    {
        return view('admin.bookings.create', [
            'customers' => User::where('role', 'customer')->where('status', 'active')->orderBy('name')->get(),
            'studios' => Studio::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')],
            'studio_id' => ['required', 'integer', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'payment_status' => ['required', Rule::in(array_keys(Reservation::PAYMENT_STATUSES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);
        if ($start->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Jam mulai booking harus di masa mendatang.']);
        }
        $this->saveWithProof($request, function ($proof) use ($data, $start, $end) {
            $studio = Studio::whereKey($data['studio_id'])->where('status', 'active')->lockForUpdate()->first();
            if (! $studio) {
                throw ValidationException::withMessages(['studio_id' => 'Studio sudah tidak tersedia.']);
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
            Reservation::create([
                'booking_code' => 'RES-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'user_id' => $data['user_id'], 'studio_schedule_id' => $schedule->id,
                'purpose' => $data['purpose'] ?? null, 'notes' => $data['notes'] ?? null,
                'total_price' => $price, 'payment_status' => $data['payment_status'], 'status' => 'confirmed',
                'payment_proof' => $proof,
            ]);
        });

        return to_route('admin.bookings.index', ['tab' => 'active'])->with('success', 'Booking berhasil dibuat dan langsung dikonfirmasi.');
    }

    public function decide(Request $request, Reservation $booking): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['accept', 'reject'])]]);
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
            $booking->update(['status' => $accept ? 'confirmed' : 'cancelled']);
            if ($schedule->status !== 'blocked') {
                $schedule->update(['status' => $accept ? 'booked' : 'available']);
            }
        });

        return to_route('admin.bookings.index')->with('success', $data['decision'] === 'accept'
            ? 'Pengajuan diterima dan masuk ke Booking Aktif.'
            : 'Pengajuan ditolak dan dipindahkan ke Riwayat.');
    }

    public function edit(Reservation $booking): View
    {
        $booking->load(['user', 'studioSchedule.studio']);

        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Reservation $booking): RedirectResponse
    {
        $data = $request->validate([
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'status' => ['required', Rule::in(array_keys(Reservation::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Reservation::PAYMENT_STATUSES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        unset($data['payment_proof']);
        $this->saveWithProof($request, function ($proof) use ($booking, $data) {
            $booking = Reservation::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $schedule = StudioSchedule::whereKey($booking->studio_schedule_id)->lockForUpdate()->firstOrFail();
            if ($booking->status === 'confirmed' && $data['status'] === 'pending') {
                throw ValidationException::withMessages(['status' => 'Booking yang sudah diterima tidak dapat dikembalikan ke pengajuan.']);
            }
            if (in_array($booking->status, ['cancelled', 'completed'], true) && $data['status'] !== $booking->status) {
                throw ValidationException::withMessages(['status' => 'Booking yang dibatalkan atau selesai tidak dapat diaktifkan kembali.']);
            }
            if ($data['status'] === 'completed' && $data['payment_status'] !== 'paid') {
                throw ValidationException::withMessages(['payment_status' => 'Pembayaran harus lunas sebelum booking diselesaikan.']);
            }
            if ($data['status'] !== 'cancelled' && $schedule->status === 'blocked') {
                throw ValidationException::withMessages(['status' => 'Jadwal studio sedang diblokir.']);
            }
            $oldProof = $booking->payment_proof;
            if ($proof) {
                $data['payment_proof'] = $proof;
            }
            $booking->update($data);
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
}
