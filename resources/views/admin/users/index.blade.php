@extends('layout.dashboard')

@section('title', 'Kelola User')

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-7 lg:p-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Kelola User</h2>
                <p class="mt-2 text-sm text-white/50">{{ auth()->user()->role === 'super_admin' ? 'Kelola akun user, admin, dan super admin.' : 'Kelola akun user (customer).' }}</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">+ Tambah User</a>
        </div>

        @if(session('success'))
            <div role="status" class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div role="alert" class="rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-300">{{ session('error') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#171717]">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3 border-b border-white/10 p-5">
                <div class="min-w-0 flex-1">
                    <label for="search" class="mb-2 block text-xs text-white/60">Cari nama atau email</label>
                    <input id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari user..." class="w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-2.5 text-sm outline-none focus:border-[#f2c83f]">
                    @error('search') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-xl border border-[#f2c83f]/40 px-5 py-2.5 text-sm text-[#f2c83f] hover:bg-[#f2c83f]/10">Cari</button>
                @if($search !== '')
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2.5 text-sm text-white/60 hover:text-white">Reset</a>
                @endif
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white/5 text-xs uppercase tracking-wide text-white/45">
                        <tr><th scope="col" class="px-5 py-4">Nama / Email</th><th scope="col" class="px-5 py-4">Telepon</th><th scope="col" class="px-5 py-4">Role</th><th scope="col" class="px-5 py-4">Status</th><th scope="col" class="px-5 py-4 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($users as $managedUser)
                            <tr class="hover:bg-white/[.025]">
                                <td class="px-5 py-4"><p class="font-medium">{{ $managedUser->name }}</p><p class="mt-1 text-xs text-white/45">{{ $managedUser->email }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-white/60">{{ $managedUser->phone ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ ['customer' => 'User', 'admin' => 'Admin', 'super_admin' => 'Super Admin'][$managedUser->role] }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs {{ $managedUser->status === 'active' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-red-400/10 text-red-300' }}">{{ $managedUser->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3">
                                        @can('update', $managedUser)
                                            <a href="{{ route('admin.users.edit', $managedUser) }}" aria-label="Edit {{ $managedUser->name }}" class="text-[#f2c83f] hover:underline">Edit</a>
                                        @endcan
                                        @can('delete', $managedUser)
                                            <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" onsubmit="return confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Hapus {{ $managedUser->name }}" class="text-red-300 hover:underline">Hapus</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-white/45">{{ $search !== '' ? 'Tidak ada user yang sesuai pencarian.' : 'Belum ada user.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-white/10 p-5">{{ $users->links() }}</div>
        </section>
    </div>
@endsection
