<section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
    <h3 class="mb-4 text-base font-semibold">{{ $proofTitle ?? 'Bukti Pembayaran' }}</h3>
    @if($proofPath)
        @if(strtolower(pathinfo($proofPath, PATHINFO_EXTENSION)) === 'pdf')
            <iframe src="{{ $proofUrl }}" title="{{ $proofTitle ?? 'Bukti Pembayaran' }}" style="display:block;width:100%;height:560px;border:1px solid #333;border-radius:12px;background:#fff"></iframe>
        @else
            <img src="{{ $proofUrl }}" alt="{{ $proofTitle ?? 'Bukti Pembayaran' }}" style="display:block;max-width:100%;max-height:560px;width:auto;height:auto;object-fit:contain;margin:0 auto;border-radius:12px">
        @endif
    @else
        <p class="text-sm text-white/50">Belum ada bukti pembayaran.</p>
    @endif
</section>
