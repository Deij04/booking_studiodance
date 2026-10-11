@php($paymentSummary = app(\App\Services\ReservationPaymentSummary::class)->for($paymentRecord))
<section id="pelunasan" class="scroll-mt-6 mt-6 rounded-2xl border border-white/10 bg-[#171717] p-6">
    <h3 class="text-lg font-semibold">DP &amp; Pelunasan</h3>
    <p class="mt-3 text-sm">Status pembayaran: {{ $paymentSummary['label'] }}</p>
    <p class="mt-2 text-sm text-white/60">Terverifikasi: Rp {{ number_format($paymentSummary['paid'], 2, ',', '.') }} &middot; Sisa: Rp {{ number_format($paymentSummary['remaining'], 2, ',', '.') }}</p>
    @if($paymentSummary['settlement'])
        @php($settlement = $paymentSummary['settlement'])
        <p class="mt-4 text-sm">Pelunasan Rp {{ number_format($settlement->amount, 2, ',', '.') }} &middot; {{ \App\Models\Reservation::PAYMENT_METHODS[$settlement->payment_method] ?? '-' }}</p>
        <p class="mt-2 text-sm">{{ \App\Models\ReservationSettlement::STATUSES[$settlement->status] }} &middot; Diproses oleh {{ $settlement->verified_by_name ?: '-' }}</p>
        <a class="mt-3 inline-block text-sm text-[#f2c83f] underline" target="_blank" rel="noopener" href="{{ route('admin.settlements.proof', $settlement) }}">Lihat bukti pelunasan</a>
        @if($settlement->status === 'pending' && !($readOnly ?? false))
            <div class="mt-4 flex gap-3">@foreach(['verified' => 'Verifikasi Pelunasan', 'rejected' => 'Tolak Bukti'] as $decision => $label)<form method="POST" action="{{ route('admin.settlements.decide', $settlement) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="{{ $decision }}"><button class="rounded-lg border border-[#f2c83f]/30 px-3 py-2 text-sm text-[#f2c83f]">{{ $label }}</button></form>@endforeach</div>
        @endif
    @elseif($paymentSummary['dp'])
        <p class="mt-3 text-sm text-white/50">{{ $paymentSummary['verified'] ? 'Pelunasan belum diajukan. Pelanggan dapat mengunggah bukti pelunasan dari detail reservasi di history.' : 'Verifikasi bukti DP terlebih dahulu melalui form di atas. Setelah itu pelanggan dapat mengajukan pelunasan dari history.' }}</p>
    @endif
</section>
