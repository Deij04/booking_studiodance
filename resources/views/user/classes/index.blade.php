@extends('layout.landing')
@push('styles')
<style>
    .classes-hero{position:relative;isolation:isolate;min-height:520px;min-height:64svh;display:flex;align-items:flex-end;overflow:hidden;padding:64px max(24px,calc((100% - 1180px)/2 + 24px))}
    .classes-hero>img{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover;object-position:center 35%;filter:sepia(.5) brightness(.55)}
    .classes-hero::after{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,#1112,#1113 40%,#111 100%)}
    .classes-hero-copy{max-width:650px}
    .classes-hero .eyebrow{padding:5px 12px;margin-bottom:16px;background:#332b16b3;font-size:10px;letter-spacing:.12em;text-transform:uppercase}
    .classes-hero h1{font-size:clamp(34px,5vw,52px);line-height:1.2;letter-spacing:-.02em}
    .classes-hero p{max-width:560px;color:var(--muted);font-size:15px;line-height:1.7;margin:18px 0 0}
    .classes-content{max-width:1180px;margin:0 auto;padding:12px 24px 48px}
    .classes-filters{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 32px}
    .class-filter{display:inline-flex;align-items:center;min-height:36px;padding:8px 18px;border:1px solid #2d2b24;border-radius:999px;background:#1e1e1e;color:#aaa;font-size:11px;font-weight:600}
    .class-filter[aria-current]{background:linear-gradient(100deg,#f4ce47,#b2931e);color:#171717;border-color:#c5a532}
    .class-filter:hover{border-color:var(--gold)}
    .user-class-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px}
    .user-class-card{display:flex;flex-direction:column;overflow:hidden;min-width:0;border:1px solid #29271f;border-radius:24px;background:#171717}
    .user-class-photo{position:relative;aspect-ratio:1.3/1;background:#1c190f;overflow:hidden}
    .user-class-photo>img{display:block;width:100%;height:100%;object-fit:cover}
    .user-class-photo::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 60%,#171717);pointer-events:none}
    .class-no-image{display:grid;place-items:center;width:100%;height:100%;background:radial-gradient(ellipse,#3e3319,#151515);color:var(--muted);font-size:13px}
    .user-class-level{position:absolute;z-index:1;top:12px;right:12px;background:#d5b43a;color:#201a09;padding:5px 9px;border-radius:999px;font-size:9px;font-weight:600;text-transform:uppercase}
    .user-class-body{display:flex;flex-direction:column;flex:1;padding:18px 16px 20px}
    .user-class-body h2{font-size:17px;margin:0 0 10px;color:var(--gold);line-height:1.4;overflow-wrap:anywhere}
    .user-class-description{font-size:12px;line-height:1.7;color:#929292;margin:0 0 20px;overflow-wrap:anywhere;white-space:pre-line}
    .user-class-info{display:grid;gap:10px;margin:auto 0 20px;padding:0;list-style:none;color:#aaa;font-size:11px;line-height:1.6}
    .user-class-info li{display:flex;gap:8px;align-items:flex-start}
    .user-class-info svg{width:14px;height:14px;flex-shrink:0;margin-top:2px;fill:none;stroke:#c5a532;stroke-width:1.6}
    .user-class-join{display:block;width:100%;padding:10px 14px;min-height:40px;border:0;border-radius:999px;background:linear-gradient(100deg,#f4ce47,#b2931e);color:#191609;font:700 11px Poppins,sans-serif;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
    .user-class-join:disabled{background:#302d24;color:#8b877b;cursor:default}
    .class-availability{color:#7cc99b;font-size:10px;margin:0 0 12px}
    .class-empty{grid-column:1/-1;padding:40px;border:1px solid #302d24;border-radius:24px;text-align:center;color:var(--muted)}
    .classes-why{background:#0e0e0e;padding:44px 24px 56px}
    .classes-why>h2{font-size:clamp(30px,4.5vw,46px);text-align:center;margin:0 0 36px}
    .classes-why>h2 span{color:var(--gold)}
    .classes-benefits{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px;max-width:1132px;margin:0 auto}
    .classes-benefit{padding:26px 22px;border:1px solid #28261f;border-radius:24px;background:#141414}
    .classes-benefit svg{width:28px;height:28px;fill:none;stroke:var(--gold);stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;margin-bottom:16px}
    .classes-benefit h3{font-size:15px;font-weight:500;color:#bbb;margin:0 0 12px}
    .classes-benefit p{font-size:13px;line-height:1.8;color:#929292;margin:0}
    .class-join-dialog{width:min(520px,calc(100% - 36px));max-height:85dvh;overflow:auto;padding:28px;border:1px solid #b99a2b;border-radius:24px;background:#191817;color:var(--text)}
    .class-join-dialog::backdrop{background:#000b}
    .class-join-dialog form{text-align:right}.class-join-dialog button{border:0;background:none;color:var(--gold);font:inherit;cursor:pointer}
    .class-join-dialog p,.class-join-dialog li{color:var(--muted);font-size:14px;line-height:1.8}
    .class-join-dialog ul{padding-left:20px}
    .class-join-dialog li{margin-bottom:12px}
    .classes-content a:focus-visible,.classes-content button:focus-visible,.class-join-dialog a:focus-visible{outline:2px solid var(--gold);outline-offset:4px}
    @media(max-width:1000px){.user-class-grid,.classes-benefits{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.classes-hero{min-height:440px;min-height:58svh;padding:48px 18px}.classes-content{padding:0 18px 32px}.classes-filters{flex-wrap:nowrap;overflow-x:auto;padding:8px 0 14px;scrollbar-width:thin}.class-filter{flex-shrink:0;min-height:44px;font-size:12px}.user-class-grid{grid-template-columns:1fr;gap:20px}.user-class-body{padding:22px}.user-class-body h2{font-size:21px}.user-class-description{font-size:15px}.user-class-info{font-size:14px}.user-class-join{min-height:48px;font-size:12px}.classes-why{padding:32px 18px 40px}.classes-benefits{grid-template-columns:1fr;gap:16px}}
    .studio-search{position:relative;margin-top:-38px;display:grid;grid-template-columns:1fr 1fr 1fr 48px;align-items:end;gap:20px;padding:28px;border:1px solid #302d24;border-radius:26px;background:#191817;box-shadow:0 18px 34px #0005}
    .studio-search label,.studio-time-label{display:block;color:#b99a2b;font-size:13px;margin-bottom:10px}
    .studio-search input,.studio-search select{width:100%;min-width:0;min-height:44px;padding:10px 12px;border:1px solid transparent;border-radius:5px;background:#202020;color:#b5b0a1;font:13px Poppins,sans-serif;color-scheme:dark}
    .studio-search input:focus,.studio-search select:focus{outline:2px solid var(--gold);outline-offset:2px}
    .studio-times{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .studio-times label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
    .studio-search-submit{display:grid;place-items:center;width:44px;height:44px;border:0;border-radius:6px;background:linear-gradient(135deg,#f0cb45,#a78915);color:#171717;cursor:pointer;box-shadow:0 0 20px #d5b43a33}
    .studio-search-submit svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2}
    .studio-search-errors{padding:18px;color:#ffd7d7;background:#3c1b1b;border-radius:12px;margin-top:20px}
.studio-search{margin:0 0 24px;grid-template-columns:1fr 1fr 1fr 48px}.class-search-reset{grid-column:1/-1;justify-self:end;font-size:12px;color:var(--gold)}
@media(max-width:900px){.studio-search{grid-template-columns:1fr 1fr}.studio-search-submit{justify-self:end}}
@media(max-width:600px){.studio-search{grid-template-columns:1fr;padding:20px;gap:16px}.studio-search input,.studio-search select{font-size:16px}.studio-search-submit{width:100%;height:44px}}
</style>
@endpush
@section('content')
<main>
    <section class="classes-hero" aria-labelledby="classes-title">
        <img src="{{ asset('images/Login.jpg') }}" alt="">
        <div class="classes-hero-copy"><span class="eyebrow">Orange Dance Academy</span><h1 id="classes-title">Find Your <span class="gold">Rhythm</span></h1><p>Discover dance classes for every stage of your journey. Explore new styles, develop your technique, and move with confidence.</p></div>
    </section>
    <div class="classes-content">
        <form class="studio-search" action="{{ route('user.classes.index') }}" method="GET">
            @if(!empty($filters['class_id']))<input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">@endif
            <div><label for="studio-date">Date</label><input id="studio-date" type="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date', $filters['date'] ?? '') }}"></div>
            <div>
                <span class="studio-time-label">Time Slots</span>
                <div class="studio-times">
                    @foreach(['start_time' => 'Start Time', 'end_time' => 'End Time'] as $field => $label)
                        <div><label for="studio-{{ $field }}">{{ $label }}</label><select id="studio-{{ $field }}" name="{{ $field }}">
                            <option value="">{{ $label }}</option>
                            @for($minute = 0; $minute < 1440; $minute += 30)
                                @php($time = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60))
                                <option value="{{ $time }}" @selected(old($field, $filters[$field] ?? '') === $time)>{{ $time }}</option>
                            @endfor
                        </select></div>
                    @endforeach
                </div>
            </div>
            <div><label for="studio-type">Studio Type</label><select id="studio-type" name="studio_id"><option value="">All Studios</option>@foreach($studioOptions as $studio)<option value="{{ $studio->id }}" @selected(old('studio_id', $filters['studio_id'] ?? '') == $studio->id)>{{ $studio->name }}</option>@endforeach</select></div>
            <button class="studio-search-submit" type="submit" aria-label="Search studios"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg></button>
            <a class="class-search-reset" href="{{ route('user.classes.index', array_filter(['class_id' => $filters['class_id'] ?? null])) }}">Reset</a>
        </form>
        @if($errors->any())<div class="studio-search-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <nav class="classes-filters" aria-label="Filter dance classes">
            <a class="class-filter" href="{{ route('user.classes.index', array_diff_key($filters, ['class_id' => true])) }}" @if(empty($filters['class_id'])) aria-current="true" @endif>All Classes</a>
            @foreach($classOptions as $option)<a class="class-filter" href="{{ route('user.classes.index', array_merge($filters, ['class_id' => $option->id])) }}" @if(($filters['class_id'] ?? null) == $option->id) aria-current="true" @endif>{{ $option->name }}</a>@endforeach
        </nav>
        <div class="user-class-grid">
            @forelse($classes as $class)
                @php($next = $class->classSchedules->first(fn ($schedule) => $schedule->occupied < $schedule->capacity))
                <article class="user-class-card">
                    <div class="user-class-photo">
                        @if($class->image)<img src="{{ asset('storage/'.$class->image) }}" alt="Photo of {{ $class->name }}" loading="lazy">@else<div class="class-no-image">Image unavailable</div>@endif
                        <span class="user-class-level">{{ \App\Models\DanceClass::LEVELS[$class->level] ?? $class->level }}</span>
                    </div>
                    <div class="user-class-body">
                        <h2>{{ $class->name }}</h2>
                        @if($next)<p class="class-availability">{{ max(0, $next->capacity - $next->occupied) }} spots left in the next session</p>@endif
                        @if($class->description)<p class="user-class-description">{{ __($class->description) }}</p>@endif
                        <ul class="user-class-info">
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18"/></svg>@if($next){{ \Illuminate\Support\Carbon::parse($next->date)->format('D, d M Y') }}, {{ substr($next->start_time, 0, 5) }}@else No available sessions yet @endif</li>
                            @if($next)<li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ substr($next->start_time, 0, 5) }} &ndash; {{ substr($next->end_time, 0, 5) }} &middot; {{ $next->studio->name }}</li>@endif
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>IDR {{ number_format($class->price, 0, '.', ',') }} / session</li>
                        </ul>
                        @if($next)<a class="user-class-join" style="text-align:center" href="{{ route('user.classes.reserve', ['danceClass' => $class, 'schedule_id' => $next->id]) }}">Join Now</a>@else<button class="user-class-join" type="button" disabled>No Available Sessions</button>@endif
                    </div>
                </article>

            @empty<div class="class-empty">No dance classes are currently available. Please check back soon.</div>@endforelse
        </div>
    </div>
    <section class="classes-why" aria-labelledby="classes-why-title">
        <h2 id="classes-why-title"><span>Why</span> Choose <span>Us?</span></h2>
        <div class="classes-benefits">
            <article class="classes-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18"/><path d="M7 17v-5m5 5V7m5 10V9"/></svg><h3>Live Session Details</h3><p>See upcoming dates, studio locations, and remaining spots from our class schedules.</p></article>
            <article class="classes-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 2-9 12h6l-1 8 9-13h-6z"/></svg><h3>Find Your Style</h3><p>Explore our class selection and discover the movements that inspire you.</p></article>
            <article class="classes-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18"/></svg><h3>Plan Your Next Session</h3><p>Compare available sessions and find a time that works for your routine.</p></article>
            <article class="classes-benefit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="2"/><path d="m11 7-6 15m8-15 6 15M8 15h8"/></svg><h3>Grow at Your Level</h3><p>Check each class level to find a suitable next step in your dance journey.</p></article>
        </div>
    </section>
</main>
<script>
    (() => {
        const date = document.getElementById('studio-date');
        const times = document.querySelectorAll('.studio-times select');
        const syncDate = () => {
            const hasTime = Array.from(times).some(input => input.value !== '');
            date.required = hasTime;
            times.forEach(input => { input.required = hasTime; });
        };
        times.forEach(input => input.addEventListener('change', syncDate));
        syncDate();
    })();
</script>
<script>
    document.querySelectorAll('[data-class-dialog]').forEach(button => {
        button.addEventListener('click', () => document.getElementById(button.dataset.classDialog).showModal());
    });
</script>
@endsection
