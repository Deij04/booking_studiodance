@extends('layout.dashboard')
@section('title', ($readOnly ?? false) ? 'Detail Booking' : 'Edit Booking')
@section('content')
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
        <div><a href="{{ route('admin.bookings.index', ['tab' => match ($booking->status) { 'pending' => 'requests', 'confirmed' => 'active', default => 'history' }]) }}" class="text-sm text-[#f2c83f] hover:underline">Kembali ke daftar booking</a><h2 class="mt-4 text-2xl font-bold">{{ $booking->booking_code }}</h2><p class="mt-2 text-sm text-white/50">Dibuat {{ $booking->created_at->format('d/m/Y H:i') }}</p></div>
        <dl class="grid gap-6 rounded-2xl border border-white/10 bg-[#171717] p-6 sm:grid-cols-2">
            <div><dt class="text-xs text-white/45">Pelanggan</dt><dd class="mt-2">{{ $booking->user->name }}<span class="mt-1 block text-sm text-white/60">{{ $booking->user->email }}</span><span class="mt-1 block text-sm text-white/60">{{ $booking->user->phone ?: '-' }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Studio dan jadwal</dt><dd class="mt-2">{{ $booking->studioSchedule->studio->name }}<span class="mt-1 block text-sm text-white/60">{{ \Carbon\Carbon::parse($booking->studioSchedule->date)->format('d/m/Y') }} · {{ substr($booking->studioSchedule->start_time, 0, 5) }}–{{ substr($booking->studioSchedule->end_time, 0, 5) }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Keperluan</dt><dd class="mt-2 break-words">{{ $booking->purpose ?: '-' }}</dd></div>
            <div><dt class="text-xs text-white/45">Total pembayaran</dt><dd class="mt-2 text-xl font-semibold text-[#f2c83f]">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs text-white/45">Metode pembayaran</dt><dd class="mt-2">{{ \App\Models\Reservation::PAYMENT_METHODS[$booking->payment_method] ?? '-' }}</dd></div>
            <div><dt class="text-xs text-white/45">Status verifikasi</dt><dd class="mt-2">{{ \App\Models\Reservation::PAYMENT_STATUSES[$booking->payment_status] }}</dd></div>
            <div><dt class="text-xs text-white/45">Diverifikasi oleh</dt><dd class="mt-2">{{ $booking->verified_by_name ?: '-' }}@if($booking->verified_at)<span class="mt-1 block text-xs text-white/50">{{ $booking->verified_at->format('d/m/Y H:i') }}</span>@endif</dd></div>
            <div><dt class="text-xs text-white/45">Nominal pembayaran {{ $booking->payment_type === 'dp' ? 'DP (50%)' : 'Lunas' }}</dt><dd class="mt-2">Rp {{ number_format($booking->payment_amount, 2, ',', '.') }}</dd></div>
            <div><dt class="text-xs text-white/45">Sisa pelunasan setelah pembayaran ini</dt><dd class="mt-2">Rp {{ number_format($booking->remaining_amount, 2, ',', '.') }}</dd></div>
            <div><dt class="text-xs text-white/45">Status penyewaan</dt><dd class="mt-2">{{ \App\Models\Reservation::STATUSES[$booking->status] }}</dd></div>
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
        @else
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.bookings.update', $booking) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-6">
            @csrf
            @method('PUT')
            @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @php
                $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm outline-none focus:border-[#f2c83f]';
            @endphp
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="status" class="text-sm text-white/70">Status booking</label><select id="status" name="status" required class="{{ $inputClass }}">@foreach(\App\Models\Reservation::STATUSES as $value => $label) @if((!in_array($booking->status, ['cancelled', 'completed']) || $value === $booking->status) && !($booking->status === 'confirmed' && $value === 'pending'))<option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>@endif @endforeach</select><p class="mt-2 text-xs text-white/45">Booking yang dibatalkan atau selesai tidak dapat diaktifkan kembali.</p></div>
                <div><label for="payment_status" class="text-sm text-white/70">Status verifikasi</label><select id="payment_status" name="payment_status" required class="{{ $inputClass }}">@foreach(\App\Models\Reservation::PAYMENT_STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', $booking->payment_status) === $value)>{{ $label }}</option>@endforeach</select><p class="mt-2 text-xs text-white/45">Verifikasi setelah memeriksa bukti pembayaran.</p></div>
                <div><label for="payment_method" class="text-sm text-white/70">Metode pembayaran</label><select id="payment_method" name="payment_method" class="{{ $inputClass }}"><option value="">Pilih metode pembayaran</option>@foreach(\App\Models\Reservation::PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method', $booking->payment_method ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="payment_type" class="text-sm text-white/70">Jenis pembayaran</label><select id="payment_type" name="payment_type" class="{{ $inputClass }}">@foreach(\App\Models\Reservation::PAYMENT_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('payment_type', $booking->payment_type ?? 'full') === $value)>{{ $label }}</option>@endforeach</select></div>
                @include('admin.bookings.payment-proof')
                @if($booking->status === 'pending')
                    <div class="sm:col-span-2"><label for="rejection_reason" class="text-sm text-white/70">Alasan penolakan</label><textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="1000" class="{{ $inputClass }}">{{ old('rejection_reason') }}</textarea><p class="mt-2 text-xs text-white/45">Wajib diisi jika pengajuan diubah menjadi Dibatalkan.</p></div>
                @endif
                <div class="sm:col-span-2"><label for="notes" class="text-sm text-white/70">Catatan</label><textarea id="notes" name="notes" rows="4" maxlength="5000" class="{{ $inputClass }}">{{ old('notes', $booking->notes) }}</textarea></div>
            </div>
            <div class="mt-6 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.bookings.index', ['tab' => match ($booking->status) { 'pending' => 'requests', 'confirmed' => 'active', default => 'history' }]) }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">Simpan Perubahan</button></div>
        </form>
        @endif
        @if(!($readOnly ?? false))
            @include('admin.bookings.settlement', ['paymentRecord' => $booking])
        @endif
        @if(auth()->user()->role === 'super_admin')
            <section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
                <h3 class="text-lg font-semibold">Riwayat Aktivitas Booking</h3>
                <p class="mt-2 text-xs text-white/45">Hanya terlihat oleh super admin. Aktivitas dicatat sejak fitur ini diaktifkan.</p>
                @php
                    $activityLabels = ['created' => 'Membuat booking (langsung dikonfirmasi)', 'accepted' => 'Menerima booking', 'rejected' => 'Menolak booking', 'updated' => 'Mengedit booking'];
                    $fieldLabels = ['user_id' => 'ID pelanggan', 'studio_schedule_id' => 'ID jadwal', 'purpose' => 'Keperluan', 'total_price' => 'Total biaya', 'status' => 'Status booking', 'payment_status' => 'Pembayaran', 'notes' => 'Catatan', 'payment_proof' => 'Bukti transfer'];
                    $formatValue = fn ($field, $value) => match ($field) {
                        'status' => \App\Models\Reservation::STATUSES[$value] ?? '-',
                        'payment_status' => \App\Models\Reservation::PAYMENT_STATUSES[$value] ?? '-',
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
