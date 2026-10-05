@php
    $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]';
@endphp
<div>

    <p class="mt-2 text-sm text-white/50">Atur jam buka harian dan blokir slot studio untuk pemeliharaan atau kebutuhan lain.</p>

    @if($errors->any())<div role="alert" class="mt-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="mt-6 grid items-start gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
            <h3 class="text-lg font-semibold">Jam Operasional</h3><p class="mt-2 text-sm text-white/50">Berlaku setiap hari untuk semua studio.</p>
            <form method="POST" action="{{ route('admin.studio-operations.hours') }}" class="mt-5">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="opens_at">Jam buka</label><x-time-select id="opens_at" name="opens_at" :value="old('opens_at', $hours['opens_at'])" required class="{{ $inputClass }}" /></div>
                    <div><label for="closes_at">Jam tutup</label><x-time-select id="closes_at" name="closes_at" :value="old('closes_at', $hours['closes_at'])" required class="{{ $inputClass }}" /></div>
                </div>
                <p class="mt-4 text-xs leading-5 text-white/40">Jam tutup harus setelah jam buka pada hari yang sama. Perubahan ditolak jika ada kelas atau booking mendatang di luar jam baru.</p>
                <button class="mt-5 rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Jam Operasional</button>
            </form>
        </section>
        <section class="rounded-2xl border border-white/10 bg-[#171717] p-6">
            <h3 class="text-lg font-semibold">Blokir Jadwal Studio</h3><p class="mt-2 text-sm text-white/50">Blokir mencegah booking dan kelas baru. Waktu yang sudah terisi tidak dapat diblokir.</p>
            <form method="POST" action="{{ route('admin.studio-operations.block') }}" class="mt-5">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="studio_id">Studio</label><select id="studio_id" name="studio_id" required class="{{ $inputClass }}"><option value="">Pilih studio</option>@foreach($studios as $studio)<option value="{{ $studio->id }}" @selected(old('studio_id') == $studio->id)>{{ $studio->name }}</option>@endforeach</select></div>
                    <div><label for="date">Tanggal</label><input id="date" name="date" type="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required class="{{ $inputClass }}"></div>
                    <div><label for="start_time">Mulai blokir</label><x-time-select id="start_time" name="start_time" :value="old('start_time', $hours['opens_at'])" required class="{{ $inputClass }}" /></div>
                    <div><label for="end_time">Selesai blokir</label><x-time-select id="end_time" name="end_time" :value="old('end_time', $hours['closes_at'])" required class="{{ $inputClass }}" /></div>
                    <div class="sm:col-span-2"><label for="block_reason">Alasan blokir</label><textarea id="block_reason" name="block_reason" rows="2" maxlength="500" required class="{{ $inputClass }}">{{ old('block_reason') }}</textarea></div>
                </div>
                <button @disabled($studios->isEmpty()) class="mt-5 rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black disabled:opacity-50">Blokir Jadwal</button>
            </form>
        </section>
    </div>
    <h3 class="mb-4 mt-8 text-lg font-semibold">Daftar Blokir Studio</h3>
    <div class="overflow-x-auto rounded-2xl border border-white/10 bg-[#171717]">
        <table class="w-full text-left text-sm"><thead class="border-b border-white/10 text-white/50"><tr><th class="p-4">Studio</th><th class="p-4">Tanggal / Jam</th><th class="p-4">Alasan</th><th class="p-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-white/10">
                @forelse($blocks as $block)
                    <tr><td class="p-4">{{ $block->studio->name }}</td><td class="whitespace-nowrap p-4">{{ \Illuminate\Support\Carbon::parse($block->date)->format('d/m/Y') }}<p class="mt-1 text-xs text-white/50">{{ substr($block->start_time, 0, 5) }} &ndash; {{ substr($block->end_time, 0, 5) }}</p></td><td class="max-w-sm break-words p-4 text-white/60">{{ $block->block_reason ?: '-' }}</td><td class="p-4"><form method="POST" action="{{ route('admin.studio-operations.unblock', $block) }}">@csrf @method('PATCH')<button class="whitespace-nowrap rounded-xl border border-[#f2c83f]/30 px-3 py-2 text-[#f2c83f]">Buka Blokir</button></form></td></tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-white/50">Belum ada jadwal studio yang diblokir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $blocks->links() }}</div>
</div>