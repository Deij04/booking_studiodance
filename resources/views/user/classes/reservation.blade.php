@extends('layout.landing')
@section('title', 'Reservasi Class')
@section('content')
<main class="reservation-page">
    <header class="reservation-heading"><h1 data-class-heading>Choose Your Session</h1><p>{{ $danceClass->name }} &middot; Rp {{ number_format($danceClass->price, 0, ',', '.') }} / session</p></header>
    <form data-class-reservation data-price="{{ $danceClass->price }}" data-resume="{{ $errors->any() && old('class_schedule_id') ? 'payment' : 'sessions' }}" action="{{ route('user.classes.store', $danceClass) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="reservation-errors" data-class-errors tabindex="-1" role="alert" @if(!$errors->any()) hidden @endif>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        <div class="booking-progress class-booking-progress" aria-label="Class booking progress"><div class="current" aria-current="step" data-progress="sessions"><span>1</span><strong>CLASS</strong></div><div data-progress="details"><span>2</span><strong>DETAILS</strong></div><div data-progress="payment"><span>3</span><strong>PAYMENT</strong></div><div><span>4</span><strong>CONFIRM</strong></div></div>
        <section data-class-step="sessions">
            <a class="schedule-back" href="{{ route('user.classes.index') }}">&larr; Back to Classes</a>
            <div class="class-session-grid">
                @forelse($schedules as $schedule)
                    @php($unavailable = $schedule->occupied >= $schedule->capacity || in_array($schedule->id, $registeredIds))
                    <label class="class-session-card {{ $unavailable ? 'unavailable' : '' }}">
                        <input type="radio" name="class_schedule_id" value="{{ $schedule->id }}" data-date="{{ \Illuminate\Support\Carbon::parse($schedule->date)->format('l, d F Y') }}" data-time="{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}" data-studio="{{ $schedule->studio->name }}" @disabled($unavailable) @checked(!$unavailable && old('class_schedule_id', request('schedule_id')) == $schedule->id)>
                        <span><strong>{{ \Illuminate\Support\Carbon::parse($schedule->date)->format('l, d F Y') }}</strong><span>{{ substr($schedule->start_time, 0, 5) }} &ndash; {{ substr($schedule->end_time, 0, 5) }}</span><span>{{ $schedule->studio->name }}</span><small>{{ in_array($schedule->id, $registeredIds) ? 'Already registered' : max(0, $schedule->capacity - $schedule->occupied).' spots left' }}</small></span>
                    </label>
                @empty<p>No upcoming sessions available.</p>@endforelse
            </div>
            <div class="reservation-actions"><button type="button" data-class-next="details" disabled>Next</button></div>
        </section>
        <section class="booking-detail-step" data-class-step="details" hidden>
            <button class="detail-back detail-back-top" type="button" data-class-back="sessions">&larr; Back</button>
            <div class="booking-detail-layout"><div class="booking-detail-form">
                <label for="class-participant">Participant Name</label><input id="class-participant" value="{{ auth()->user()->name }}" readonly>
                <label for="class-payment-method">Metode Pembayaran</label><select id="class-payment-method" name="payment_method" required><option value="">Pilih metode pembayaran</option>@foreach(\App\Models\Reservation::ONLINE_PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select>
                <label for="class-payment-type">Jenis Pembayaran</label><select id="class-payment-type" name="payment_type" required>@foreach(\App\Models\Reservation::PAYMENT_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('payment_type', 'full') === $value)>{{ $label }}</option>@endforeach</select>
                <label for="class-notes">Note</label><textarea id="class-notes" name="notes" maxlength="5000" rows="4">{{ old('notes') }}</textarea>
            </div><aside class="booking-info"><h2>Class Info</h2><p>{{ $danceClass->name }}</p><p data-class-date></p><p data-class-time></p><p data-class-studio></p><button type="button" class="detail-next" data-class-next="payment">Next</button></aside></div>
        </section>
        <section class="booking-payment-step" data-class-step="payment" hidden>
            <button class="detail-back detail-back-top" type="button" data-class-back="details">&larr; Back</button>
            <div class="payment-layout">
                <article class="payment-studio-card"><div class="payment-studio-photo"><span>RESERVED CLASS</span>@if($danceClass->image)<img src="{{ asset('storage/'.$danceClass->image) }}" alt="{{ $danceClass->name }}">@else<div class="payment-photo-empty">{{ $danceClass->name }}</div>@endif</div><div class="payment-studio-body"><div class="payment-studio-title"><h3>{{ $danceClass->name }}</h3><div><strong>Rp {{ number_format($danceClass->price, 0, ',', '.') }}</strong><small>1 session</small></div></div>@if($danceClass->description)<p class="payment-description">{{ $danceClass->description }}</p>@endif<dl class="payment-studio-facts"><div><dt>DATE</dt><dd data-class-date></dd></div><div><dt>TIME</dt><dd data-class-time></dd></div><div><dt>STUDIO</dt><dd data-class-studio></dd></div><div><dt>PARTICIPANT</dt><dd>{{ auth()->user()->name }}</dd></div></dl></div></article>
                
                <article class="payment-summary"><h3>Payment Summary</h3><div><span>Class Registration (1 person)</span><span>Rp {{ number_format($danceClass->price, 0, ',', '.') }}</span></div><div class="payment-grand-total"><strong>Grand Total</strong><strong>Rp {{ number_format($danceClass->price, 0, ',', '.') }}</strong></div><div class="payment-installment"><span data-class-due-label>Bayar Sekarang</span><strong data-class-due></strong></div><div class="payment-installment"><span>Sisa Pelunasan</span><strong data-class-remaining></strong></div></article>
                <aside class="payment-image-panel payment-method-card"><div class="payment-status-panel"><p>Metode Pembayaran<strong data-class-method-label></strong></p><div><span>Jenis Pembayaran :</span><strong data-class-type-label></strong></div></div><div class="payment-image-frame" data-class-qris><img src="{{ asset(config('booking.qris_image')) }}" alt="QRIS pembayaran">@if(config('booking.qris_image') === 'images/logoorange.png')<p>Logo sementara - QRIS belum tersedia</p>@endif</div><div class="payment-bank-account" data-class-bank hidden><h3>Informasi Rekening</h3><dl><dt>Bank</dt><dd>{{ config('booking.bank_name') ?: 'Belum tersedia' }}</dd><dt>Nomor Rekening</dt><dd class="bank-account-number">{{ config('booking.bank_account_number') ?: 'Belum tersedia' }}</dd><dt>Atas Nama</dt><dd>{{ config('booking.bank_account_name') ?: 'Belum tersedia' }}</dd></dl>
                    @if(config('booking.bank_account_number') === '0000000000')
                        <p>Data rekening dummy untuk contoh tampilan.</p>
                    @endif
                </div></aside>
            </div>
            <div class="payment-upload"><label for="class-payment-proof">Bukti pembayaran (wajib)</label><input id="class-payment-proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required><small>JPG, PNG, WEBP atau PDF, maksimal 2 MB. Pembayaran diverifikasi oleh admin.</small></div>
            <div class="reservation-actions"><button type="submit" data-class-submit>Ajukan Reservasi Class</button></div>
        </section>
        <noscript><p>Aktifkan JavaScript untuk melakukan reservasi kelas.</p></noscript>
    </form>
</main>
@endsection
