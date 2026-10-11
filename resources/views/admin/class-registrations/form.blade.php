@extends('layout.dashboard')
@section('title', $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="mb-6 text-2xl font-bold">{{ $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class' }}</h2>
    <form method="POST" action="{{ $registration->exists ? route('admin.class-registrations.update', $registration) : route('admin.class-registrations.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @php($inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]')
        @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div><label for="user_id" class="text-sm">Peserta</label><select id="user_id" name="user_id" required class="{{ $inputClass }}"><option value="">Pilih peserta</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></div>
            @if($users->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada customer aktif. Tambahkan peserta melalui menu Kelola User.</p>@endif
            <div class="mt-5"><label for="class_schedule_id" class="text-sm">Jadwal class</label><select id="class_schedule_id" name="class_schedule_id" required class="{{ $inputClass }}"><option value="">Pilih jadwal class</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected(old('class_schedule_id') == $schedule->id) @disabled($schedule->occupied >= $schedule->capacity)>{{ $schedule->danceClass->name }} · {{ \Illuminate\Support\Carbon::parse($schedule->date)->format('d/m/Y') }} {{ substr($schedule->start_time, 0, 5) }} · {{ $schedule->studio->name }} · Rp {{ number_format($schedule->danceClass->price, 2, ',', '.') }} · Sisa {{ max(0, $schedule->capacity - $schedule->occupied) }} kursi</option>@endforeach</select></div>
            @if($schedules->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada jadwal class aktif yang akan datang.</p>@endif
            <p class="mt-4 text-xs leading-5 text-white/50">Harga mengikuti class saat reservasi dibuat. Reservasi baru berstatus Menunggu dan Belum Terverifikasi, serta langsung menggunakan satu kursi.</p>
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.class-registrations.index') }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button data-save-confirm type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Reservasi</button></div>
    </form>

</div>
@endsection
