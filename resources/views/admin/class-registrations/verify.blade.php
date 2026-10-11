@extends('layout.dashboard')
@section('title', 'Verifikasi Pembayaran Class')
@section('content')
<main class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
    <a href="{{ route('admin.class-registrations.index') }}" class="text-sm text-[#f2c83f]">Kembali ke reservasi class</a>
    <h2 class="text-2xl font-bold">Verifikasi Pembayaran Class</h2>
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="rounded-2xl border border-white/10 bg-[#171717] p-6 space-y-3">
        <p class="font-semibold">{{ $registration->user->name }} &middot; {{ $registration->classSchedule->danceClass->name }}</p>
        <p class="text-sm text-white/60">{{ $registration->classSchedule->date }} &middot; {{ substr($registration->classSchedule->start_time,0,5) }} - {{ substr($registration->classSchedule->end_time,0,5) }} WITA</p>
        <p class="text-[#f2c83f]">Lunas: Rp {{ number_format($registration->price, 0, ',', '.') }}</p>
        <p class="text-sm">Metode: {{ \App\Models\Booking::PAYMENT_METHODS[$registration->payment?->payment_method] ?? 'Tunai (admin)' }}</p>
    </section>
    @include('admin.bookings.proof-preview', ['proofPath' => $registration->payment?->payment_proof, 'proofUrl' => route('admin.class-registrations.payment-proof', $registration)])
    @if($registration->payment?->verification_status === 'rejected')
        <p class="rounded-xl bg-red-400/10 p-4 text-sm text-red-300">Pengajuan Ditolak: {{ $registration->payment->rejection_reason }}. Pelanggan perlu mendaftar class lagi.</p>
    @else
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.class-registrations.decide', $registration) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="verified"><button class="rounded-lg bg-[#f2c83f] px-4 py-2 text-sm font-semibold text-black">Verifikasi</button></form>
            <form method="POST" action="{{ route('admin.class-registrations.decide', $registration) }}" data-class-payment-reject>@csrf @method('PATCH')<input type="hidden" name="decision" value="rejected"><input type="hidden" name="rejection_reason" value="{{ old('rejection_reason') }}"><button class="rounded-lg bg-red-400/10 px-4 py-2 text-sm font-semibold text-red-300">Tolak</button></form>
        </div>
    @endif
</main>
@endsection
