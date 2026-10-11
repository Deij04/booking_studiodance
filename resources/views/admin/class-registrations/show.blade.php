@extends('layout.dashboard')
@section('title', 'Detail Reservasi Class')
@section('content')
<div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
    <a href="{{ route('admin.class-registrations.index') }}" class="text-sm text-[#f2c83f]">Kembali ke daftar reservasi</a>
    @if(session('success'))<p role="status" class="text-green-300">{{ session('success') }}</p>@endif
    @if($errors->any())<p role="alert" class="text-red-300">{{ $errors->first() }}</p>@endif
    <h2 class="text-2xl font-bold">Detail Reservasi Class</h2>
    <dl class="grid gap-6 rounded-2xl border border-white/10 bg-[#171717] p-6 sm:grid-cols-2">
        <div><dt class="text-xs text-white/45">Peserta</dt><dd class="mt-2">{{ $registration->user->name }}<span class="block text-sm text-white/60">{{ $registration->user->email }}</span></dd></div>
        <div><dt class="text-xs text-white/45">Class</dt><dd class="mt-2">{{ $registration->classSchedule->danceClass->name }}</dd></div>
        <div><dt class="text-xs text-white/45">Studio</dt><dd class="mt-2">{{ $registration->classSchedule->studio->name }}</dd></div>
        <div><dt class="text-xs text-white/45">Jadwal</dt><dd class="mt-2">{{ \Illuminate\Support\Carbon::parse($registration->classSchedule->date)->format('d/m/Y') }} &middot; {{ substr($registration->classSchedule->start_time, 0, 5) }} &ndash; {{ substr($registration->classSchedule->end_time, 0, 5) }}</dd></div>
        <div><dt class="text-xs text-white/45">Total biaya</dt><dd class="mt-2">Rp {{ number_format($registration->price, 2, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-white/45">Status reservasi</dt><dd class="mt-2">{{ $registration->display_status }}</dd></div>
        <div><dt class="text-xs text-white/45">Status verifikasi</dt><dd class="mt-2">{{ ($registration->payment?->verification_status === 'rejected' ? 'Belum Terverifikasi' : \App\Models\Booking::PAYMENT_STATUSES[$registration->payment_status]) }}</dd></div>
        <div><dt class="text-xs text-white/45">Metode pembayaran</dt><dd class="mt-2">{{ \App\Models\Booking::PAYMENT_METHODS[$registration->payment?->payment_method] ?? '-' }}</dd></div>
        <div><dt class="text-xs text-white/45">Jenis pembayaran</dt><dd class="mt-2">{{ \App\Models\Booking::PAYMENT_TYPES[$registration->payment?->payment_type] ?? '-' }}</dd></div>
        <div><dt class="text-xs text-white/45">Diverifikasi oleh</dt><dd class="mt-2">{{ $registration->payment?->verified_by_name ?: '-' }}@if($registration->payment?->verified_at)<span class="block text-xs text-white/60">{{ $registration->payment->verified_at->format('d/m/Y H:i') }}</span>@endif</dd></div>
    </dl>
    @if($registration->payment?->verification_status === 'rejected')<p class="text-sm text-red-300">Alasan pengajuan ditolak: {{ $registration->payment->rejection_reason }}</p>@endif
    @if($registration->payment?->notes)<p class="text-sm whitespace-pre-wrap">Catatan: {{ $registration->payment->notes }}</p>@endif
    @include('admin.bookings.proof-preview', ['proofPath' => $registration->payment?->payment_proof, 'proofUrl' => route('admin.class-registrations.payment-proof', $registration)])
    @php
        $summary = app(\App\Services\PaymentSummary::class)->for($registration);
    @endphp
    <section id="pelunasan" class="rounded-2xl border border-white/10 bg-[#171717] p-6 space-y-4">
        <h3 class="font-semibold">Pembayaran &amp; Pelunasan</h3>
        <p>{{ $summary['label'] }}</p>
        <p>Sudah dibayar: Rp {{ number_format($summary['paid'], 0, ',', '.') }} &middot; Sisa: Rp {{ number_format($summary['remaining'], 0, ',', '.') }}</p>
        @if($summary['canSettle'])
            <a href="{{ route('admin.class-registrations.settlement-form', $registration) }}" class="inline-block rounded-lg bg-[#f2c83f] px-3 py-2 text-xs font-semibold text-black">Pelunasan</a>
        @endif
        @if($summary['settlement']?->status === 'pending')
            <div class="flex gap-3">
                @foreach(['verified' => 'Verifikasi Pelunasan', 'rejected' => 'Tolak Pelunasan'] as $decision => $label)
                <form method="POST" action="{{ route('admin.settlements.decide', $summary['settlement']) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="{{ $decision }}"><button class="rounded-lg border border-[#f2c83f]/30 px-3 py-2 text-xs text-[#f2c83f]">{{ $label }}</button></form>
                @endforeach
            </div>
        @endif
    </section>
    @foreach($registration->settlements()->orderBy('id')->get() as $classSettlement)
        <p class="text-sm">Pelunasan Rp {{ number_format($classSettlement->amount, 0, ',', '.') }} &middot; {{ \App\Models\Payment::STATUSES[$classSettlement->status] }} &middot; {{ $classSettlement->verified_by_name }}</p>
        @include('admin.bookings.proof-preview', ['proofPath' => $classSettlement->payment_proof, 'proofUrl' => route('admin.settlements.proof', $classSettlement), 'proofTitle' => 'Bukti Pelunasan'])
    @endforeach
</div>
@endsection
