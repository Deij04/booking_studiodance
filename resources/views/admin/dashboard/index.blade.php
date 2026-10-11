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
    <section class="mt-6 rounded-2xl border border-white/10 bg-[#171717] p-6">
        <p class="text-sm text-white/50">Akumulasi Pendapatan Penyewaan Studio</p>
        <p class="mt-3 text-3xl font-bold text-[#f2c83f]">Rp {{ number_format($bookingMetrics['revenue'], 0, ',', '.') }}</p>
        <p class="mt-2 text-xs text-white/50">Pembayaran awal dan pelunasan yang terverifikasi. Termasuk pembayaran pada booking dibatalkan.</p>
    </section>
    <section class="mt-6 rounded-2xl border border-white/10 bg-[#171717] p-6" data-booking-charts>
        <div class="flex flex-wrap items-center justify-between gap-4"><h2 class="text-lg font-semibold">Grafik Penyewaan Studio</h2><div class="flex flex-wrap gap-2" role="group" aria-label="Periode grafik">@foreach($bookingMetrics['charts'] as $key => $chart)<button type="button" data-chart-period="{{ $key }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="rounded-lg border border-[#f2c83f]/30 px-3 py-2 text-sm text-[#f2c83f]">{{ $chart['label'] }}</button>@endforeach</div></div>
        <p class="mt-2 text-xs text-white/50">Jumlah booking dikonfirmasi dan selesai menurut tanggal penggunaan (WITA). Pengajuan dan pembatalan tidak dihitung.</p>
        @if($errors->any())<div role="alert" class="mt-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="GET" action="{{ route('admin.dashboard') }}" class="mt-5 flex flex-wrap items-end gap-3" data-chart-filter>
            <label class="text-xs text-white/60">Dari tanggal<input type="date" name="from" value="{{ $bookingMetrics['filters']['from'] }}" class="mt-2 block rounded-lg border border-white/15 bg-[#0d0d0d] px-3 py-2 text-sm text-white [color-scheme:dark]"></label>
            <label class="text-xs text-white/60">Sampai tanggal<input type="date" name="to" value="{{ $bookingMetrics['filters']['to'] }}" class="mt-2 block rounded-lg border border-white/15 bg-[#0d0d0d] px-3 py-2 text-sm text-white [color-scheme:dark]"></label>
            <label class="text-xs text-white/60">Bulan<select name="month" class="mt-2 block rounded-lg border border-white/15 bg-[#0d0d0d] px-3 py-2 text-sm text-white"><option value="">Semua bulan</option>@foreach(range(1,12) as $month)<option value="{{ $month }}" @selected($bookingMetrics['filters']['month'] == $month)>{{ \Carbon\Carbon::create(2000, $month, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></label>
            <label class="text-xs text-white/60">Tahun<input type="number" name="year" min="1900" max="2100" value="{{ $bookingMetrics['filters']['year'] }}" class="mt-2 block w-24 rounded-lg border border-white/15 bg-[#0d0d0d] px-3 py-2 text-sm text-white"></label>
            <button class="rounded-lg bg-[#f2c83f] px-4 py-2 text-sm font-semibold text-black">Terapkan</button><a href="{{ route('admin.dashboard') }}" class="px-2 py-2 text-sm text-white/60">Reset</a>
        </form>
        @foreach($bookingMetrics['charts'] as $key => $chart)
            <div data-chart-panel="{{ $key }}" @if(!$loop->first) hidden @endif>
                @php
                    $peak = max(1, max(array_column($chart['points'], 'count')));
                    $chartWidth = max(900, count($chart['points']) * 140);
                @endphp
                <div class="mt-4 overflow-x-auto"><svg viewBox="0 0 {{ $chartWidth }} 260" role="img" aria-label="{{ $chart['label'] }}: {{ $chart['total'] }} penyewaan" style="width:100%;min-width:{{ $chartWidth }}px;height:auto">
                    <title>{{ $chart['label'] }} - Jumlah penyewaan studio</title>
                    @foreach([0, 1, 2] as $tick)
                        @php
                            $y = 210 - $tick * 90;
                        @endphp
                        <line x1="35" y1="{{ $y }}" x2="{{ $chartWidth - 10 }}" y2="{{ $y }}" stroke="#333"/>
                        <text x="25" y="{{ $y + 4 }}" text-anchor="end" fill="#999" font-size="11">{{ round($peak * $tick / 2, 1) }}</text>
                    @endforeach
                    @foreach($chart['points'] as $point)
                        @php
                            $step = ($chartWidth - 50) / count($chart['points']);
                            $x = 40 + $loop->index * $step;
                            $height = $point['count'] / $peak * 180;
                        @endphp
                        <rect data-chart-bar data-chart-label="{{ $point['label'] }}" data-chart-count="{{ $point['count'] }}" tabindex="0" aria-label="{{ $point['label'] }}: {{ $point['count'] }} penyewaan" x="{{ $x }}" y="{{ 210 - $height }}" width="{{ max(8, $step - 8) }}" height="{{ max(1, $height) }}" rx="3" fill="#f2c83f"><title>{{ $point['label'] }}: {{ $point['count'] }} penyewaan</title></rect>
                        <text x="{{ $x + ($step - 8) / 2 }}" y="230" text-anchor="middle" fill="#aaa" font-size="10">{{ $key === 'week' ? substr($point['label'], 0, 10) : $point['label'] }}</text>
                        @if($point['count'])<text x="{{ $x + ($step - 8) / 2 }}" y="{{ 202 - $height }}" text-anchor="middle" fill="#fff" font-size="11">{{ $point['count'] }}</text>@endif
                    @endforeach
                </svg></div>
                @if(!$chart['total'])<p class="mt-2 text-sm text-white/50">Belum ada penyewaan pada periode ini.</p>@endif
            </div>
        @endforeach
    </section>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.bookings.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">+ Tambah Booking</a>
        <a href="{{ route('admin.bookings.index') }}" class="rounded-xl border border-[#f2c83f]/30 px-5 py-3 text-sm text-[#f2c83f]">Kelola Pengajuan</a>
    </div>
    <div id="bookings" class="mt-6 grid gap-6 xl:grid-cols-2">
        @foreach(['requests' => ['Pengajuan Belum Diurus', 'Pengajuan paling lama ditampilkan lebih dahulu.', $pendingReservations], 'active' => ['Jadwal Booking Terdekat', 'Booking dikonfirmasi yang sedang berlangsung atau akan datang.', $upcomingReservations]] as $tab => [$heading, $description, $bookings])
            <section class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">{{ $heading }}</h2><p class="mt-1 text-xs text-white/40">{{ $description }}</p></div><a href="{{ route('admin.bookings.index', ['tab' => $tab]) }}" class="shrink-0 text-xs text-[#f2c83f]">Lihat semua</a></div>
                <div class="mt-5 space-y-3">
                    @forelse($bookings as $reservation)
                        <a href="{{ route('admin.bookings.show', $reservation) }}" class="block rounded-xl border border-white/10 p-4 transition hover:border-[#f2c83f]/40">
                            <div class="flex flex-wrap justify-between gap-2"><h3 class="text-sm font-semibold text-[#f2c83f]">{{ $reservation->booking_code }}</h3><span class="text-xs text-white/50">{{ \App\Models\Booking::STATUSES[$reservation->status] }}</span></div>
                            <p class="mt-2 text-sm">{{ $reservation->user->name }} &middot; {{ $reservation->studio->name }}</p>
                            <p class="mt-1 text-xs text-white/50">{{ \Carbon\Carbon::parse($reservation->date)->format('d/m/Y') }} &middot; {{ substr($reservation->start_time, 0, 5) }}–{{ substr($reservation->end_time, 0, 5) }}</p>
                            <div class="mt-3 flex flex-wrap justify-between gap-2 text-xs"><span class="{{ $reservation->payment_status === 'paid' ? 'text-emerald-300' : 'text-amber-300' }}">{{ \App\Models\Booking::PAYMENT_STATUSES[$reservation->payment_status] }}</span><span class="text-white/50">Rp {{ number_format($reservation->total_price, 0, ',', '.') }} &middot; Buka detail &rarr;</span></div>
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

@push('scripts')
<script>
const chartTooltip = document.createElement('div');
chartTooltip.setAttribute('role', 'tooltip');
chartTooltip.hidden = true;
chartTooltip.style.cssText = 'position:fixed;z-index:1000;pointer-events:none;background:#242016;border:1px solid #f2c83f;border-radius:8px;padding:10px 14px;color:#fff;font-size:13px;max-width:280px';
document.body.append(chartTooltip);
document.querySelectorAll('[data-chart-bar]').forEach(bar => {
    const show = event => {
        chartTooltip.textContent = `${bar.dataset.chartLabel}: ${bar.dataset.chartCount} penyewaan`;
        chartTooltip.hidden = false;
        const rect = bar.getBoundingClientRect();
        const x = event.clientX ?? rect.left;
        const y = event.clientY ?? rect.top;
        chartTooltip.style.left = `${Math.max(8, Math.min(x + 12, innerWidth - chartTooltip.offsetWidth - 8))}px`;
        chartTooltip.style.top = `${Math.max(8, Math.min(y - chartTooltip.offsetHeight - 12, innerHeight - chartTooltip.offsetHeight - 8))}px`;
    };
    bar.addEventListener('pointermove', show); bar.addEventListener('focus', show);
    bar.addEventListener('pointerleave', () => chartTooltip.hidden = true);
    bar.addEventListener('blur', () => chartTooltip.hidden = true);
});
const chartFilter = document.querySelector('[data-chart-filter]');
chartFilter.querySelectorAll('[name="month"], [name="year"]').forEach(input => input.addEventListener('change', () => {
    chartFilter.elements.from.value = ''; chartFilter.elements.to.value = '';
}));
document.querySelectorAll('[data-chart-period]').forEach(button => button.addEventListener('click', () => {
    chartTooltip.hidden = true;
    document.querySelectorAll('[data-chart-panel]').forEach(panel => panel.hidden = panel.dataset.chartPanel !== button.dataset.chartPeriod);
    document.querySelectorAll('[data-chart-period]').forEach(tab => tab.setAttribute('aria-pressed', String(tab === button)));
}));
</script>
@endpush
