@extends('layout.dashboard')
@section('title', $plan->exists ? 'Edit Paket Membership' : 'Tambah Paket Membership')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="mb-6 text-2xl font-bold">{{ $plan->exists ? 'Edit Paket Membership' : 'Tambah Paket Membership' }}</h2>
    <form method="POST" action="{{ $plan->exists ? route('admin.membership-plans.update', $plan) : route('admin.membership-plans.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @if($plan->exists) @method('PUT') @endif
        @php
            $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]';
        @endphp
        @include('admin.memberships.errors')
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="name">Nama paket</label><input id="name" name="name" maxlength="255" value="{{ old('name', $plan->name) }}" required class="{{ $inputClass }}"></div>
            <div><label for="status">Status</label><select id="status" name="status" required class="{{ $inputClass }}">@foreach(['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $plan->status) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="price">Harga (Rp)</label><input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('price', $plan->price) }}" required class="{{ $inputClass }}"></div>
            <div><label for="duration_days">Durasi (hari)</label><input id="duration_days" name="duration_days" type="number" min="1" max="3650" step="1" value="{{ old('duration_days', $plan->duration_days) }}" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label for="description">Deskripsi / manfaat paket</label><textarea id="description" name="description" rows="4" maxlength="10000" class="{{ $inputClass }}">{{ old('description', $plan->description) }}</textarea></div>
        </div>
        <p class="mt-4 text-xs leading-5 text-white/50">Perubahan harga dan durasi berlaku untuk pendaftaran baru. Nonaktifkan paket agar tidak dapat dipilih saat menambah membership.</p>
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.memberships.index', ['tab' => 'plans']) }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button data-save-confirm class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Paket</button></div>
    </form>
</div>
@endsection
