@extends('layout.landing')
@push('styles')
<style>
.membership-payment{max-width:760px;margin:0 auto;padding:48px 24px;color:var(--text)}.membership-payment h1{font-size:30px;margin:18px 0}.membership-payment h2{font-size:20px;margin:0 0 16px}.membership-payment p{line-height:1.7;color:var(--muted)}.membership-payment section,.membership-payment form{padding:24px;margin:24px 0;border:1px solid rgba(240,198,58,.25);border-radius:18px;background:var(--panel)}.membership-payment strong{color:var(--gold)}.membership-payment dl{display:grid;grid-template-columns:1fr 2fr;gap:12px}.membership-payment dd{margin:0;overflow-wrap:anywhere}.membership-payment img{display:block;max-width:220px;width:100%;margin:16px 0}.membership-payment input[type=file]{display:block;width:100%;box-sizing:border-box;padding:14px;margin:14px 0;border:1px solid #59513a;border-radius:10px}.membership-payment button{padding:14px 24px;border:0;border-radius:12px;background:var(--gold);color:#171717;font:inherit;cursor:pointer}.membership-payment a{color:var(--gold)}.membership-payment .error{color:#ffaaaa}.membership-payment small{display:block;line-height:1.6;color:var(--muted)}
</style>
@endpush
@section('content')
<main class="membership-payment">
    <a href="{{ route('memberships.index') }}">&larr; Kembali ke paket membership</a>
    <h1>Pembayaran Membership</h1>
    <section aria-label="Ringkasan paket">
        <h2>{{ $plan->display_name }}</h2>
        <p>Total pembayaran: <strong>Rp {{ number_format($plan->price, 0, ',', '.') }}</strong></p>
        <p>Masa berlaku {{ $plan->duration_days }} hari sejak disetujui admin.</p>
        @if($plan->quota_hours)<p>Kuota studio: {{ $plan->quota_hours }} jam.</p>@endif
        @if($plan->free_classes)<p>Kuota kelas: {{ $plan->class_quota === null ? 'Tanpa batas' : $plan->class_quota.' kelas' }}.</p>@endif
    </section>
    <section aria-label="Instruksi pembayaran">
        <h2>1. Lakukan pembayaran</h2>
        <p>Bayar sesuai total paket melalui transfer bank atau QRIS yang tersedia, lalu simpan bukti pembayaran.</p>
        <h3>Transfer Bank</h3>
        @if(config('booking.bank_account_number') && config('booking.bank_account_number') !== '0000000000')
            <dl><dt>Bank</dt><dd>{{ config('booking.bank_name') }}</dd><dt>Nomor rekening</dt><dd>{{ config('booking.bank_account_number') }}</dd><dt>Atas nama</dt><dd>{{ config('booking.bank_account_name') }}</dd></dl>
        @else
            <p>Informasi rekening belum tersedia. Hubungi admin untuk mendapatkan rekening pembayaran.</p>
        @endif
        <h3>QRIS</h3>
        @if(config('booking.qris_image') && config('booking.qris_image') !== 'images/logoorange.png')
            <img src="{{ asset(config('booking.qris_image')) }}" alt="QRIS pembayaran membership">
        @else
            <p>QRIS belum tersedia. Hubungi admin untuk detail pembayaran.</p>
        @endif
    </section>
    <form method="POST" action="{{ route('memberships.store') }}" enctype="multipart/form-data">
        @csrf
        <h2>2. Kirim bukti pembayaran</h2>
        @if($errors->any())<div role="alert" class="error"><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div>@endif
        <input type="hidden" name="membership_plan_id" value="{{ $plan->id }}">
        <label for="payment_proof">Bukti pembayaran (wajib)</label>
        <input id="payment_proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required aria-describedby="proof-help">
        <small id="proof-help">JPG, PNG, WEBP, atau PDF. Maksimal 2 MB.</small>
        <p>Pengajuan dikirim ke admin setelah bukti pembayaran diunggah. Membership belum aktif sampai admin memverifikasi pembayaran dan menyetujui pengajuan.</p>
        <button data-save-confirm type="submit">Kirim Bukti &amp; Pengajuan</button>
    </form>
</main>
@endsection
