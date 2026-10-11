@extends('layout.landing')
@section('title', 'Reservasi Studio')
@section('content')
<main class="reservation-page">
    <header class="reservation-heading"><h1>Availability Calendar</h1><p>Real-time scheduling across all studio spaces.</p></header>
    <form data-reservation-calendar data-resume-payment="{{ $errors->any() && old('date') ? '1' : '0' }}" data-url="{{ url('reservations/availability') }}" data-today="{{ today()->toDateString() }}" data-minimum="{{ config('booking.minimum_minutes') }}" data-step="{{ config('booking.step_minutes') }}" action="{{ route('user.reservations.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="reservation-errors" data-reservation-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        <div class="reservation-studio" data-schedule-studio><span>{{ $studio->name }}</span><span>Rp {{ number_format($studio->price_per_hour, 0, ',', '.') }}/hour</span></div>
        <input type="hidden" id="studio_id" name="studio_id" value="{{ $studio->id }}" data-name="{{ $studio->name }}" data-price="{{ $studio->price_per_hour }}" data-capacity="{{ $studio->capacity }}">
        <input type="hidden" id="reservation-date" name="date" value="{{ old('date', request('date')) }}">
        <input type="hidden" id="reservation-start" name="start_time" value="{{ old('start_time', request('start_time')) }}">
        <input type="hidden" id="reservation-end" name="end_time" value="{{ old('end_time', request('end_time')) }}">
        <a class="schedule-back" data-schedule-back href="{{ route('user.studios.index') }}">&larr; Back to Studios</a>
        <section class="reservation-schedule" aria-label="Choose your reservation schedule">
            <div class="calendar-panel">
                <nav class="calendar-month" aria-label="Calendar month"><button type="button" data-month-prev aria-label="Previous month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6"/></svg></button><h2 id="calendar-month-title"></h2><button type="button" data-month-next aria-label="Next month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg></button></nav>
                <div class="calendar-table"><div class="calendar-weekdays" aria-hidden="true">@foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $day)<span>{{ $day }}</span>@endforeach</div><div class="calendar-days" id="reservation-days" aria-label="Dates"></div></div>
                <div class="calendar-legend"><span></span> booked</div>
                <p class="calendar-status" id="reservation-status" role="status" aria-live="polite">Loading availability...</p><button type="button" data-retry hidden>Try again</button>
            </div>
            <aside class="schedule-sidebar">
                <div class="time-panel"><h2 id="selected-date-title">Choose a date</h2><div id="reservation-times" class="time-list"></div></div>
                <div class="duration-panel"><label for="reservation-hours">Hours:</label><input id="reservation-hours" type="number" value="{{ config('booking.minimum_minutes') / 60 }}" min="{{ config('booking.minimum_minutes') / 60 }}" step="{{ config('booking.step_minutes') / 60 }}" aria-describedby="duration-help"><div class="duration-controls"><button type="button" data-duration-up aria-label="Increase duration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 15 6-6 6 6"/></svg></button><button type="button" data-duration-down aria-label="Decrease duration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></button></div></div>
                <p id="duration-help" class="duration-help">Choose an available start time and duration.</p>
                <button type="button" class="reservation-next" data-next disabled>Next</button>
            </aside>
        </section>
        <section class="booking-detail-step" id="reservation-details" hidden aria-label="Booking details">
            <div class="booking-progress" aria-label="Booking progress"><div class="done"><span>&check;</span><strong>STUDIO</strong></div><div class="current" aria-current="step"><span>2</span><strong>DETAILS</strong></div><div><span>3</span><strong>PAYMENT</strong></div><div><span>4</span><strong>CONFIRM</strong></div></div>
            <button class="detail-back detail-back-top" type="button" data-back>&larr; Back</button>
            <div class="booking-detail-layout">
                <div class="booking-detail-form">
                    <label for="booking_name">Booking Name</label><input id="booking_name" name="booking_name" value="{{ auth()->user()->name }}" maxlength="255" readonly aria-readonly="true">
                    <label for="purpose">Session Type</label><select id="purpose" name="purpose" required><option value="">Choose session type</option>@foreach(['Group Training', 'Private Practice', 'Dance Rehearsal', 'Workshop', 'Other'] as $type)<option value="{{ $type }}" @selected(old('purpose') === $type)>{{ $type }}</option>@endforeach</select>
                    <label for="payment_method">Metode Pembayaran</label><select id="payment_method" name="payment_method" required><option value="">Pilih metode pembayaran</option>@foreach(\App\Models\Reservation::ONLINE_PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select>
                    <label for="payment_type">Jenis Pembayaran</label><select id="payment_type" name="payment_type" required>@foreach(\App\Models\Reservation::PAYMENT_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('payment_type', 'full') === $value)>{{ $label }}</option>@endforeach</select>
                    <label for="notes">Note</label><textarea id="notes" name="notes" rows="6" maxlength="5000" placeholder="Add a note for your session">{{ old('notes') }}</textarea>
                </div>
                <aside class="booking-info"><h2>Booking Info</h2><p id="info-studio"></p><p id="info-date"></p><p id="info-time"></p><p id="info-duration"></p><button type="button" class="detail-next" data-details-next>Next</button></aside>
            </div>
        </section>
        <section class="booking-payment-step" id="reservation-payment" hidden aria-labelledby="payment-title">
            <h2 id="payment-title" class="payment-accessible-title" tabindex="-1">Payment</h2>
            <div class="booking-progress payment-progress" aria-label="Booking progress"><div class="done"><span>&check;</span><strong>STUDIO</strong></div><div class="done"><span>&check;</span><strong>DETAILS</strong></div><div class="current" aria-current="step"><span>3</span><strong>PAYMENT</strong></div><div><span>4</span><strong>CONFIRM</strong></div></div>
            <button type="button" class="detail-back detail-back-top" data-payment-back>&larr; Back</button>
            <div class="payment-layout">
                <article class="payment-studio-card">
                    <div class="payment-studio-photo"><span>RESERVED STUDIO</span>@if($studio->image)<img src="{{ asset('storage/'.$studio->image) }}" alt="{{ $studio->name }}">@else<div class="payment-photo-empty">{{ $studio->name }}</div>@endif</div>
                    <div class="payment-studio-body"><div class="payment-studio-title"><h3>{{ $studio->name }}</h3><div><strong id="payment-studio-total"></strong><small>Rp {{ number_format($studio->price_per_hour, 0, ',', '.') }} / hr</small></div></div>
                        @if($studio->description)<p class="payment-description">{{ $studio->description }}</p>@endif
                        <dl class="payment-studio-facts"><div><dt>DATE</dt><dd id="payment-date"></dd></div><div><dt>TIME</dt><dd id="payment-time"></dd></div><div><dt>CAPACITY</dt><dd>{{ $studio->capacity }} people</dd></div></dl>
                    </div>
                </article>
                
                <article class="payment-summary"><h3>Payment Summary</h3><div><span id="payment-rental-label">Studio Rental</span><span id="payment-rental-total"></span></div><div class="payment-grand-total"><strong>Grand Total</strong><strong id="reservation-price" aria-live="polite"></strong></div><div class="payment-installment"><span id="payment-due-label">Bayar Sekarang</span><strong id="payment-due"></strong></div><div class="payment-installment"><span>Sisa Pelunasan</span><strong id="payment-remaining"></strong></div><p id="reservation-summary" hidden></p></article>
                <aside class="payment-image-panel payment-method-card"><div class="payment-status-panel"><p>Metode Pembayaran<strong id="payment-method-summary"></strong></p><div><span>Jenis Pembayaran :</span><strong id="payment-type-summary"></strong></div></div>
                    <div class="payment-image-frame" data-qris-panel><img id="payment-method-image" src="{{ asset('images/logoorange.png') }}" data-qris-src="{{ asset(config('booking.qris_image', 'images/logoorange.png')) }}" alt="Gambar pembayaran sementara"><p id="payment-image-caption"></p></div>
                    <div class="payment-bank-account" data-bank-panel hidden><h3>Informasi Rekening</h3><dl><dt>Bank</dt><dd>{{ config('booking.bank_name') ?: 'Belum tersedia' }}</dd><dt>Nomor Rekening</dt><dd class="bank-account-number">{{ config('booking.bank_account_number') ?: 'Belum tersedia' }}</dd><dt>Atas Nama</dt><dd>{{ config('booking.bank_account_name') ?: 'Belum tersedia' }}</dd></dl>
                        @if(config('booking.bank_account_number') === '0000000000')<p>Data rekening dummy untuk contoh tampilan.</p>
                        @endif
                        @if(!config('booking.bank_account_number'))<p>Informasi rekening belum tersedia. Hubungi admin untuk detail pembayaran.</p>
                        @endif
                    </div>
                    <a id="payment-image-download" href="{{ asset('images/logoorange.png') }}" download hidden>UNDUH QRIS</a>
                </aside>
            </div>
            <div class="payment-upload"><label for="payment_proof">Bukti pembayaran (wajib)</label><input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required><small>JPG, PNG, WEBP atau PDF, maksimal 2 MB. Pembayaran diverifikasi oleh admin.</small></div>
            <div class="reservation-actions"><button type="submit" data-submit disabled>Ajukan Reservasi</button></div>
        </section>
        <noscript><p role="alert">Aktifkan JavaScript untuk memilih jadwal reservasi.</p></noscript>
    </form>
</main>
@endsection
