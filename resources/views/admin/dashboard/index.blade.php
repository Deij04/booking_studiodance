@extends('layout.dashboard')

@section('content')
<div class="mx-auto max-w-[1500px] p-4 sm:p-7 lg:p-10">
    <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#171717] p-6 sm:p-8">
        <div class="absolute inset-y-0 right-0 hidden w-2/5 bg-[url('/images/Login.jpg')] bg-cover bg-center opacity-25 [mask-image:linear-gradient(to_right,transparent,black)] md:block"></div>
        <div class="relative max-w-2xl">
            <span class="inline-flex rounded-full border border-[#f2c83f]/25 bg-[#f2c83f]/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[.18em] text-[#f2c83f]">Orange Dance Academy</span>
            <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">Keep moving, keep growing.</h2>
            <p class="mt-2 max-w-xl text-sm leading-6 text-white/50">Pantau pengajuan pelanggan dan jadwal booking studio dalam satu tempat.</p>
            <a href="#classes" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black transition hover:bg-[#ffda58]">Explore Classes <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-white/10 bg-[#171717] p-5"><p class="text-sm text-white/50">Total Booking</p><p class="mt-4 text-3xl font-bold">{{ $reservationCount }}</p><p class="mt-2 text-xs text-white/40">Seluruh booking pelanggan</p></article>
        @foreach(['requests' => ['Pengajuan Booking', $pendingCount, 'Perlu ditinjau'], 'active' => ['Booking Aktif', $confirmedCount, 'Sudah dikonfirmasi'], 'history' => ['Riwayat Booking', $historyCount, 'Selesai atau dibatalkan']] as $tab => [$label, $count, $description])
            <a href="{{ route('admin.bookings.index', ['tab' => $tab]) }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 transition hover:border-[#f2c83f]/50"><p class="text-sm text-white/50">{{ $label }}</p><p class="mt-4 text-3xl font-bold text-[#f2c83f]">{{ $count }}</p><p class="mt-2 text-xs text-white/40">{{ $description }} &rarr;</p></a>
        @endforeach
    </section>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.bookings.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">+ Tambah Booking</a>
        <a href="{{ route('admin.bookings.index') }}" class="rounded-xl border border-[#f2c83f]/30 px-5 py-3 text-sm text-[#f2c83f]">Kelola Pengajuan</a>
    </div>
    <div id="bookings" class="mt-6 grid gap-6 xl:grid-cols-2">
        @foreach(['requests' => ['Pengajuan Belum Diurus', 'Pengajuan paling lama ditampilkan lebih dahulu.', $pendingReservations], 'active' => ['Jadwal Booking Terdekat', 'Booking dikonfirmasi yang sedang berlangsung atau akan datang.', $upcomingReservations]] as $tab => [$heading, $description, $reservations])
            <section class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">{{ $heading }}</h2><p class="mt-1 text-xs text-white/40">{{ $description }}</p></div><a href="{{ route('admin.bookings.index', ['tab' => $tab]) }}" class="shrink-0 text-xs text-[#f2c83f]">Lihat semua</a></div>
                <div class="mt-5 space-y-3">
                    @forelse($reservations as $reservation)
                        <a href="{{ route('admin.bookings.edit', $reservation) }}" class="block rounded-xl border border-white/10 p-4 transition hover:border-[#f2c83f]/40">
                            <div class="flex flex-wrap justify-between gap-2"><h3 class="text-sm font-semibold text-[#f2c83f]">{{ $reservation->booking_code }}</h3><span class="text-xs text-white/50">{{ \App\Models\Reservation::STATUSES[$reservation->status] }}</span></div>
                            <p class="mt-2 text-sm">{{ $reservation->user->name }} &middot; {{ $reservation->studioSchedule->studio->name }}</p>
                            <p class="mt-1 text-xs text-white/50">{{ \Carbon\Carbon::parse($reservation->studioSchedule->date)->format('d/m/Y') }} &middot; {{ substr($reservation->studioSchedule->start_time, 0, 5) }}–{{ substr($reservation->studioSchedule->end_time, 0, 5) }}</p>
                            <div class="mt-3 flex flex-wrap justify-between gap-2 text-xs"><span class="{{ $reservation->payment_status === 'paid' ? 'text-emerald-300' : 'text-amber-300' }}">{{ \App\Models\Reservation::PAYMENT_STATUSES[$reservation->payment_status] }}</span><span class="text-white/50">Rp {{ number_format($reservation->total_price, 0, ',', '.') }} &middot; Buka detail &rarr;</span></div>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-white/45">{{ $tab === 'requests' ? 'Semua pengajuan sudah diurus.' : 'Belum ada jadwal booking mendatang.' }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    <div class="mt-6 grid gap-6 ">
        <section id="classes" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div><h2 class="text-lg font-semibold">Upcoming Classes</h2><p class="mt-1 text-xs text-white/40">Jadwal kelas terdekat</p></div>
                <a href="{{ route('admin.dance-classes.index') }}" class="text-xs font-medium text-[#f2c83f]">View all</a>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($upcomingClasses as $schedule)
                    <article class="flex items-center gap-4 rounded-xl border border-white/[.07] bg-white/[.025] p-3.5">
                        <div class="grid min-w-12 place-items-center rounded-xl bg-[#f2c83f]/10 px-2 py-2 text-center"><span class="text-[9px] uppercase text-[#f2c83f]">{{ \Carbon\Carbon::parse($schedule->date)->format('M') }}</span><strong class="text-lg leading-5">{{ \Carbon\Carbon::parse($schedule->date)->format('d') }}</strong></div>
                        <div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold">{{ $schedule->danceClass->name }}</h3><p class="mt-1 truncate text-[11px] text-white/40">{{ $schedule->studio->name }} · {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</p></div>
                        <span class="hidden rounded-lg bg-white/5 px-2.5 py-1.5 text-[10px] capitalize text-white/50 sm:block">{{ $schedule->danceClass->level }}</span>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-white/10 px-4 py-10 text-center"><p class="text-sm text-white/45">Belum ada jadwal kelas mendatang.</p></div>
                @endforelse
            </div>
        </section>


    </div>
</div>
@endsection
