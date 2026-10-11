@extends('layout.dashboard')
@section('title', 'Pelunasan Class')
@section('content')
<main class="mx-auto max-w-3xl space-y-5 p-6">
<a href="{{ route('admin.class-registrations.show', $registration) }}" class="text-sm text-[#f2c83f]">Kembali ke detail</a>
<h2 class="text-xl font-bold">Pelunasan Class</h2>
<p>{{ $registration->user->name }} &middot; {{ $registration->classSchedule->danceClass->name }}</p>
<p>Sisa pembayaran: Rp {{ number_format($summary['remaining'], 0, ',', '.') }}</p>
@if($errors->any())<p class="text-red-300">{{ $errors->first() }}</p>@endif
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.class-registrations.settle', $registration) }}" class="space-y-3">
                @csrf
                <label class="block text-sm">Metode pembayaran<select required name="payment_method" class="ml-3 rounded-lg bg-black p-2">@foreach(\App\Models\Booking::PAYMENT_METHODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                <label class="block text-sm">Bukti pembayaran<input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 block text-sm"></label>
                <p class="text-xs text-white/50">Bukti wajib untuk QRIS / transfer, maksimal 2 MB. Dicatat langsung terverifikasi atas nama admin.</p>
                <button class="rounded-lg bg-[#f2c83f] px-3 py-2 text-xs font-semibold text-black">Catat Pelunasan</button>
            </form>
</main>
@endsection
