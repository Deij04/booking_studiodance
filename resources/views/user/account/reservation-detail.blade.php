@extends('layout.landing')
@section('title', 'Detail Reservasi')
@push('styles')
<style>
.history-detail{max-width:1100px;margin:auto;padding:40px 24px}.history-detail h1{font-size:28px;color:var(--gold);margin:20px 0}.history-detail h2{font-size:19px;margin:0 0 20px}.history-detail p{font-size:14px;line-height:1.7;color:#bbb}.history-detail-card{padding:24px;background:#191817;border:1px solid #343026;border-radius:22px;margin-top:22px}.history-detail dl{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:0}.history-detail dt{font-size:12px;color:#999;margin-bottom:6px}.history-detail dd{font-size:14px;margin:0;color:#e1d5a1}.history-detail .payment-totals{display:flex;flex-wrap:wrap;gap:26px;margin:20px 0}.history-detail .payment-totals strong{display:block;font-size:21px;color:var(--gold);margin-top:6px}.history-detail table{width:100%;border-collapse:collapse;text-align:left;font-size:13px}.history-detail th,.history-detail td{padding:14px 10px;border-bottom:1px solid #333}.history-detail th{font-size:11px;color:#999}.history-payment-table{overflow:auto}.history-detail input,.history-detail select{display:block;width:100%;box-sizing:border-box;padding:12px;background:#121211;color:#eee;border:1px solid #454035;border-radius:8px;font:inherit;font-size:13px;margin:10px 0 18px}.history-detail label{font-size:13px}.history-detail .notice{padding:16px;border:1px solid #827127;border-radius:12px}.history-detail .error{border-color:#a34d4d;color:#f0b0b0}.history-bank{margin:16px 0;background:#121211;padding:18px;border-radius:12px}.history-qris{max-width:200px;margin:16px 0;background:#fff;padding:14px}.history-qris img{width:100%;display:block}.history-detail button{padding:12px 20px;border:0;border-radius:10px;background:var(--gold);font:inherit;font-size:13px;cursor:pointer;color:#111}.history-detail [hidden]{display:none!important}@media(max-width:600px){.history-detail{padding:28px 18px}.history-detail-card{padding:20px}.history-detail dl{grid-template-columns:1fr}.history-detail th,.history-detail td{white-space:nowrap}}
</style>
@endpush
@section('content')
<main class="history-detail">
    <a class="gold" href="{{ route('user.history') }}">&larr; Back to History</a>
    <h1>Detail Reservasi {{ $type === 'studio' ? 'Studio' : 'Class' }}</h1>
    @if(session('success'))<p class="notice" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="history-detail-card"><h2>{{ $title ?: '-' }}</h2><dl>
        @if($type === 'studio')<div><dt>Kode Booking</dt><dd>{{ $record->booking_code }}</dd></div>@endif
        <div><dt>Status {{ $type === 'studio' ? 'Penyewaan' : 'Reservasi Kelas' }}</dt><dd>{{ $type === 'class' ? $record->display_status : (\App\Models\Booking::STATUSES[$summary['rentalStatus']] ?? $summary['rentalStatus']) }}</dd></div>
        <div><dt>Nama</dt><dd>{{ auth()->user()->name }}</dd></div><div><dt>Studio</dt><dd>{{ $schedule?->studio?->name ?: '-' }}</dd></div>
        <div><dt>Tanggal</dt><dd>{{ $schedule?->date ? \Illuminate\Support\Carbon::parse($schedule->date)->format('d M Y') : '-' }}</dd></div><div><dt>Jam</dt><dd>{{ substr($schedule?->start_time ?? '-', 0, 5) }} &ndash; {{ substr($schedule?->end_time ?? '-', 0, 5) }}</dd></div>
        @if($type === 'studio' && $record->purpose)<div><dt>Session Type</dt><dd>{{ $record->purpose }}</dd></div>@endif
    </dl>
        @if($type === 'studio' && $record->rejection_reason)<p>Alasan pembatalan: {{ $record->rejection_reason }}</p>@endif
        @if($summary['initial']?->notes)<p>Catatan: {{ $summary['initial']->notes }}</p>@endif
    </section>
    <section class="history-detail-card"><h2>Pembayaran</h2><p>Status pembayaran: <strong>{{ $summary['label'] }}</strong></p>
        <div class="payment-totals"><span>Total<strong>Rp {{ number_format($summary['total'], 2, ',', '.') }}</strong></span><span>Pembayaran terverifikasi<strong>Rp {{ number_format($summary['paid'], 2, ',', '.') }}</strong></span><span>Sisa pembayaran<strong>Rp {{ number_format($summary['remaining'], 2, ',', '.') }}</strong></span></div>
        <div class="history-payment-table"><table><thead><tr><th>Tahap pembayaran</th><th>Nominal</th><th>Metode</th><th>Status pembayaran</th></tr></thead><tbody>
            <tr><td>{{ $summary['dp'] ? 'DP (50%)' : 'Pembayaran penuh' }}</td><td>Rp {{ number_format($summary['firstAmount'], 2, ',', '.') }}</td><td>{{ \App\Models\Booking::PAYMENT_METHODS[$summary['initial']?->payment_method] ?? '-' }}</td><td>{{ $summary['verified'] ? 'Terverifikasi' : (($type === 'class' && $record->payment?->verification_status === 'rejected') ? 'Belum Terverifikasi' : 'Menunggu Verifikasi') }}</td></tr>
            @if($type === 'class')
                @if(!$summary['settlement'] && ($summary['dp'] || $summary['remaining'] > 0))
                    <tr><td>Pelunasan</td><td>Rp {{ number_format($summary['remaining'], 2, ',', '.') }}</td><td>-</td><td>Belum diajukan</td></tr>
                @endif
                @foreach($record->settlements()->orderBy('id')->get() as $classSettlement)
                    <tr><td>Pelunasan</td><td>Rp {{ number_format($classSettlement->amount, 2, ',', '.') }}</td><td>{{ \App\Models\Booking::PAYMENT_METHODS[$classSettlement->payment_method] ?? '-' }}</td><td>{{ \App\Models\Payment::STATUSES[$classSettlement->status] }}</td></tr>
                @endforeach
            @else
            @if($summary['dp'] || ($type === 'class' && ($summary['remaining'] > 0 || $summary['settlement'])))
                <tr><td>Pelunasan</td><td>Rp {{ number_format($summary['settlement']?->amount ?? $summary['remaining'], 2, ',', '.') }}</td><td>{{ \App\Models\Booking::PAYMENT_METHODS[$summary['settlement']?->payment_method] ?? '-' }}</td><td>{{ $summary['settlement'] ? \App\Models\Payment::STATUSES[$summary['settlement']->status] : 'Belum diajukan' }}</td></tr>
            @endif
            @endif
        </tbody></table></div>
    </section>
    @if($type === 'class' && $record->payment?->verification_status === 'rejected')
        <section class="history-detail-card"><h2>Pengajuan Reservasi Ditolak</h2><p class="notice error">{{ $record->payment->rejection_reason }}</p><p>Silakan mendaftar class lagi dan kirim pengajuan baru.</p><a class="gold" href="{{ route('user.classes.reserve', $schedule->danceClass) }}">Daftar Class Lagi</a></section>
    @endif
    @if($summary['settlement']?->status === 'rejected')
        <p class="notice error" role="alert">Pembayaran Ditolak: bukti pelunasan belum diterima. Pembayaran sebelumnya tetap tercatat; silakan unggah ulang bukti pelunasan yang benar.</p>
    @endif
    @if($summary['canSettle'])
        <section id="bayar-pelunasan" class="history-detail-card" style="scroll-margin-top:100px"><h2>Bayar Pelunasan</h2><p>Nominal pelunasan: <strong>Rp {{ number_format($summary['remaining'], 2, ',', '.') }}</strong></p>
            <form action="{{ route('user.history.settle', ['type' => $type, 'id' => $record->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label for="settlement-method">Metode pembayaran</label><select id="settlement-method" name="payment_method" required>@foreach(\App\Models\Booking::ONLINE_PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select>
                <div class="history-qris" data-settlement-qris><img src="{{ asset(config('booking.qris_image')) }}" alt="QRIS pembayaran">@if(config('booking.qris_image') === 'images/logoorange.png')<small style="color:#555">Logo sementara, QRIS belum tersedia.</small>@endif</div>
                <div class="history-bank" data-settlement-bank hidden><p>Bank: {{ config('booking.bank_name') ?: '-' }}</p><p>Nomor rekening: {{ config('booking.bank_account_number') ?: '-' }}</p><p>Atas nama: {{ config('booking.bank_account_name') ?: '-' }}</p>@if(config('booking.bank_account_number') === '0000000000')<small>Data rekening dummy.</small>@endif</div>
                <label for="settlement-proof">Bukti pelunasan</label><input id="settlement-proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required><p>Maksimal 2 MB. Pelunasan akan diperiksa admin.</p><button type="submit">Ajukan Pelunasan</button>
            </form>
        </section>
    @elseif($summary['dp'] && !$summary['verified'])
        <p>Pelunasan dapat diajukan setelah DP diverifikasi dan reservasi dikonfirmasi admin.</p>
    @endif
</main>
<script>
(() => {
    const method = document.getElementById('settlement-method');
    if (!method) return;
    const sync = () => { document.querySelector('[data-settlement-qris]').hidden = method.value !== 'qris'; document.querySelector('[data-settlement-bank]').hidden = method.value !== 'bank_transfer'; };
    method.addEventListener('change', sync); sync();
    const proof = document.getElementById('settlement-proof');
    proof.addEventListener('change', () => proof.setCustomValidity(proof.files[0]?.size > 2048 * 1024 ? 'Ukuran bukti maksimal 2 MB.' : ''));
})();
</script>
@endsection
