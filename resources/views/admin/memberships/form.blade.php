@extends('layout.dashboard')
@section('title', $membership->exists ? 'Edit Membership' : 'Tambah Membership')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="mb-6 text-2xl font-bold">{{ $membership->exists ? 'Edit Membership' : 'Tambah Membership' }}</h2>
    <form method="POST" enctype="multipart/form-data" action="{{ $membership->exists ? route('admin.memberships.update', $membership) : route('admin.memberships.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @if($membership->exists) @method('PUT') @endif
        @php
            $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]';
        @endphp
        @include('admin.memberships.errors')
        @if(session('success'))<p role="status" class="mb-5 rounded-xl bg-green-400/10 p-4 text-sm text-green-300">{{ session('success') }}</p>@endif
        @if($membership->exists && $membership->payment_proof)
            <a href="{{ route('admin.memberships.payment-proof', $membership) }}" target="_blank" rel="noopener" class="mb-5 inline-block text-sm text-[#f2c83f]">Lihat bukti pembayaran &rarr;</a>
        @endif
        @if($membership->exists)
            <div class="mb-6 space-y-2 rounded-xl bg-white/5 p-4 text-sm"><p class="font-semibold">{{ $membership->user->name }} &middot; {{ $membership->user->email }}</p><p>{{ $membership->membershipPlan->display_name }}</p><p class="text-white/60">{{ $membership->start_date?->format('d/m/Y') ?? '-' }} &ndash; {{ $membership->end_date?->format('d/m/Y') ?? '-' }}</p><p class="text-[#f2c83f]">Rp {{ number_format($membership->price, 2, ',', '.') }}</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="payment_status">Pembayaran</label><select id="payment_status" name="payment_status" required class="{{ $inputClass }}">@foreach(['unpaid' => 'Belum Lunas', 'paid' => 'Lunas'] as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', $membership->payment_status) === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="status">Status membership</label><select id="status" name="status" required class="{{ $inputClass }}">@foreach(\App\Models\Membership::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $membership->effective_status) === $value)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <p class="mt-4 text-xs leading-5 text-white/50">Membership dapat diaktifkan setelah lunas dan tanggal mulai tiba. Untuk perpanjangan, tambahkan membership baru. Pembatalan tidak mengubah status pembayaran secara otomatis.</p>
        @else
            <div><label for="user_id">Customer</label><select id="user_id" name="user_id" required class="{{ $inputClass }}"><option value="">Pilih customer</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} &middot; {{ $user->email }}</option>@endforeach</select></div>
            <div class="mt-5"><label for="membership_plan_id">Paket membership</label><select id="membership_plan_id" name="membership_plan_id" required class="{{ $inputClass }}"><option value="">Pilih paket</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('membership_plan_id') == $plan->id)>{{ $plan->display_name }} &middot; {{ $plan->duration_days }} hari &middot; Rp {{ number_format($plan->price, 2, ',', '.') }}</option>@endforeach</select></div>
            <div class="mt-5"><label for="start_date">Tanggal mulai</label><input id="start_date" name="start_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('start_date', today()->toDateString()) }}" required class="{{ $inputClass }}"></div>
            <div class="mt-5"><label for="payment_proof">Bukti pembayaran</label><input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="{{ $inputClass }}"><p class="mt-2 text-xs text-white/50">Wajib diunggah. JPG, PNG, WEBP, atau PDF, maksimal 2 MB.</p></div>
            <p class="mt-4 text-xs leading-5 text-white/50">Tanggal berakhir dihitung otomatis dari durasi paket, termasuk hari pertama. Membership baru berstatus Menunggu dan Belum Lunas.</p>
            @if($users->isEmpty() || $plans->isEmpty())<p role="status" class="mt-4 text-sm text-[#f2c83f]">Pastikan customer aktif dan paket aktif sudah tersedia sebelum menambahkan membership.</p>@endif
        @endif
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.memberships.index') }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button data-save-confirm class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Membership</button></div>
    </form>
    @if($membership->exists)
        <section class="mt-6 rounded-2xl border border-white/10 bg-[#171717] p-5">
            <h3 class="mb-4 font-semibold">Alihkan ke paket lain</h3>
            @if($plans->isNotEmpty())
                <form method="POST" action="{{ route('admin.memberships.transfer', $membership) }}">
                    @csrf
                    <label for="target_plan_id" class="text-sm">Paket tujuan</label>
                    <select id="target_plan_id" name="target_plan_id" required class="{{ $inputClass }}"><option value="">Pilih paket tujuan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('target_plan_id') == $plan->id)>{{ $plan->display_name }}</option>@endforeach</select>
                    <p class="mt-3 text-xs leading-5 text-white/50">Harga, masa berlaku, pembayaran, dan benefit yang sudah diberikan tetap mengikuti keanggotaan semula.</p>
                    <button data-save-confirm type="submit" class="mt-4 rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Alihkan Member</button>
                </form>
            @else
                <p class="text-sm text-white/50">Belum ada paket aktif lain untuk tujuan pengalihan.</p>
            @endif
        </section>
    @endif
    @if($membership->exists && $membership->status === 'pending')
        <section class="mt-6 rounded-2xl border border-white/10 bg-[#171717] p-5">
            <h3 class="mb-4 font-semibold">Persetujuan Membership</h3>
            @include('admin.memberships.decisions')
        </section>
    @endif
</div>
@endsection
