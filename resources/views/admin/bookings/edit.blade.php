@extends('layout.dashboard')
@section('title', 'Detail Booking')
@section('content')
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
        <div><a href="{{ route('admin.bookings.index', ['tab' => match ($booking->status) { 'pending' => 'requests', 'confirmed' => 'active', default => 'history' }]) }}" class="text-sm text-[#f2c83f] hover:underline">Kembali ke daftar booking</a><h2 class="mt-4 text-2xl font-bold">{{ $booking->booking_code }}</h2><p class="mt-2 text-sm text-white/50">Dibuat {{ $booking->created_at->format('d/m/Y H:i') }}</p></div>
        <dl class="grid gap-6 rounded-2xl border border-white/10 bg-[#171717] p-6 sm:grid-cols-2">
            <div><dt class="text-xs text-white/45">Pelanggan</dt><dd class="mt-2">{{ $booking->user->name }}<span class="mt-1 block text-sm text-white/60">{{ $booking->user->email }}</span><span class="mt-1 block text-sm text-white/60">{{ $booking->user->phone ?: '-' }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Studio dan jadwal</dt><dd class="mt-2">{{ $booking->studioSchedule->studio->name }}<span class="mt-1 block text-sm text-white/60">{{ \Carbon\Carbon::parse($booking->studioSchedule->date)->format('d/m/Y') }} · {{ substr($booking->studioSchedule->start_time, 0, 5) }}–{{ substr($booking->studioSchedule->end_time, 0, 5) }}</span></dd></div>
            <div><dt class="text-xs text-white/45">Keperluan</dt><dd class="mt-2 break-words">{{ $booking->purpose ?: '-' }}</dd></div>
            <div><dt class="text-xs text-white/45">Total pembayaran</dt><dd class="mt-2 text-xl font-semibold text-[#f2c83f]">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-6">
            @csrf
            @method('PUT')
            @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @php($inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm outline-none focus:border-[#f2c83f]')
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="status" class="text-sm text-white/70">Status booking</label><select id="status" name="status" required class="{{ $inputClass }}">@foreach(\App\Models\Reservation::STATUSES as $value => $label) @if((!in_array($booking->status, ['cancelled', 'completed']) || $value === $booking->status) && !($booking->status === 'confirmed' && $value === 'pending'))<option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>@endif @endforeach</select><p class="mt-2 text-xs text-white/45">Booking yang dibatalkan atau selesai tidak dapat diaktifkan kembali.</p></div>
                <div><label for="payment_status" class="text-sm text-white/70">Status pembayaran</label><select id="payment_status" name="payment_status" required class="{{ $inputClass }}">@foreach(\App\Models\Reservation::PAYMENT_STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', $booking->payment_status) === $value)>{{ $label }}</option>@endforeach</select><p class="mt-2 text-xs text-white/45">Pembayaran harus lunas untuk menyelesaikan booking.</p></div>
                <div class="sm:col-span-2"><label for="notes" class="text-sm text-white/70">Catatan</label><textarea id="notes" name="notes" rows="4" maxlength="5000" class="{{ $inputClass }}">{{ old('notes', $booking->notes) }}</textarea></div>
            </div>
            <div class="mt-6 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.bookings.index', ['tab' => match ($booking->status) { 'pending' => 'requests', 'confirmed' => 'active', default => 'history' }]) }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">Simpan Perubahan</button></div>
        </form>
    </div>
@endsection
