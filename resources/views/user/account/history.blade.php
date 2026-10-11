@extends('layout.landing')
@section('content')
<main class="section" style="max-width:1000px;margin:0 auto;min-height:60vh">
    <h1 class="section-title">My <span class="gold">History</span></h1>
    <p class="sub">Your studio bookings, class registrations, and memberships.</p>
    @if(session('success'))<p role="status" data-booking-notice="success" style="padding:16px;border:1px solid var(--gold);border-radius:12px">{{ session('success') }}</p>@endif
    @foreach(['Studio Bookings' => $bookings, 'Class Registrations' => $registrations, 'Memberships' => $memberships] as $heading => $records)
        <section style="margin-top:32px">
            <h2 style="color:var(--gold);font-size:24px">{{ $heading }}</h2>
            @forelse($records as $record)
                <article class="faq-item" style="overflow-wrap:anywhere">
                    @if($heading === 'Studio Bookings')
                        <h3>{{ $record->studio?->name ?? 'Studio' }} &middot; {{ $record->booking_code }}</h3>
                        <p class="small">{{ $record->date ?? '-' }} &middot; {{ $record->start_time ?? '-' }} &ndash; {{ $record->end_time ?? '-' }}</p>
                        @if($record->payment_method)<p class="small">Metode pembayaran: {{ \App\Models\Booking::PAYMENT_METHODS[$record->payment_method] ?? '-' }}</p>@endif
                        @if($record->rejection_reason)<p class="small">Alasan penolakan: {{ $record->rejection_reason }}</p>@endif
                        @php($status = \App\Models\Booking::STATUSES[$record->status] ?? $record->status)
                        @php($price = $record->total_price)
                    @elseif($heading === 'Class Registrations')
                        <h3>{{ $record->classSchedule?->danceClass?->name ?? 'Dance Class' }}</h3>
                        <p class="small">{{ $record->classSchedule?->date ?? '-' }} &middot; {{ $record->classSchedule?->start_time ?? '-' }} &ndash; {{ $record->classSchedule?->end_time ?? '-' }}</p>
                        @if($record->payment)<p class="small">{{ \App\Models\Booking::PAYMENT_METHODS[$record->payment->payment_method] ?? '-' }} &middot; {{ \App\Models\Booking::PAYMENT_TYPES[$record->payment->payment_type] ?? '-' }}</p>@endif
                        @php($status = $record->display_status)
                        @php($price = $record->price)
                    @else
                        <h3>{{ $record->membershipPlan?->name ?? 'Membership' }}</h3>
                        <p class="small">@if($record->start_date && $record->end_date){{ $record->start_date->format('d M Y') }} &ndash; {{ $record->end_date->format('d M Y') }}@else Dates assigned upon approval @endif</p>
                        @php($status = $record->effective_status)
                        @php($price = $record->price)
                    @endif
                    @if(in_array($heading, ['Studio Bookings', 'Class Registrations']))
                        <p class="small">Status {{ $heading === 'Studio Bookings' ? 'Penyewaan' : 'Reservasi Kelas' }}: <strong>{{ $status }}</strong></p>
                        @php($historyPayment = app(\App\Services\PaymentSummary::class)->for($record))
                        @if($heading === 'Class Registrations')<p class="small">Status pembayaran: <strong>{{ $historyPayment['label'] }}</strong></p>@endif
                        <p class="small">Total Dibayar: IDR {{ number_format($historyPayment['paid'], 0, '.', ',') }}</p>
                        <a class="btn" href="{{ route('user.history.detail', ['type' => $heading === 'Studio Bookings' ? 'studio' : 'class', 'id' => $record->id]) }}" style="display:inline-flex;align-items:center;justify-content:center;margin:10px 0 0;padding:8px 16px;min-height:36px;font-size:12px;font-weight:500;line-height:1.4">Detail Reservasi</a>
                        @php($historyPayment = app(\App\Services\PaymentSummary::class)->for($record))
                        @if($historyPayment['canSettle'])
                            <a class="btn" href="{{ route('user.history.detail', ['type' => $heading === 'Studio Bookings' ? 'studio' : 'class', 'id' => $record->id]) }}#bayar-pelunasan" style="display:inline-flex;align-items:center;justify-content:center;margin:10px 0 0 8px;padding:8px 16px;min-height:36px;font-size:12px;font-weight:600;line-height:1.4;background:var(--gold);color:#111">Pelunasan</a>
                        @endif
                    @else
                        <p class="small">{{ ucfirst($status) }} &middot; {{ $record->payment_status === 'paid' ? 'Paid' : 'Unpaid' }} &middot; IDR {{ number_format($price, 0, '.', ',') }}</p>
                    @endif
                </article>
            @empty<p class="small" style="color:var(--muted)">No records yet.</p>@endforelse
            <nav aria-label="{{ $heading }} pagination" style="display:flex;justify-content:space-between;gap:16px">
                @if($records->previousPageUrl())<a class="btn" href="{{ $records->previousPageUrl() }}">Previous</a>@endif
                @if($records->nextPageUrl())<a class="btn" href="{{ $records->nextPageUrl() }}">Next</a>@endif
            </nav>
        </section>
    @endforeach
</main>
@endsection
