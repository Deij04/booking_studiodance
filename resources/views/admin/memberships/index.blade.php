@extends('layout.dashboard')
@section('title', 'Membership')
@section('content')
@php
    $tab = request('tab') === 'plans' ? 'plans' : 'members';
@endphp
<div class="p-4 sm:p-7 lg:p-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Membership</h2><p class="mt-2 text-sm text-white/50">Kelola paket, periode keanggotaan, dan pembayaran member.</p></div>
        <a href="{{ route($tab === 'plans' ? 'admin.membership-plans.create' : 'admin.memberships.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">{{ $tab === 'plans' ? 'Tambah Paket' : 'Tambah Membership' }}</a>
    </div>
    @if(session('success'))<div role="status" class="mt-6 rounded-xl bg-green-400/10 p-4 text-sm text-green-300">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mt-6 text-sm text-red-300">{{ $errors->first() }}</div>@endif
    <nav class="mt-6 flex gap-2 border-b border-white/10 pb-3" aria-label="Tampilan membership">
        @foreach(['members' => 'Daftar Member', 'plans' => 'Paket Membership'] as $value => $label)
            <a href="{{ route('admin.memberships.index', ['tab' => $value]) }}" @if($tab === $value) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold {{ $tab === $value ? 'bg-[#f2c83f] text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </nav>
    @if($tab === 'plans')
        <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($plans as $plan)
                <article class="flex flex-col rounded-2xl border border-white/10 bg-[#171717] p-6">
                    <div class="flex items-start justify-between gap-3"><h3 class="text-xl font-bold">{{ $plan->name }}</h3><span class="rounded-full bg-white/10 px-3 py-1 text-xs text-white/60">{{ $plan->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></div>
                    <p class="mt-6 text-2xl font-bold text-[#f2c83f]">Rp {{ number_format($plan->price, 2, ',', '.') }}</p><p class="mt-1 text-sm text-white/50">Masa berlaku {{ $plan->duration_days }} hari</p>
                    <p class="my-5 whitespace-pre-line break-words text-sm leading-6 text-white/60">{{ $plan->description ?: 'Belum ada deskripsi paket.' }}</p>
                    <a href="{{ route('admin.membership-plans.edit', $plan) }}" class="mt-auto border-t border-white/10 pt-4 text-sm font-semibold text-[#f2c83f]">Edit Paket</a>
                </article>
            @empty
                <p class="p-8 text-sm text-white/50">Belum ada paket membership.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $plans->links() }}</div>
    @else
        <form method="GET" class="my-6 flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="members">
            <div class="flex-1"><label for="search" class="text-sm text-white/60">Cari member atau paket</label><input id="search" name="search" value="{{ $search }}" placeholder="Nama, email, atau paket" class="mt-2 w-full rounded-xl border border-white/15 bg-[#171717] px-4 py-3 text-sm"></div>
            <div><label for="status" class="text-sm text-white/60">Status</label><select id="status" name="status" class="mt-2 block rounded-xl border border-white/15 bg-[#171717] px-4 py-3 text-sm"><option value="">Semua status</option>@foreach(\App\Models\Membership::STATUSES as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="rounded-xl bg-white/10 px-5 py-3 text-sm">Cari</button><a href="{{ route('admin.memberships.index') }}" class="px-3 py-3 text-sm text-white/60">Reset</a>
        </form>
        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-[#171717]"><table class="w-full whitespace-nowrap text-left text-sm">
            <thead class="border-b border-white/10 text-white/50"><tr><th class="p-4">Member</th><th class="p-4">Paket</th><th class="p-4">Periode</th><th class="p-4">Harga</th><th class="p-4">Pembayaran</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-white/10">
                @forelse($memberships as $membership)
                    <tr><td class="p-4"><p class="font-semibold">{{ $membership->user->name }}</p><p class="mt-1 text-xs text-white/50">{{ $membership->user->email }}</p></td><td class="p-4">{{ $membership->membershipPlan->name }}</td><td class="p-4">{{ $membership->start_date?->format('d/m/Y') ?? '-' }} &ndash; {{ $membership->end_date?->format('d/m/Y') ?? '-' }}</td><td class="p-4">Rp {{ number_format($membership->price, 2, ',', '.') }}</td><td class="p-4 {{ $membership->payment_status === 'paid' ? 'text-green-300' : 'text-[#f2c83f]' }}">{{ $membership->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</td><td class="p-4">{{ \App\Models\Membership::STATUSES[$membership->effective_status] }}</td><td class="p-4"><a href="{{ route('admin.memberships.edit', $membership) }}" class="text-[#f2c83f]">Edit</a></td></tr>
                    @if($membership->status === 'pending')
                        <tr><td colspan="7" class="px-4 pb-4 pt-2">@include('admin.memberships.decisions')</td></tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-white/50">{{ $search || $status ? 'Tidak ada member yang sesuai pencarian.' : 'Belum ada membership.' }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-6">{{ $memberships->links() }}</div>
    @endif
</div>
@endsection
