@extends('layout.dashboard')
@section('title', $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="mb-6 text-2xl font-bold">{{ $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class' }}</h2>
    <form method="POST" action="{{ $registration->exists ? route('admin.class-registrations.update', $registration) : route('admin.class-registrations.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @if($registration->exists) @method('PUT') @endif
        @php($inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]')
        @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if($registration->exists)
            <div class="mb-6 space-y-2 rounded-xl bg-white/5 p-4 text-sm">
                <p class="font-semibold">{{ $registration->user->name }} · {{ $registration->user->email }}</p>
                <p>{{ $registration->classSchedule->danceClass->name }} · {{ $registration->classSchedule->studio->name }}</p>
                <p class="text-white/60">{{ \Illuminate\Support\Carbon::parse($registration->classSchedule->date)->format('d/m/Y') }} · {{ substr($registration->classSchedule->start_time, 0, 5) }}–{{ substr($registration->classSchedule->end_time, 0, 5) }}</p>
                <p class="text-[#f2c83f]">Rp {{ number_format($registration->price, 2, ',', '.') }}</p>
            </div>
            @if($registration->payment)
                <div class="mb-6 rounded-xl border border-white/10 p-4 text-sm space-y-3">
                    <p>Metode: {{ \App\Models\Reservation::PAYMENT_METHODS[$registration->payment->payment_method] ?? '-' }}</p>
                    <p>Jenis pembayaran: {{ \App\Models\Reservation::PAYMENT_TYPES[$registration->payment->payment_type] ?? '-' }}</p>
                    <p>Nominal pembayaran: Rp {{ number_format($registration->payment_amount, 2, ',', '.') }}</p>
                    <p>Sisa pelunasan setelah pembayaran ini: Rp {{ number_format($registration->remaining_amount, 2, ',', '.') }}</p>
                    <p>Diverifikasi oleh: {{ $registration->payment->verified_by_name ?: '-' }} @if($registration->payment->verified_at)({{ $registration->payment->verified_at->format('d/m/Y H:i') }})@endif</p>
                    @if($registration->payment->notes)<p>Catatan: {{ $registration->payment->notes }}</p>@endif
                    @if($registration->payment->payment_proof)<a class="inline-block text-[#f2c83f] underline" href="{{ route('admin.class-registrations.payment-proof', $registration) }}" target="_blank" rel="noopener">Lihat bukti pembayaran</a>@else<p class="text-white/50">Belum ada bukti pembayaran.</p>@endif
                </div>
            @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="payment_status" class="text-sm">Status verifikasi</label><select id="payment_status" name="payment_status" class="{{ $inputClass }}" required>@foreach(\App\Models\Reservation::PAYMENT_STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', $registration->payment_status) === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="registration_status" class="text-sm">Status reservasi</label><select id="registration_status" name="registration_status" class="{{ $inputClass }}" required>@foreach(\App\Models\ClassRegistration::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('registration_status', $registration->registration_status) === $value)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <p class="mt-4 text-xs text-white/50">Konfirmasi membutuhkan pembayaran terverifikasi. Pembatalan mengembalikan kuota class. Status pembayaran tidak berubah otomatis saat dibatalkan.</p>
        @else
            <div><label for="user_id" class="text-sm">Peserta</label><select id="user_id" name="user_id" required class="{{ $inputClass }}"><option value="">Pilih peserta</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></div>
            @if($users->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada customer aktif. Tambahkan peserta melalui menu Kelola User.</p>@endif
            <div class="mt-5"><label for="class_schedule_id" class="text-sm">Jadwal class</label><select id="class_schedule_id" name="class_schedule_id" required class="{{ $inputClass }}"><option value="">Pilih jadwal class</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected(old('class_schedule_id') == $schedule->id) @disabled($schedule->occupied >= $schedule->capacity)>{{ $schedule->danceClass->name }} · {{ \Illuminate\Support\Carbon::parse($schedule->date)->format('d/m/Y') }} {{ substr($schedule->start_time, 0, 5) }} · {{ $schedule->studio->name }} · Rp {{ number_format($schedule->danceClass->price, 2, ',', '.') }} · Sisa {{ max(0, $schedule->capacity - $schedule->occupied) }} kursi</option>@endforeach</select></div>
            @if($schedules->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada jadwal class aktif yang akan datang.</p>@endif
            <p class="mt-4 text-xs leading-5 text-white/50">Harga mengikuti class saat reservasi dibuat. Reservasi baru berstatus Menunggu dan Belum Terverifikasi, serta langsung menggunakan satu kursi.</p>
        @endif
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.class-registrations.index') }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Reservasi</button></div>
    </form>
    @if($registration->exists)
        @include('admin.bookings.settlement', ['paymentRecord' => $registration])
    @endif
</div>
@endsection
