@extends('layout.dashboard')

@section('title', 'Kelola Studio')

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-7 lg:p-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Kelola Studio</h2>
                <p class="mt-2 text-sm text-white/50">Kelola data studio, jam operasional, dan blokir jadwal.</p>
            </div>
            @if($tab === 'studios')
                <a href="{{ route('admin.studios.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">+ Tambah Studio</a>
            @endif
        </div>
        @if(session('success'))
            <div role="status" class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div role="alert" class="rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-300">{{ session('error') }}</div>
        @endif
        <nav class="flex flex-wrap gap-2 border-b border-white/10 pb-3" aria-label="Pengelolaan studio">
            @foreach(['studios' => 'Daftar Studio', 'operations' => 'Operasional Studio'] as $value => $label)
                <a href="{{ route('admin.studios.index', ['tab' => $value]) }}" @if($tab === $value) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold {{ $tab === $value ? 'bg-[#f2c83f] text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>
        @if($tab === 'operations')
            @include('admin.studios.operations')
        @else
        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#171717]">
            <form method="GET" action="{{ route('admin.studios.index') }}" class="flex flex-wrap items-end gap-3 border-b border-white/10 p-5">
                <div class="min-w-0 flex-1">
                    <label for="search" class="mb-2 block text-xs text-white/60">Cari nama studio</label>
                    <input id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari studio..." class="w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-2.5 text-sm outline-none focus:border-[#f2c83f]">
                    @error('search')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-xl border border-[#f2c83f]/40 px-5 py-2.5 text-sm text-[#f2c83f] hover:bg-[#f2c83f]/10">Cari</button>
                @if($search !== '')<a href="{{ route('admin.studios.index') }}" class="px-3 py-2.5 text-sm text-white/60 hover:text-white">Reset</a>@endif
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white/5 text-xs uppercase tracking-wide text-white/45">
                        <tr><th scope="col" class="px-5 py-4">Studio</th><th scope="col" class="px-5 py-4">Kapasitas</th><th scope="col" class="px-5 py-4">Harga / jam</th><th scope="col" class="px-5 py-4">Status</th><th scope="col" class="px-5 py-4 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($studios as $studio)
                            <tr class="hover:bg-white/[.025]">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($studio->image)
                                            <img src="{{ asset('storage/'.$studio->image) }}" alt="Foto {{ $studio->name }}" loading="lazy" class="size-14 shrink-0 rounded-xl object-cover">
                                        @else
                                            <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-white/5 text-center text-[10px] text-white/40">Tanpa foto</span>
                                        @endif
                                        <div><p class="font-medium">{{ $studio->name }}</p><p class="mt-1 max-w-xs text-xs text-white/45">{{ \Illuminate\Support\Str::limit($studio->description, 90) }}</p></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-white/60">{{ $studio->capacity }} orang</td>
                                <td class="whitespace-nowrap px-5 py-4">Rp {{ number_format($studio->price_per_hour, 2, ',', '.') }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs {{ $studio->status === 'active' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-red-400/10 text-red-300' }}">{{ $studio->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.studios.edit', $studio) }}" aria-label="Edit {{ $studio->name }}" class="text-[#f2c83f] hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.studios.destroy', $studio) }}" onsubmit="return confirm('Hapus studio ini? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Hapus {{ $studio->name }}" class="text-red-300 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-white/45">{{ $search !== '' ? 'Tidak ada studio yang sesuai pencarian.' : 'Belum ada studio.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-white/10 p-5">{{ $studios->links() }}</div>
        </section>
        @endif
    </div>
@endsection
