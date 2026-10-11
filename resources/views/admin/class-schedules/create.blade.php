@extends('layout.dashboard')
@section('title', $schedule->exists ? 'Edit Jadwal Class' : 'Tambah Jadwal Class')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="text-2xl font-bold">{{ $schedule->exists ? 'Edit Jadwal Class' : 'Tambah Jadwal Class' }}</h2>
    <p class="mb-6 mt-2 text-sm text-white/50">Tentukan waktu dan tempat kelas agar peserta bisa melakukan reservasi.</p>
    <form method="POST" action="{{ $schedule->exists ? route('admin.class-schedules.update', $schedule) : route('admin.class-schedules.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @if($schedule->exists) @method('PUT') @endif
        @php
            $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]';
        @endphp
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="dance_class_id" class="text-sm">Kelas</label><select id="dance_class_id" name="dance_class_id" required class="{{ $inputClass }}"><option value="">Pilih kelas</option>@foreach($danceClasses as $danceClass)<option value="{{ $danceClass->id }}" data-capacity="{{ $danceClass->capacity }}" @selected(old('dance_class_id', $schedule->dance_class_id) == $danceClass->id)>{{ $danceClass->name }} (maks. {{ $danceClass->capacity }} peserta)</option>@endforeach</select></div>
            <div><label for="studio_id" class="text-sm">Studio</label><select id="studio_id" name="studio_id" required class="{{ $inputClass }}"><option value="">Pilih studio</option>@foreach($studios as $studio)<option value="{{ $studio->id }}" data-capacity="{{ $studio->capacity }}" @selected(old('studio_id', $schedule->studio_id) == $studio->id)>{{ $studio->name }} (maks. {{ $studio->capacity }} peserta)</option>@endforeach</select></div>
            <div><label for="date" class="text-sm">Tanggal</label><input id="date" name="date" type="date" min="{{ today()->toDateString() }}" value="{{ old('date', $schedule->date) }}" required class="{{ $inputClass }}"></div>
            <div><label for="capacity" class="text-sm">Kuota peserta</label><input id="capacity" name="capacity" aria-describedby="capacity-limit" type="number" min="1" step="1" value="{{ old('capacity', $schedule->capacity) }}" required class="{{ $inputClass }}"><p id="capacity-limit" aria-live="polite" class="mt-2 text-xs text-white/40">Pilih kelas dan studio untuk melihat batas kuota.</p></div>
            <div><label for="start_time" class="text-sm">Jam mulai</label><x-time-select id="start_time" name="start_time" :min="$hours['opens_at']" :max="$hours['closes_at']" :value="old('start_time', substr($schedule->start_time ?? '', 0, 5))" required class="{{ $inputClass }}" /></div>
            <div><label for="end_time" class="text-sm">Jam selesai</label><x-time-select id="end_time" name="end_time" :min="$hours['opens_at']" :max="$hours['closes_at']" :value="old('end_time', substr($schedule->end_time ?? '', 0, 5))" required class="{{ $inputClass }}" /></div>
            @if($schedule->exists)
                <div><label for="status" class="text-sm">Status jadwal</label><select id="status" name="status" class="{{ $inputClass }}">@foreach(['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $schedule->status) === $value)>{{ $label }}</option>@endforeach</select></div>
            @endif
        </div>
        <p class="mt-5 text-xs text-white/50">Jam operasional {{ $hours['opens_at'] }}–{{ $hours['closes_at'] }}. Jadwal nonaktif tidak tersedia untuk reservasi.</p>
        @if($schedule->exists)<p class="mt-3 text-xs text-[#f2c83f]">Jika waktu atau studio berubah, informasikan jadwal baru kepada peserta. Notifikasi tidak dikirim otomatis.</p>@endif
        @if($danceClasses->isEmpty() || $studios->isEmpty())
            <p role="status" class="mt-4 text-sm text-[#f2c83f]">Tambahkan kelas dan studio aktif melalui menu Dance Classes dan Kelola Studio terlebih dahulu.</p>
        @endif
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.class-registrations.index', ['tab' => 'classes']) }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button data-save-confirm type="submit" @disabled($danceClasses->isEmpty() || $studios->isEmpty()) class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black disabled:opacity-50">Simpan Jadwal</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const studio = document.getElementById('studio_id');
        const danceClass = document.getElementById('dance_class_id');
        const capacity = document.getElementById('capacity');
        const hint = document.getElementById('capacity-limit');

        const clampCapacity = () => {
            if (capacity.max && capacity.value !== '' && capacity.valueAsNumber > Number(capacity.max)) {
                capacity.value = capacity.max;
            }
        };

        const updateLimit = () => {
            const studioLimit = Number(studio.selectedOptions[0]?.dataset.capacity);
            const classLimit = Number(danceClass.selectedOptions[0]?.dataset.capacity);
            const limits = [studioLimit, classLimit].filter((value) => Number.isFinite(value) && value > 0);
            if (limits.length === 0) {
                capacity.removeAttribute('max');
                hint.textContent = 'Pilih kelas dan studio untuk melihat batas kuota.';
                return;
            }
            capacity.max = String(Math.min(...limits));
            clampCapacity();
            hint.textContent = `Maksimal ${capacity.max} peserta, mengikuti kapasitas kelas dan studio yang dipilih. Kuota tidak boleh kurang dari peserta terdaftar.`;
        };

        studio.addEventListener('change', updateLimit);
        danceClass.addEventListener('change', updateLimit);
        capacity.addEventListener('input', clampCapacity);
        capacity.addEventListener('change', clampCapacity);
        updateLimit();
    })();
</script>
@endpush
