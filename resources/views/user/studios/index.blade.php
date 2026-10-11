@extends('layout.landing')

@push('styles')
<style>
    .studio-hero{position:relative;display:grid;place-items:center;min-height:580px;min-height:72svh;padding:80px 24px 110px;text-align:center;isolation:isolate;overflow:hidden}
    .studio-hero-photo{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover;filter:sepia(.55) brightness(.5)}
    .studio-hero::after{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,#1113,#1115 45%,#111 100%)}
    .studio-hero h1{font-size:clamp(34px,4.8vw,56px);letter-spacing:-.02em;line-height:1.2;margin:0}
    .studio-hero p{max-width:650px;margin:22px auto 0;color:#aaa89f;font-size:16px;line-height:1.7}
    .studio-content{max-width:1180px;margin:0 auto;padding:0 24px 48px}
    .studio-search{position:relative;margin-top:-38px;display:grid;grid-template-columns:1fr 1fr 1fr 48px;align-items:end;gap:20px;padding:28px;border:1px solid #302d24;border-radius:26px;background:#191817;box-shadow:0 18px 34px #0005}
    .studio-search label,.studio-time-label{display:block;color:#b99a2b;font-size:13px;margin-bottom:10px}
    .studio-search input,.studio-search select{width:100%;min-width:0;min-height:44px;padding:10px 12px;border:1px solid transparent;border-radius:5px;background:#202020;color:#b5b0a1;font:13px Poppins,sans-serif;color-scheme:dark}
    .studio-search input:focus,.studio-search select:focus{outline:2px solid var(--gold);outline-offset:2px}
    .studio-times{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .studio-times label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
    .studio-search-submit{display:grid;place-items:center;width:44px;height:44px;border:0;border-radius:6px;background:linear-gradient(135deg,#f0cb45,#a78915);color:#171717;cursor:pointer;box-shadow:0 0 20px #d5b43a33}
    .studio-search-submit svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2}
    .studio-results-heading{display:flex;justify-content:space-between;gap:20px;align-items:end;margin:0 0 24px}
    .studio-results-heading h2{font-size:28px;margin:0 0 8px;line-height:1.3}
    .studio-results-heading p{margin:0;color:#929292;font-size:14px;line-height:1.6}
    .studio-view-all{color:#b99a2b;font-size:13px;white-space:nowrap}
    .user-studio-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
    .user-studio-card{min-width:0;overflow:hidden;display:flex;flex-direction:column;border:1px solid #2e2b24;border-radius:26px;background:#191817}
    .studio-image-wrap{position:relative;aspect-ratio:1.7/1;overflow:hidden;background:#252015}
    .user-studio-image{display:block;width:100%;height:100%;object-fit:cover}
    .studio-placeholder{display:grid;place-items:center;height:100%;background:radial-gradient(ellipse,#4c3d1d,#191817)}
    .studio-placeholder{color:var(--muted);font-size:14px;text-align:center;padding:20px}
    .studio-status{position:absolute;top:14px;right:12px;padding:5px 10px;border-radius:999px;background:#343434e6;color:#ddd;font-size:9px;font-weight:600;letter-spacing:.07em;text-transform:uppercase}
    .studio-status::before{content:'';display:inline-block;width:6px;height:6px;margin-right:6px;border-radius:50%;background:#23c976}
    .studio-card-body{display:flex;flex-direction:column;flex:1;padding:22px 20px 28px}
    .studio-card-heading{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap}
    .studio-card-heading h3{font-size:19px;font-weight:600;margin:0;overflow-wrap:anywhere}
    .studio-rate{color:var(--gold);font-size:15px;font-weight:700;white-space:nowrap}
    .studio-rate small{font-size:11px;color:#929292;font-weight:400}
    .user-studio-description{color:#929292;font-size:12px;line-height:1.7;margin:12px 0;overflow-wrap:anywhere}
    .studio-capacity{display:flex;align-items:center;gap:7px;color:#aaa;font-size:12px;margin:0 0 20px}
    .studio-capacity svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.5}
    .studio-reserve{display:block;text-align:center;margin-top:auto;padding:12px;border:1px solid #b99a2b;border-radius:5px;color:#c5a532;font:13px Poppins,sans-serif;background:transparent;cursor:pointer}
    .studio-reserve:hover{background:#c5a532;color:#111}
    .studio-empty-state{grid-column:1/-1;padding:40px;text-align:center;border:1px solid #302d24;border-radius:24px;color:var(--muted)}
    .studio-empty-state h2{font-size:24px;color:var(--gold)}
    .studio-benefits{background:#0e0e0e;padding:40px 24px 52px}
    .studio-benefit-grid{max-width:1180px;margin:0 auto;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px}
    .studio-benefit{padding:26px;border:1px solid #27251f;border-radius:26px;background:#141414}
    .studio-benefit svg{width:28px;height:28px;margin-bottom:16px;fill:none;stroke:var(--gold);stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
    .studio-benefit h2{font-size:15px;font-weight:500;color:#bbb;margin:0 0 12px}
    .studio-benefit p{font-size:13px;color:#929292;line-height:1.8;margin:0}
    .studio-booking-dialog{width:min(480px,calc(100% - 36px));padding:28px;border:1px solid #b99a2b;border-radius:24px;background:#191817;color:var(--text)}
    .studio-booking-dialog::backdrop{background:#000b}
    .studio-booking-dialog p{color:var(--muted);line-height:1.8;font-size:14px}
    .studio-booking-dialog form{text-align:right}.studio-booking-dialog button{background:transparent;border:0;color:var(--gold);font:inherit;cursor:pointer}
    .studio-search-errors{padding:18px;color:#ffd7d7;background:#3c1b1b;border-radius:12px;margin-top:20px}
    .studio-content a:focus-visible,.studio-content button:focus-visible,.studio-booking-dialog a:focus-visible{outline:2px solid var(--gold);outline-offset:4px}
    @media(max-width:1000px){.user-studio-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-benefit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-search{grid-template-columns:1fr 1fr}.studio-search-submit{justify-self:end}}
    @media(max-width:600px){.studio-hero{min-height:460px;min-height:62svh;padding:64px 20px 80px}.studio-hero p{font-size:15px}.studio-content{padding:0 18px 32px}.studio-search{grid-template-columns:1fr;padding:22px;gap:18px;border-radius:22px}.studio-search input,.studio-search select{font-size:16px;min-height:48px}.studio-search-submit{width:100%;height:48px}.studio-results-heading{align-items:start;margin-top:0;gap:12px;flex-wrap:wrap}.studio-results-heading h2{font-size:25px}.user-studio-grid{grid-template-columns:1fr;gap:20px}.studio-benefits{padding:32px 18px}.studio-benefit-grid{grid-template-columns:1fr;gap:16px}.studio-reserve{font-size:15px;min-height:48px}}
</style>
@endpush

@section('content')
<main>
    <section class="studio-hero" aria-labelledby="studio-page-title">
        <img class="studio-hero-photo" src="{{ asset('images/bg-studi1.png') }}" alt="">
        <div>
            <h1 id="studio-page-title">Premium <span class="gold">Studio</span> Spaces</h1>
            <p>Elevate your practice in a space designed for movement. Find the right studio for your next rehearsal, class, or creative session.</p>
        </div>
    </section>
    <div class="studio-content">
        <div class="studio-results-heading">
            <div><h2>Our All Spaces</h2><p>{{ $checkedAvailability ? 'Available spaces for your selected date and time.' : 'Tailored environments for every movement.' }}</p></div>
        </div>
        <div class="user-studio-grid">
            @forelse($studios as $studio)
                <article class="user-studio-card">
                    <div class="studio-image-wrap">
                        @if($studio->image)<img class="user-studio-image" src="{{ asset('storage/'.$studio->image) }}" alt="Photo of {{ $studio->name }}" loading="lazy">
                        @else<div class="studio-placeholder">Gambar tidak tersedia</div>@endif
                        <span class="studio-status">{{ $checkedAvailability ? 'Available' : 'Active Studio' }}</span>
                    </div>
                    <div class="studio-card-body">
                        <div class="studio-card-heading"><h3>{{ $studio->name }}</h3><span class="studio-rate">IDR {{ number_format($studio->price_per_hour, 0, '.', ',') }}<small>/hr</small></span></div>
                        @if($studio->description)<p class="user-studio-description">{{ __($studio->description) }}</p>@endif
                        <p class="studio-capacity"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="3"/><path d="M3 21v-5a6 6 0 0 1 12 0v5m1-17a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v3"/></svg>{{ $studio->capacity }} people</p>
                        <a class="studio-reserve" href="{{ route('user.bookings.create', ['studio_id' => $studio->id] + array_intersect_key($filters, array_flip(['date', 'start_time', 'end_time']))) }}">Reserve Now</a>
                    </div>
                </article>
            @empty
                <div class="studio-empty-state"><h2>{{ $allStudios->isEmpty() ? 'No studios available yet' : 'No matching studios' }}</h2><p>{{ $allStudios->isEmpty() ? 'Please check back soon for available studios.' : 'Try another date, time slot, or studio.' }}</p></div>
            @endforelse
        </div>
    </div>
    <section class="studio-benefits" aria-label="Studio services">
        <div class="studio-benefit-grid">
            <article class="studio-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M7 17v-5m5 5V7m5 10V9"/></svg><h2>Live Availability</h2><p>Choose a date and time to check studio availability against bookings and class schedules.</p></article>
            <article class="studio-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 2-9 12h6l-1 8 9-13h-6z"/></svg><h2>Easy Booking</h2><p>Choose your studio and available time, then submit your reservation for approval.</p></article>
            <article class="studio-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18m-9 4v3l3 1"/></svg><h2>Plan Your Practice</h2><p>Explore time slots and choose a session that fits your practice schedule.</p></article>
            <article class="studio-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="2"/><path d="m11 7-6 15m8-15 6 15M8 15h8"/></svg><h2>Space for Every Session</h2><p>Compare studio capacity and hourly rates to find the right space for your needs.</p></article>
        </div>
    </section>
</main>

@endsection
