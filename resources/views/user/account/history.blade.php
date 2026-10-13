@extends('layout.landing')
@section('content')
<style>
.history-page{max-width:1100px;min-height:65vh;padding-top:48px;padding-bottom:64px}.history-overview{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:30px 0 36px}.history-summary{padding:22px;border:1px solid #f0c63a30;border-radius:20px;background:linear-gradient(145deg,#282316,#191919 75%);transition:border-color .2s}.history-summary:hover{border-color:var(--gold)}.history-summary strong{display:block;color:var(--gold);font-size:30px}.history-summary span{font-size:13px;color:var(--muted)}.history-section{margin-top:36px;scroll-margin-top:110px}.history-section-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.history-section-heading h2{margin:0;font-size:22px}.history-count{padding:5px 12px;border-radius:99px;background:#f0c63a14;color:var(--gold);font-size:12px;white-space:nowrap}.history-records{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.history-page .history-record{margin:0;padding:24px;border-radius:20px;background:#191919;border:1px solid #f0c63a26;overflow-wrap:anywhere}.history-record h3{margin:0 0 16px;font-size:18px;line-height:1.6}.history-record .small{color:var(--muted);font-size:13px;line-height:1.8}.history-record strong{color:var(--text)}.history-page .history-record .btn{min-height:44px!important;margin-left:0!important;margin-right:8px!important;border-radius:10px!important}.history-empty{grid-column:1/-1;padding:32px 24px;border:1px dashed #f0c63a30;border-radius:20px;background:#191919;text-align:center}.history-empty p{color:var(--muted);font-size:13px;line-height:1.8;margin:8px 0 0}.history-empty strong{font-size:15px}.history-pagination{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:18px}.history-pagination .btn{min-height:44px;padding:10px 18px;font-size:13px}.history-notice{padding:16px 20px;border:1px solid #74be8b55;border-radius:12px;background:#213127;color:#b7e5c3;font-size:14px}@media(max-width:760px){.history-records{grid-template-columns:1fr}.history-overview{gap:10px}.history-summary{padding:16px 12px}.history-summary strong{font-size:24px}.history-summary span{font-size:11px}.history-section-heading h2{font-size:19px}.history-page .history-record{padding:20px}.history-page{padding-top:32px}}@media(max-width:380px){.history-overview{grid-template-columns:1fr}.history-summary{display:flex;align-items:center;gap:16px}}
</style>
<main class="section history-page">
    <h1 class="section-title">My <span class="gold">History</span></h1>
    <p class="sub">Pantau reservasi studio dan kelas dance kamu dalam satu tempat.</p>
    @if(session('success'))<p class="history-notice" role="status" data-booking-notice="success">{{ session('success') }}</p>@endif
    <nav class="history-overview" aria-label="Kategori riwayat">
        @foreach(['Studio Bookings' => $bookings, 'Class Registrations' => $registrations] as $heading => $records)
            <a class="history-summary" href="#history-{{ \Illuminate\Support\Str::slug($heading) }}"><strong>{{ $records->total() }}</strong><span>{{ ['Studio Bookings' => 'Reservasi studio', 'Class Registrations' => 'Reservasi kelas'][$heading] }}</span></a>
        @endforeach
    </nav>
    @foreach(['Studio Bookings' => $bookings, 'Class Registrations' => $registrations] as $heading => $records)
        <section class="history-section" id="history-{{ \Illuminate\Support\Str::slug($heading) }}" aria-labelledby="heading-{{ \Illuminate\Support\Str::slug($heading) }}">
            <div class="history-section-heading"><h2 id="heading-{{ \Illuminate\Support\Str::slug($heading) }}">{{ $heading }}</h2><span class="history-count">{{ $records->total() }} riwayat</span></div>
            <div class="history-records">
            @forelse($records as $record)
                <article class="faq-item history-record">
                    @if($heading === 'Studio Bookings')
                        <h3>{{ $record->studio?->name ?? 'Studio' }} &middot; {{ $record->booking_code }}</h3>
                        <p class="small">{{ $record->date ?? '-' }} &middot; {{ $record->start_time ?? '-' }} &ndash; {{ $record->end_time ?? '-' }}</p>
                        @if($record->payment_method)<p class="small">Metode pembayaran: {{ \App\Models\Booking::PAYMENT_METHODS[$record->payment_method] ?? '-' }}</p>@endif
                        @if($record->rejection_reason)<p class="small">Alasan penolakan: {{ $record->rejection_reason }}</p>@endif
                        @php($status = \App\Models\Booking::STATUSES[$record->status] ?? $record->status)
                    @elseif($heading === 'Class Registrations')
                        <h3>{{ $record->classSchedule?->danceClass?->name ?? 'Dance Class' }}</h3>
                        <p class="small">{{ $record->classSchedule?->date ?? '-' }} &middot; {{ $record->classSchedule?->start_time ?? '-' }} &ndash; {{ $record->classSchedule?->end_time ?? '-' }}</p>
                        @if($record->payment)<p class="small">{{ \App\Models\Booking::PAYMENT_METHODS[$record->payment->payment_method] ?? '-' }} &middot; {{ \App\Models\Booking::PAYMENT_TYPES[$record->payment->payment_type] ?? '-' }}</p>@endif
                        @php($status = $record->display_status)
                    @endif
                        <p class="small">Status {{ $heading === 'Studio Bookings' ? 'Penyewaan' : 'Reservasi Kelas' }}: <strong>{{ $status }}</strong></p>
                        @php($historyPayment = app(\App\Services\PaymentSummary::class)->for($record))
                        @if($heading === 'Class Registrations')<p class="small">Status pembayaran: <strong>{{ $historyPayment['label'] }}</strong></p>@endif
                        <p class="small">Total Dibayar: IDR {{ number_format($historyPayment['paid'], 0, '.', ',') }}</p>
                        <a class="btn" href="{{ route('user.history.detail', ['type' => $heading === 'Studio Bookings' ? 'studio' : 'class', 'id' => $record->id]) }}" style="display:inline-flex;align-items:center;justify-content:center;margin:10px 0 0;padding:8px 16px;min-height:36px;font-size:12px;font-weight:500;line-height:1.4">Detail Reservasi</a>
                        @if($historyPayment['canSettle'])
                            <a class="btn" href="{{ route('user.history.detail', ['type' => $heading === 'Studio Bookings' ? 'studio' : 'class', 'id' => $record->id]) }}#bayar-pelunasan" style="display:inline-flex;align-items:center;justify-content:center;margin:10px 0 0 8px;padding:8px 16px;min-height:36px;font-size:12px;font-weight:600;line-height:1.4;background:var(--gold);color:#111">Pelunasan</a>
                        @endif
                </article>
            @empty
                <div class="history-empty"><strong>Belum ada riwayat</strong><p>{{ ['Studio Bookings' => 'Reservasi studio kamu akan muncul di sini.', 'Class Registrations' => 'Kelas yang kamu daftarkan akan muncul di sini.'][$heading] }}</p></div>
            @endforelse
            </div>
            <nav class="history-pagination" aria-label="{{ $heading }} pagination">
                @if($records->previousPageUrl())<a class="btn" href="{{ $records->previousPageUrl() }}">Previous</a>@endif
                @if($records->nextPageUrl())<a class="btn" href="{{ $records->nextPageUrl() }}">Next</a>@endif
            </nav>
        </section>
    @endforeach
</main>
@endsection
