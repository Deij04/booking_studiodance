@php($paymentSummary = app(\App\Services\PaymentSummary::class)->for($paymentRecord))
<section id="pelunasan" class="scroll-mt-6 mt-6 rounded-2xl border border-white/10 bg-[#171717] p-6">
    <h3 class="text-lg font-semibold">DP &amp; Pelunasan</h3>
    <p class="mt-3 text-sm">Status pembayaran: {{ $paymentSummary['label'] }}</p>
    <p class="mt-2 text-sm text-white/60">Terverifikasi: Rp {{ number_format($paymentSummary['paid'], 2, ',', '.') }} &middot; Sisa: Rp {{ number_format($paymentSummary['remaining'], 2, ',', '.') }}</p>
    @if($paymentSummary['settlement'])
        @php($settlement = $paymentSummary['settlement'])
        <p class="mt-4 text-sm">Pelunasan Rp {{ number_format($settlement->amount, 2, ',', '.') }} &middot; {{ \App\Models\Booking::PAYMENT_METHODS[$settlement->payment_method] ?? '-' }}</p>
        <p class="mt-2 text-sm">{{ \App\Models\Payment::STATUSES[$settlement->status] }} &middot; Diproses oleh {{ $settlement->verified_by_name ?: '-' }}</p>
        @if($settlement->payment_proof)
        <a class="mt-3 inline-block text-sm text-[#f2c83f] underline" target="_blank" rel="noopener" href="{{ route('admin.settlements.proof', $settlement) }}">Lihat bukti pelunasan</a>
        @else
            <p class="mt-3 text-sm text-white/50">Pelunasan tunai dicatat oleh admin.</p>
        @endif
        @if($settlement->status === 'pending' && !($readOnly ?? false))
            <div class="mt-4 flex gap-3">@foreach(['verified' => 'Verifikasi Pelunasan', 'rejected' => 'Tolak Bukti'] as $decision => $label)<form method="POST" action="{{ route('admin.settlements.decide', $settlement) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="{{ $decision }}"><button class="rounded-lg border border-[#f2c83f]/30 px-3 py-2 text-sm text-[#f2c83f]">{{ $label }}</button></form>@endforeach</div>
        @endif
    @elseif($paymentSummary['dp'])
        <p class="mt-3 text-sm text-white/50">{{ $paymentSummary['verified'] ? 'Pelunasan belum diajukan. Pelanggan dapat mengunggah bukti pelunasan dari detail reservasi di history.' : 'Verifikasi bukti DP terlebih dahulu melalui form di atas. Setelah itu pelanggan dapat mengajukan pelunasan dari history.' }}</p>
    @endif
    @if($paymentRecord instanceof \App\Models\Booking && $paymentSummary['canSettle'] && !($readOnly ?? false))
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.bookings.settle', $paymentRecord) }}" class="mt-4 space-y-4">
            @csrf
            <h4 class="font-semibold">Catat Pelunasan oleh Admin</h4>
            <p class="text-sm text-white/60">Nominal: Rp {{ number_format($paymentSummary['remaining'], 0, ',', '.') }}. Langsung terverifikasi oleh {{ auth()->user()->name }} dan booking tetap aktif sampai waktu penyewaan berakhir. Jika waktunya sudah lewat, booking masuk Riwayat.</p>
            <label class="block text-sm">Metode pembayaran<select name="payment_method" required class="mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3">@foreach(\App\Models\Booking::PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block text-sm">Bukti pelunasan<input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 file:mr-4 file:rounded-lg file:border-0 file:bg-[#f2c83f] file:px-4 file:py-2 file:text-black"></label>
            <p class="text-xs text-white/50">Wajib untuk QRIS dan transfer bank, opsional untuk tunai. Maksimal 2 MB.</p>
            <button data-save-confirm class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Pelunasan</button>
        </form>
    @endif

</section>
