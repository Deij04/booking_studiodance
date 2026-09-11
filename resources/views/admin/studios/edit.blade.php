@extends('layout.dashboard')

@section('title', 'Edit Studio')

@section('content')
    <div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
        <h2 class="text-2xl font-bold">Edit Studio</h2>
        <p class="mt-2 mb-6 text-sm text-white/50">Perbarui studio {{ $studio->name }}.</p>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.studios.update', $studio) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
            @csrf
            @method('PUT')
            @php
                $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white outline-none focus:border-[#f2c83f]';
            @endphp

            @if($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-300">
                    <p class="font-semibold">Periksa kembali data berikut:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="text-sm text-white/70">Nama studio *</label>
                    <input id="name" name="name" value="{{ old('name', $studio->name) }}" required maxlength="255" class="{{ $inputClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="text-sm text-white/70">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" maxlength="10000" class="{{ $inputClass }}">{{ old('description', $studio->description) }}</textarea>
                </div>
                <div>
                    <label for="capacity" class="text-sm text-white/70">Kapasitas (orang) *</label>
                    <input id="capacity" type="number" name="capacity" value="{{ old('capacity', $studio->capacity) }}" required min="1" max="4294967295" step="1" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="price_per_hour" class="text-sm text-white/70">Harga per jam (Rp) *</label>
                    <input id="price_per_hour" type="number" name="price_per_hour" value="{{ old('price_per_hour', $studio->price_per_hour) }}" required min="0" max="9999999999.99" step="0.01" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="status" class="text-sm text-white/70">Status *</label>
                    <select id="status" name="status" required class="{{ $inputClass }}">
                        <option value="active" @selected(old('status', $studio->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $studio->status) === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="image" class="text-sm text-white/70">Foto studio (opsional)</label>
                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="image-help" class="{{ $inputClass }}">
                    <p id="image-help" class="mt-2 text-xs text-white/45">JPG, PNG, atau WebP, maksimal 2 MB. Kosongkan untuk mempertahankan foto saat edit.</p>
                    @if($studio->image)
                        <img src="{{ asset('storage/'.$studio->image) }}" alt="Foto {{ $studio->name }}" class="mt-4 h-40 w-64 rounded-xl object-cover">
                        <label class="mt-3 flex items-center gap-2 text-sm text-white/60"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))> Hapus foto saat ini</label>
                    @endif
                </div>
            </div>
            <div class="mt-7 flex items-center justify-end gap-4 border-t border-white/10 pt-6">
                <a href="{{ route('admin.studios.index') }}" class="text-sm text-white/60 hover:text-white">Batal</a>
                <button type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">{{ $studio->exists ? 'Simpan Perubahan' : 'Tambah Studio' }}</button>
            </div>
        </form>
    </div>
@endsection
