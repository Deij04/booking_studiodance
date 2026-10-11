@extends('layout.dashboard')
@section('title', ($readOnly ?? false) ? 'Detail Booking' : (($paymentOnly ?? false) ? 'Pelunasan Booking' : 'Edit Booking'))
@section('content')
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
        <div><a href="{{ route('admin.bookings.index', ['tab' => match ($booking->status) { 'pending' => 'requests', 'confirmed' => 'active', default => 'history' }]) }}" class="text-sm text-[#f2c83f] hover:underline">Kembali ke daftar booking</a><h2 class="mt-4 text-2xl font-bold">{{ $booking->booking_code }}</h2><p class="mt-2 text-sm text-white/50">Dibuat {{ $booking->created_at->format('d/m/Y H:i') }}</p></div>
        <dl class="grid gap-6 rounded-2xl border border-white/10 bg-[#171717] p-6 sm:grid-cols-2">
            <div><dt class="text-xs text-white/45">Pelanggan</dt><dd class="mt-2">{{ $booking->user->name }}<span class="mt-1 block text-sm text-white/60">{{ $booking->user->email }}</span><span class="mt-1 block text-sm text-white/60">{{ $booking->user->phone ?: '-' }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Studio dan jadwal</dt><dd class="mt-2">{{ $booking->studio->name }}<span class="mt-1 block text-sm text-white/60">{{ \Carbon\Carbon::parse($booking->date)->format('d/m/Y') }} · {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Keperluan</dt><dd class="mt-2 break-words">{{ $booking->purpose ?: '-' }}</dd></div>
            <div><dt class="text-xs text-white/45">Total pembayaran</dt><dd class="mt-2 text-xl font-semibold text-[#f2c83f]">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs text-white/45">Metode pembayaran</dt><dd class="mt-2">{{ \App\Models\Booking::PAYMENT_METHODS[$booking->payment_method] ?? '-' }}</dd></div>
            <div><dt class="text-xs text-white/45">Verifikasi Pengajuan Booking</dt><dd class="mt-2">{{ \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] }}</dd></div>
            <div><dt class="text-xs text-white/45">Diverifikasi oleh (Pengajuan Booking)</dt><dd class="mt-2">{{ $booking->verified_by_name ?: '-' }}@if($booking->verified_at)<span class="mt-1 block text-xs text-white/50">{{ $booking->verified_at->format('d/m/Y H:i') }}</span>@endif</dd></div>
            <div><dt class="text-xs text-white/45">Nominal pembayaran {{ $booking->payment_type === 'dp' ? 'DP (50%)' : 'Lunas' }}</dt><dd class="mt-2">Rp {{ number_format($booking->payment_amount, 2, ',', '.') }}</dd></div>
            <div><dt class="text-xs text-white/45">Sisa pelunasan setelah pembayaran ini</dt><dd class="mt-2">Rp {{ number_format($booking->remaining_amount, 2, ',', '.') }}</dd></div>
            @if($booking->payment_type === 'dp')
                <div><dt class="text-xs text-white/45">Verifikasi Pelunasan</dt><dd class="mt-2">{{ $booking->settlement ? \App\Models\Payment::STATUSES[$booking->settlement->status] : 'Belum diajukan' }}</dd></div>
                <div><dt class="text-xs text-white/45">Diverifikasi oleh (Pelunasan)</dt><dd class="mt-2">{{ $booking->settlement?->verified_by_name ?: '-' }}@if($booking->settlement?->verified_at)<span class="mt-1 block text-xs text-white/50">{{ $booking->settlement->verified_at->format('d/m/Y H:i') }}</span>@endif</dd></div>
            @endif
            <div><dt class="text-xs text-white/45">Status penyewaan</dt><dd class="mt-2">{{ \App\Models\Booking::STATUSES[$booking->status] }}</dd></div>
            @if($booking->notes)<div class="sm:col-span-2"><dt class="text-xs text-white/45">Catatan</dt><dd class="mt-2 whitespace-pre-wrap break-words">{{ $booking->notes }}</dd></div>@endif
        </dl>
        @if($booking->rejection_reason)
            <div class="rounded-xl border border-red-400/20 bg-red-400/10 p-4"><h3 class="text-sm font-semibold text-red-300">Alasan Penolakan</h3><p class="mt-2 whitespace-pre-wrap break-words text-sm text-white/70">{{ $booking->rejection_reason }}</p></div>
        @endif
        @if($readOnly ?? false)
            @include('admin.bookings.proof-preview', ['proofPath' => $booking->payment_proof, 'proofUrl' => route('admin.bookings.payment-proof', $booking)])
            @if($booking->settlement)
                
                @include('admin.bookings.proof-preview', ['proofPath' => $booking->settlement->payment_proof, 'proofUrl' => route('admin.settlements.proof', $booking->settlement), 'proofTitle' => 'Bukti Pelunasan'])
            @endif
        @elseif($paymentOnly ?? false)
            <h2 class="text-xl font-semibold">Pelunasan Booking</h2>
            @if(session('success'))<p class="text-sm text-emerald-300">{{ session('success') }}</p>@endif
            @if($errors->any())<div role="alert" class="rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @include('admin.bookings.settlement', ['paymentRecord' => $booking])
        @else
            <form data-booking-calendar data-booking-id="{{ $booking->id }}" data-availability-url="{{ url('admin/booking-availability') }}" data-today="{{ today()->toDateString() }}" method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold">Edit Studio dan Jadwal</h2>
                @if($errors->any())<div role="alert" class="mt-4 rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                @php
                    $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm outline-none focus:border-[#f2c83f]';
                @endphp
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div><label for="studio_id" class="text-sm">Studio</label><select id="studio_id" name="studio_id" required class="{{ $inputClass }}">@foreach($studios as $studio)<option value="{{ $studio->id }}" @selected(old('studio_id', $booking->studio_id) == $studio->id)>{{ $studio->name }}</option>@endforeach</select></div>
                    <div><label for="date" class="text-sm">Tanggal penggunaan (WITA)</label><input id="date" name="date" value="{{ old('date', $booking->date) }}" required class="{{ $inputClass }}"></div>
                    <div><label for="start_time" class="text-sm">Jam mulai</label><select id="start_time" name="start_time" data-old="{{ old('start_time', substr($booking->start_time, 0, 5)) }}" required class="{{ $inputClass }}"></select></div>
                    <div><label for="end_time" class="text-sm">Jam selesai</label><select id="end_time" name="end_time" data-old="{{ old('end_time', substr($booking->end_time, 0, 5)) }}" required class="{{ $inputClass }}"></select></div>
                    <div class="sm:col-span-2"><p id="availability-status" role="status" class="text-sm text-white/60"></p><button id="availability-retry" type="button" hidden class="mt-2 text-sm text-[#f2c83f]">Coba lagi</button></div>
                </div>
                <p class="mt-4 text-xs text-white/50">Minimal 1 jam. Total biaya yang disepakati dan pembayaran terverifikasi tetap tercatat.</p>
                <button data-save-confirm type="submit" class="mt-5 rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Jadwal</button>
            </form>
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" data-booking-cancel class="rounded-2xl border border-red-400/20 bg-[#171717] p-6">
                @csrf
                @method('PATCH')
                <h3 class="font-semibold">Batalkan Booking</h3>
                <p class="mt-2 text-sm text-white/50">Booking masuk Riwayat dan jadwal dibuka kembali. Pembayaran terverifikasi tetap tercatat sebagai pendapatan.</p>
                <button type="submit" class="mt-4 rounded-lg bg-red-400/10 px-4 py-2 text-sm font-semibold text-red-300">Batalkan Booking</button>
            </form>
        @endif
        @if(auth()->user()->role === 'super_admin' && $activities)
            <section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
                <h3 class="text-lg font-semibold">Riwayat Aktivitas Booking</h3>
                <p class="mt-2 text-xs text-white/45">Hanya terlihat oleh super admin. Aktivitas dicatat sejak fitur ini diaktifkan.</p>
                @php
                    $activityLabels = ['created' => 'Membuat booking (langsung dikonfirmasi)', 'accepted' => 'Menerima booking', 'rejected' => 'Menolak booking', 'updated' => 'Mengedit booking'];
                    $fieldLabels = ['user_id' => 'ID pelanggan', 'studio_id' => 'Studio', 'date' => 'Tanggal', 'start_time' => 'Jam mulai', 'end_time' => 'Jam selesai', 'purpose' => 'Keperluan', 'total_price' => 'Total biaya', 'status' => 'Status booking', 'payment_status' => 'Pembayaran', 'notes' => 'Catatan', 'payment_proof' => 'Bukti transfer'];
                    $formatValue = fn ($field, $value) => match ($field) {
                        'status' => \App\Models\Booking::STATUSES[$value] ?? '-',
                        'payment_status' => \App\Models\Booking::PAYMENT_STATUSES[$value] ?? '-',
                        'total_price' => $value !== null ? 'Rp '.number_format((float) $value, 0, ',', '.') : '-',
                        default => $value === null || $value === '' ? '-' : $value,
                    };
                @endphp
                <div class="mt-5 space-y-4">
                    @forelse($activities as $activity)
                        <article class="rounded-xl border border-white/10 p-4">
                            <p class="text-sm font-semibold text-[#f2c83f]">{{ $activityLabels[$activity->action] ?? $activity->action }}</p>
                            <p class="mt-2 text-sm">{{ $activity->actor_name }} <span class="text-xs text-white/45">({{ str_replace('_', ' ', $activity->actor_role) }})</span></p>
                            <p class="mt-1 text-xs text-white/45">{{ $activity->actor_email }} &middot; {{ $activity->created_at->format('d/m/Y H:i:s') }}</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                @foreach($activity->changes as $field => $change)
                                    <div class="break-words"><dt class="text-xs text-white/45">{{ $fieldLabels[$field] ?? $field }}</dt><dd class="mt-1 whitespace-pre-wrap">{{ $formatValue($field, $change['before']) }} &rarr; {{ $formatValue($field, $change['after']) }}</dd></div>
                                @endforeach
                            </dl>
                        </article>
                    @empty
                        <p class="text-sm text-white/45">Belum ada aktivitas yang tercatat untuk booking ini.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $activities->links() }}</div>
            </section>
        @endif
    </div>
@endsection
