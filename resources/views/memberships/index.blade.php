@extends('layout.landing')
@section('content')
<main class="section">
    <h2 class="section-title">Membership Saya</h2>
    <p class="sub">Pilih paket, ajukan membership, lalu hubungi admin untuk pembayaran. Membership aktif setelah pembayaran diperiksa dan pengajuan disetujui.</p>
    @if(session('success'))<p role="status" class="info-pill">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="info-pill"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="class-grid">
        @forelse($plans as $plan)
            <article class="class-card"><div class="class-body">
                <h3>{{ $plan->name }}</h3>
                <p style="color:var(--gold);font-size:22px;font-weight:700">Rp {{ number_format($plan->price, 2, ',', '.') }}</p>
                <p>{{ $plan->duration_days }} hari</p>
                <p style="white-space:pre-line;margin:16px 0;overflow-wrap:anywhere">{{ $plan->description }}</p>
                <form method="POST" action="{{ route('memberships.store') }}">
                    @csrf
                    <input type="hidden" name="membership_plan_id" value="{{ $plan->id }}">
                    <label for="start-date-{{ $plan->id }}" class="small">Tanggal mulai</label>
                    <input id="start-date-{{ $plan->id }}" name="start_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('membership_plan_id') == $plan->id ? old('start_date', today()->toDateString()) : today()->toDateString() }}" required style="display:block;width:100%;padding:12px;margin:8px 0 16px;border:1px solid #555;border-radius:12px;background:#111;color:var(--text);color-scheme:dark">
                    <button class="btn btn-fill" type="submit" style="cursor:pointer">Ajukan Membership</button>
                </form>
            </div></article>
        @empty
            <p>Belum ada paket membership aktif.</p>
        @endforelse
    </div>
    <div class="bar"></div>
    <h2>Riwayat Pengajuan</h2>
    <p class="small">Tanggal akhir termasuk dalam masa berlaku. Pengajuan dengan tanggal mulai mendatang dapat diaktifkan oleh admin setelah tanggal tersebut tiba.</p>
    @forelse($memberships as $membership)
        <article class="faq-item">
            <h3>{{ $membership->membershipPlan->name }}</h3>
            <p class="small">{{ $membership->start_date?->format('d/m/Y') ?? '-' }} &ndash; {{ $membership->end_date?->format('d/m/Y') ?? '-' }} &middot; Rp {{ number_format($membership->price, 2, ',', '.') }}</p>
            <p class="small"><strong>{{ $membership->effective_status === 'cancelled' ? 'Ditolak / Dibatalkan' : \App\Models\Membership::STATUSES[$membership->effective_status] }}</strong> &middot; {{ $membership->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</p>
        </article>
    @empty
        <p>Belum ada pengajuan membership.</p>
    @endforelse
    <nav aria-label="Halaman riwayat membership" style="display:flex;justify-content:space-between;gap:16px;margin-top:20px">
        @if($memberships->previousPageUrl())<a class="btn" href="{{ $memberships->previousPageUrl() }}">Sebelumnya</a>@endif
        @if($memberships->nextPageUrl())<a class="btn" href="{{ $memberships->nextPageUrl() }}">Berikutnya</a>@endif
    </nav>
</main>
@endsection
