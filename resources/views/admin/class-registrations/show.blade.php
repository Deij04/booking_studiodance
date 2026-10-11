@extends('layout.dashboard')
@section('title', 'Detail Reservasi Class')
@section('content')
<div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
    <a href="{{ route('admin.class-registrations.index') }}" class="text-sm text-[#f2c83f]">Kembali ke daftar reservasi</a>
    <h2 class="text-2xl font-bold">Detail Reservasi Class</h2>
    <dl class="grid gap-6 rounded-2xl border border-white/10 bg-[#171717] p-6 sm:grid-cols-2">
        <div><dt class="text-xs text-white/45">Peserta</dt><dd class="mt-2">{{ $registration->user->name }}<span class="block text-sm text-white/60">{{ $registration->user->email }}</span></dd></div>
        <div><dt class="text-xs text-white/45">Class</dt><dd class="mt-2">{{ $registration->classSchedule->danceClass->name }}</dd></div>
        <div><dt class="text-xs text-white/45">Studio</dt><dd class="mt-2">{{ $registration->classSchedule->studio->name }}</dd></div>
        <div><dt class="text-xs text-white/45">Jadwal</dt><dd class="mt-2">{{ \Illuminate\Support\Carbon::parse($registration->classSchedule->date)->format('d/m/Y') }} &middot; {{ substr($registration->classSchedule->start_time, 0, 5) }} &ndash; {{ substr($registration->classSchedule->end_time, 0, 5) }}</dd></div>
        <div><dt class="text-xs text-white/45">Total biaya</dt><dd class="mt-2">Rp {{ number_format($registration->price, 2, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-white/45">Status reservasi</dt><dd class="mt-2">{{ \App\Models\ClassRegistration::STATUSES[$registration->registration_status] }}</dd></div>
        <div><dt class="text-xs text-white/45">Status verifikasi</dt><dd class="mt-2">{{ \App\Models\Reservation::PAYMENT_STATUSES[$registration->payment_status] }}</dd></div>
        <div><dt class="text-xs text-white/45">Metode pembayaran</dt><dd class="mt-2">{{ \App\Models\Reservation::PAYMENT_METHODS[$registration->payment?->payment_method] ?? '-' }}</dd></div>
        <div><dt class="text-xs text-white/45">Jenis pembayaran</dt><dd class="mt-2">{{ \App\Models\Reservation::PAYMENT_TYPES[$registration->payment?->payment_type] ?? '-' }}</dd></div>
        <div><dt class="text-xs text-white/45">Diverifikasi oleh</dt><dd class="mt-2">{{ $registration->payment?->verified_by_name ?: '-' }}@if($registration->payment?->verified_at)<span class="block text-xs text-white/60">{{ $registration->payment->verified_at->format('d/m/Y H:i') }}</span>@endif</dd></div>
    </dl>
    @if($registration->payment?->notes)<p class="text-sm whitespace-pre-wrap">Catatan: {{ $registration->payment->notes }}</p>@endif
    @include('admin.bookings.proof-preview', ['proofPath' => $registration->payment?->payment_proof, 'proofUrl' => route('admin.class-registrations.payment-proof', $registration)])
    @if($registration->settlement)
        @include('admin.bookings.proof-preview', ['proofPath' => $registration->settlement->payment_proof, 'proofUrl' => route('admin.settlements.proof', $registration->settlement), 'proofTitle' => 'Bukti Pelunasan'])
    @endif
</div>
@endsection
