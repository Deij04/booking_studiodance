@extends('layout.dashboard')
@section('title', 'Edit Reservasi Class')
@section('content')
<main class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
    <a href="{{ route('admin.class-registrations.index') }}" class="text-sm text-[#f2c83f]">Kembali ke reservasi class</a>
    <h2 class="text-2xl font-bold">Edit Reservasi Class</h2>
    <section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
        <p class="font-semibold">{{ $registration->user->name }} &middot; {{ $registration->classSchedule->danceClass->name }}</p>
        <p class="mt-2 text-sm text-white/60">Jadwal asal: {{ $registration->classSchedule->date }} &middot; {{ substr($registration->classSchedule->start_time,0,5) }} WITA</p>
        <p class="mt-2 text-sm text-white/60">Peserta dan jadwal dapat dikoreksi. Pembayaran tetap tercatat. Kelas tujuan harus masih berlangsung atau belum dimulai. Selisih biaya yang lebih tinggi perlu dilunasi.</p>
    </section>
    @if($errors->any())<div role="alert" class="text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.class-registrations.update', $registration) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-6">@csrf @method('PUT')
        <div class="grid gap-5 sm:grid-cols-2">
        <label class="text-sm">Peserta<select name="user_id" required class="mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm">@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id', $registration->user_id) == $user->id)>{{ $user->name }} &middot; {{ $user->email }}</option>@endforeach</select></label>
        <label class="text-sm">Jadwal class<select name="class_schedule_id" required class="mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm">@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected(old('class_schedule_id', $registration->class_schedule_id) == $schedule->id) @disabled($schedule->id !== $registration->class_schedule_id && $schedule->occupied >= $schedule->capacity)>{{ $schedule->danceClass->name }} &middot; Rp {{ number_format($schedule->danceClass->price, 0, ',', '.') }} &middot; {{ $schedule->date }} &middot; {{ substr($schedule->start_time,0,5) }} - {{ substr($schedule->end_time,0,5) }} WITA &middot; {{ $schedule->studio->name }} &middot; Sisa {{ max(0, $schedule->capacity - $schedule->occupied) }} kursi</option>@endforeach</select></label>
        </div>
        @if($schedules->isEmpty())<p class="mt-3 text-sm text-white/60">Belum ada jadwal tujuan yang tersedia.</p>@endif
        <button data-save-confirm class="mt-5 rounded-xl bg-[#f2c83f] px-5 py-3 font-semibold text-black" @disabled($schedules->isEmpty())>Simpan Perubahan</button>
    </form>
</main>
@endsection
