@extends('layout.dashboard')
@section('title', 'Reservasi Class')
@section('content')
<div class="p-4 sm:p-7 lg:p-10">
    @php
        $activeTab = request('tab') === 'classes' ? 'classes' : 'reservations';
    @endphp
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><h2 class="text-2xl font-bold">Reservasi Class</h2><p class="mt-2 text-sm text-white/50">Kelola peserta, pembayaran, dan status reservasi dance class.</p></div>
        <a href="{{ route($activeTab === 'classes' ? 'admin.class-schedules.create' : 'admin.class-registrations.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">{{ $activeTab === 'classes' ? 'Tambah Jadwal Class' : 'Tambah Reservasi' }}</a>
    </div>
    @if(session('success'))<div role="status" class="mt-6 rounded-xl bg-green-400/10 p-4 text-sm text-green-300">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mt-6 text-red-300">{{ $errors->first() }}</div>@endif
    <nav class="mt-6 flex gap-2 border-b border-white/10 pb-3" aria-label="Tampilan reservasi class">
        @foreach(['reservations' => 'Daftar Reservasi', 'classes' => 'Jadwal Class'] as $tab => $label)
            <a href="{{ route('admin.class-registrations.index', array_merge(request()->except('tab'), ['tab' => $tab])) }}" @if($activeTab === $tab) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold {{ $activeTab === $tab ? 'bg-[#f2c83f] text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </nav>
    @if($activeTab === 'classes')
    <section class="mt-8" aria-labelledby="class-schedules-heading">
        <div class="mb-5"><h3 id="class-schedules-heading" class="text-lg font-semibold">Jadwal &amp; Peserta Class</h3><p class="mt-1 text-sm text-white/50">Lihat jadwal kelas dan peserta setiap sesi. Jadwal yang akan datang ditampilkan lebih dahulu.</p></div>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($schedules as $schedule)
                @php
                    $participants = $schedule->classRegistrations->where('registration_status', '!=', 'cancelled');
                    $cancelled = $schedule->classRegistrations->where('registration_status', 'cancelled');
                    $ended = \Illuminate\Support\Carbon::parse($schedule->date.' '.$schedule->end_time)->isPast();
                @endphp
                <article class="min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-[#171717]">
                    <div class="border-b border-white/10 bg-gradient-to-br from-[#f2c83f]/10 to-transparent p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-medium uppercase tracking-wide text-[#f2c83f]">{{ \App\Models\DanceClass::LEVELS[$schedule->danceClass->level] ?? $schedule->danceClass->level }}</p><h4 class="mt-2 text-xl font-bold">{{ $schedule->danceClass->name }}</h4></div>
                            <span class="shrink-0 rounded-full bg-white/10 px-3 py-1 text-xs text-white/70">{{ $schedule->status !== 'active' ? 'Nonaktif' : ($ended ? 'Sudah berakhir' : 'Terjadwal') }}</span>
                        </div>
                        <p class="mt-4 font-semibold text-[#f2c83f]">{{ \Illuminate\Support\Carbon::parse($schedule->date)->locale('id')->translatedFormat('l, d F Y') }}</p>
                        <p class="mt-1 text-sm text-white/70">{{ substr($schedule->start_time, 0, 5) }} &ndash; {{ substr($schedule->end_time, 0, 5) }} &middot; {{ $schedule->studio->name }}</p>
                    </div>
                    <div class="p-5">
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            @if(\Illuminate\Support\Carbon::parse($schedule->date.' '.$schedule->start_time)->isFuture())
                                <a href="{{ route('admin.class-schedules.edit', $schedule) }}" class="rounded-xl border border-[#f2c83f]/30 px-3 py-2 text-sm text-[#f2c83f]">{{ $schedule->status === 'active' ? 'Edit Jadwal' : 'Edit / Aktifkan' }}</a>
                            @endif
                            @if($schedule->status === 'active')
                                <form method="POST" action="{{ route('admin.class-schedules.deactivate', $schedule) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-xl border border-red-400/30 px-3 py-2 text-sm text-red-300">Nonaktifkan</button>
                                </form>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="font-semibold">{{ $schedule->occupied }} / {{ $schedule->capacity }} peserta</span><span class="text-white/50">{{ max(0, $schedule->capacity - $schedule->occupied) }} kursi tersisa</span></div>
                        <details class="group mt-4" open>
                            <summary class="cursor-pointer text-sm font-medium text-[#f2c83f]">Daftar peserta ({{ $participants->count() }})</summary>
                            <ul class="mt-3 max-h-64 space-y-3 overflow-y-auto" aria-label="Peserta {{ $schedule->danceClass->name }}">
                                @forelse($participants as $participant)
                                    <li class="flex items-center justify-between gap-3 rounded-xl bg-white/5 p-3">
                                        <div class="min-w-0"><p class="break-words text-sm font-medium">{{ $participant->user->name }}</p><p class="mt-1 text-xs text-white/50">{{ \App\Models\ClassRegistration::STATUSES[$participant->registration_status] }} &middot; {{ $participant->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</p></div>
                                        <a href="{{ route('admin.class-registrations.edit', $participant) }}" class="shrink-0 text-xs text-[#f2c83f] hover:underline" aria-label="Edit reservasi {{ $participant->user->name }}">Edit</a>
                                    </li>
                                @empty
                                    <li class="rounded-xl border border-dashed border-white/10 p-4 text-sm text-white/40">Belum ada peserta yang ikut.</li>
                                @endforelse
                            </ul>
                        </details>
                        @if($cancelled->isNotEmpty())
                            <details class="mt-4 border-t border-white/10 pt-3"><summary class="cursor-pointer text-xs text-white/40">Dibatalkan ({{ $cancelled->count() }})</summary><ul class="mt-3 space-y-2">@foreach($cancelled as $participant)<li class="flex items-center justify-between gap-3 text-xs text-white/50"><span>{{ $participant->user->name }}</span><a href="{{ route('admin.class-registrations.edit', $participant) }}" class="text-[#f2c83f]">Edit</a></li>@endforeach</ul></details>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-white/15 p-8 text-center text-sm text-white/50 md:col-span-2 xl:col-span-3">Belum ada jadwal class. Card kelas akan tampil setelah jadwal tersedia.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $schedules->links() }}</div>
    </section>
    @else
    <h3 class="mt-10 text-lg font-semibold">Daftar Reservasi</h3>
    <form method="GET" class="my-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="reservations">
        <div class="flex-1"><label for="search" class="text-sm text-white/60">Cari peserta atau class</label><input id="search" name="search" value="{{ $search }}" placeholder="Nama, email, atau nama class" class="mt-2 w-full rounded-xl border border-white/15 bg-[#171717] px-4 py-3 text-sm"></div>
        <div><label for="status" class="text-sm text-white/60">Status reservasi</label><select id="status" name="status" class="mt-2 block rounded-xl border border-white/15 bg-[#171717] px-4 py-3 text-sm"><option value="">Semua status</option>@foreach(\App\Models\ClassRegistration::STATUSES as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="rounded-xl bg-white/10 px-5 py-3 text-sm">Cari</button><a href="{{ route('admin.class-registrations.index') }}" class="px-3 py-3 text-sm text-white/60">Reset</a>
    </form>
    <div class="overflow-x-auto rounded-2xl border border-white/10 bg-[#171717]">
        <table class="w-full whitespace-nowrap text-left text-sm">
            <thead class="border-b border-white/10 text-white/50"><tr><th class="p-4">Peserta</th><th class="p-4">Class / Jadwal</th><th class="p-4">Harga</th><th class="p-4">Pembayaran</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-white/10">
                @forelse($registrations as $registration)
                    <tr>
                        <td class="p-4"><p class="font-semibold">{{ $registration->user->name }}</p><p class="mt-1 text-xs text-white/50">{{ $registration->user->email }}</p></td>
                        <td class="p-4"><p>{{ $registration->classSchedule->danceClass->name }}</p><p class="mt-1 text-xs text-white/50">{{ \Illuminate\Support\Carbon::parse($registration->classSchedule->date)->format('d/m/Y') }} · {{ substr($registration->classSchedule->start_time, 0, 5) }}–{{ substr($registration->classSchedule->end_time, 0, 5) }}</p><p class="mt-1 text-xs text-white/50">{{ $registration->classSchedule->studio->name }}</p></td>
                        <td class="p-4">Rp {{ number_format($registration->price, 2, ',', '.') }}</td>
                        <td class="p-4 {{ $registration->payment_status === 'paid' ? 'text-green-300' : 'text-[#f2c83f]' }}">{{ $registration->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</td>
                        <td class="p-4">{{ \App\Models\ClassRegistration::STATUSES[$registration->registration_status] }}</td>
                        <td class="p-4"><a href="{{ route('admin.class-registrations.edit', $registration) }}" class="text-[#f2c83f] hover:underline">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-white/50">{{ $search !== '' || $status !== '' ? 'Tidak ada reservasi yang sesuai pencarian.' : 'Belum ada reservasi class.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $registrations->links() }}</div>
    @endif
</div>
@endsection
