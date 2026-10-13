@extends('layout.landing')

@push('styles')
    <style>
        .landing-intro{position:relative;isolation:isolate;background:radial-gradient(ellipse at 50% 12%,rgba(155,119,0,.19),transparent 65%),#111}
        .dance-hero{display:flex;flex-direction:column;justify-content:center;min-height:calc(100vh - var(--hero-header-height,89px));min-height:calc(100dvh - var(--hero-header-height,89px));max-width:1180px;margin:0 auto;padding:clamp(20px,4svh,44px) 24px clamp(18px,3svh,30px);text-align:center}
        .dance-hero>*{flex-shrink:0}
        .dance-hero h1{margin:0;color:#e3e1dc;font-size:clamp(32px,min(6vw,9svh),72px);font-weight:300;line-height:1.12;letter-spacing:.12em}
        .dance-hero h1 span{display:block}
        .dance-hero h1 strong{color:#ffd000;font-weight:800;text-transform:uppercase}
        .dance-hero-description{max-width:940px;margin:clamp(14px,2.5svh,22px) auto 0;color:#aaa89f;font-size:clamp(14px,1.3vw,16px);line-height:1.6}
        .dance-hero-actions{display:flex;justify-content:center;flex-wrap:wrap;gap:20px;margin-top:clamp(28px,5svh,48px)}
        .dance-hero-actions .btn{min-width:190px;min-height:46px;padding:12px 26px;font-size:13px;color:#eac32e;border-color:rgba(218,177,31,.4);background:rgba(42,35,12,.4);transition:background-color .2s,transform .2s}
        .dance-hero-actions .btn-fill{color:#191609;background:#d5b43a;border:1px solid #d5b43a;box-shadow:0 0 24px rgba(213,180,58,.12)}
        .dance-hero-actions .btn:hover{transform:translateY(-2px);background:#3b3214}
        .dance-hero-actions .btn-fill:hover{background:#ebc847}
        .dance-feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px;margin:clamp(32px,6svh,56px) auto 0;width:100%;max-width:1080px}
        .dance-feature{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:clamp(106px,18svh,148px);padding:18px 12px;border:1px solid rgba(218,177,31,.3);border-radius:24px;background:rgba(29,27,17,.75);box-shadow:inset 0 1px 0 rgba(242,200,63,.05)}
        .dance-feature svg{width:34px;height:34px;fill:none;stroke:#e6bd00;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
        .dance-feature strong{font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#e0dfd8}
        .dance-hero a:focus-visible{outline:2px solid #ffd000;outline-offset:5px}
        .landing-intro #studio{padding-top:36px}
        .landing-intro #studio .section-title{text-transform:uppercase;color:#f3f3f0;font-size:clamp(30px,4.3vw,52px);line-height:1.2}
        .landing-intro #studio .section-title .gold{color:#ffd000}
        .landing-intro #studio .sub{font-size:13px;margin-top:10px;margin-bottom:36px}
        #studio .studio-photo{overflow:hidden}
        #studio .studio-image-unavailable{display:grid;place-items:center;min-height:220px;padding:20px;color:var(--muted);font-size:14px;text-align:center}
        #studio .studio-photo img{display:block;width:100%;height:220px;object-fit:cover}
        #studio .content-card{grid-template-columns:1fr;align-content:start}
        #studio .studio-empty{grid-column:1 / -1;text-align:center;color:var(--muted)}
        #studio .content-card>div{min-width:0;overflow-wrap:anywhere}
        #studio .studio-see-more{display:table;margin:28px auto 0;padding:8px 18px;border:1px solid rgba(240,198,58,.45);border-radius:999px;background:transparent;color:var(--gold);font:600 12px Poppins,sans-serif}
        #studio .studio-see-more:hover{background:rgba(240,198,58,.12)}
        #studio .studio-see-more:focus-visible{outline:2px solid var(--gold);outline-offset:4px}
        .availability-panel{max-width:940px;margin:0 auto;border-top:2px solid #9b811e;border-bottom:2px solid #9b811e;padding:30px 0}
        .availability-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
        .availability-heading h2{margin:0;color:#e8e8e8;font-size:clamp(20px,2vw,28px);font-weight:600;line-height:1.3}
        .availability-heading p{margin:6px 0 0;color:#929292;font-size:12px;line-height:1.6}
        .availability-see-all{flex-shrink:0;border:1px solid #c6a300;border-radius:6px;padding:5px 9px;background:transparent;color:#ffd000;font:600 11px Poppins,sans-serif;cursor:pointer}
        .availability-slots{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;max-width:680px;margin:24px auto 0}
        .availability-slot{display:flex;align-items:center;gap:12px;min-width:0;min-height:78px;padding:14px 18px;border:1px solid #c6a300;border-radius:24px;background:#111}
        .availability-icon{display:grid;place-items:center;flex-shrink:0;width:40px;height:40px;border-radius:50%;background:#3d3418;color:#edc500}
        .availability-icon svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
        .availability-slot-info{min-width:0}
        .availability-slot-info h3{margin:0;font-size:15px;font-weight:500;color:#e8e8e8;overflow-wrap:anywhere}
        .availability-slot-info p{margin:2px 0 0;color:#929292;font-size:11px}
        .availability-status{display:flex;align-items:center;gap:6px;margin-left:auto;white-space:nowrap;color:#ffd000;font-size:11px;font-weight:600}
        .availability-status::before{content:"";width:6px;height:6px;border-radius:50%;background:#1ed760}
        .availability-dialog{width:min(900px,calc(100% - 32px));max-height:85dvh;overflow:auto;padding:28px;border:1px solid #c6a300;border-radius:20px;background:#111;color:#e8e8e8}
        .availability-dialog::backdrop{background:rgba(0,0,0,.75)}
        .availability-dialog h2{margin:0;font-size:24px}
        .availability-dialog>p{color:#929292;font-size:13px}
        .availability-dialog form{text-align:right;margin-bottom:16px}
        .availability-see-all:focus-visible{outline:2px solid #ffd000;outline-offset:4px}
        .dance-classes .section-title{text-transform:uppercase;color:#f4f4f4;font-size:clamp(30px,3.5vw,46px);line-height:1.2}
        .dance-classes .section-title .gold{color:#ffd000}
        .dance-classes .sub{font-size:12px;color:#929292;margin:10px auto 44px;max-width:780px}
        .dance-class-track{display:grid;grid-auto-flow:column;grid-auto-columns:calc((100% - 60px) / 3.5);gap:20px;width:100%;max-width:940px;margin:0 auto;overflow-x:auto;scroll-snap-type:x mandatory;scroll-padding:2px;padding:4px 2px 18px;scrollbar-width:thin;scrollbar-color:#b99a2b #252218}
        .dance-class-track::-webkit-scrollbar{height:6px}
        .dance-class-track::-webkit-scrollbar-track{background:#252218;border-radius:999px}
        .dance-class-track::-webkit-scrollbar-thumb{background:#b99a2b;border-radius:999px}
        .dance-class-track:focus-visible{outline:2px solid #ffd000;outline-offset:4px}
        .dance-class-card{min-width:0;overflow:hidden;scroll-snap-align:start;border:1px solid rgba(218,177,31,.13);border-radius:22px;background:#121212}
        .dance-class-photo{position:relative;aspect-ratio:1.15 / 1;overflow:hidden;background:#211a0d}
        .dance-class-photo img{display:block;width:100%;height:100%;object-fit:cover;object-position:var(--photo-position,center);filter:sepia(.45)}
        .dance-class-photo::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,#121212 100%);pointer-events:none}
        .dance-class-price{position:absolute;z-index:1;top:14px;right:14px;padding:4px 10px;border-radius:999px;background:#ffd000;color:#171300;font-size:11px;font-weight:700}
        .dance-class-body{padding:10px 14px 14px}
        .dance-class-body h3{margin:0;color:#eee;font-size:14px;font-weight:500}
        .dance-class-coach{margin:4px 0 10px;color:#8f8f8f;font-size:11px}
        .dance-class-reserve{display:flex;align-items:center;justify-content:center;min-height:36px;border:1px solid #b79500;border-radius:999px;color:#ffd000;font-size:11px;font-weight:500}
        .dance-class-details{margin:14px 0 0;color:#aaa;font-size:12px;line-height:1.7}
        .dance-class-more{display:flex;align-items:center;justify-content:center;width:250px;max-width:100%;min-height:36px;margin:28px auto 0;border:0;border-radius:6px;background:linear-gradient(100deg,#f4cf47,#8c7119);color:#191609;font:600 11px Poppins,sans-serif;letter-spacing:.06em;cursor:pointer}
        .dance-classes a:focus-visible,.dance-class-more:focus-visible{outline:2px solid #ffd000;outline-offset:4px}
        @media(max-width:900px){
            .dance-class-track{grid-auto-columns:calc((100% - 32px) / 2.5);gap:16px}
        }
        @media(max-width:520px){.dance-class-track{grid-auto-columns:68%;gap:14px}.dance-classes .sub{margin-bottom:26px}}
        @media(max-width:700px){
            .premium{padding:34px 20px}
            .premium .eyebrow{padding:8px 16px;font-size:14px;margin-bottom:22px}
            .premium .section-title{font-size:clamp(32px,8vw,40px);line-height:1.2}
            .premium .sub{font-size:18px;line-height:1.65;margin-top:16px}
            .premium .hero-actions{margin-top:24px;gap:14px;flex-wrap:wrap}
            .premium .hero-actions .btn{flex:1 1 200px;min-height:56px;padding:16px 22px;font-size:17px}
            .availability-panel{padding:24px 0}
            .availability-heading{gap:12px}
            .availability-heading h2{font-size:20px}
            .availability-heading p{font-size:12px}
            .availability-slots{grid-template-columns:1fr;gap:12px;margin-top:20px;max-width:360px}
            .availability-slot{min-height:72px;padding:12px 14px;border-radius:20px;gap:10px}
            .availability-icon{width:36px;height:36px}
            .availability-slot-info h3{font-size:15px}
        }
        @media(max-width:700px){
            .dance-hero{padding:clamp(16px,3svh,28px) 20px 18px}
            .dance-hero h1{font-size:clamp(25px,min(6.4vw,7svh),42px);letter-spacing:.08em}
            .dance-hero-description{margin-top:14px;font-size:13px;line-height:1.5}
            .dance-hero-actions{margin-top:clamp(24px,4svh,32px);gap:12px}
            .dance-hero-actions .btn{min-width:0;padding:12px 20px;font-size:12px;min-height:46px}
            .dance-feature-grid{gap:12px;margin-top:clamp(28px,4.5svh,36px);grid-template-columns:repeat(2,minmax(0,1fr))}
            .dance-feature{min-height:clamp(92px,14svh,112px);padding:12px 8px;gap:8px;border-radius:20px}
            .dance-feature svg{width:28px;height:28px}
            .landing-intro #studio{padding-top:28px}
        }
        .testimonials{max-width:1180px;margin:0 auto;padding-top:56px}
        .testimonials .section-title,.landing-qna .section-title{font-size:clamp(26px,3vw,36px);color:#e8e8e8}
        .testimonials .sub{font-size:13px;margin-top:8px;margin-bottom:38px}
        .testimonial-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
        .testimonial-card{display:flex;flex-direction:column;justify-content:space-between;gap:24px;margin:0;padding:26px;border:1px solid rgba(240,198,58,.14);border-radius:22px;background:#101010;box-shadow:0 0 24px rgba(240,198,58,.1)}
        .testimonial-card blockquote{margin:0;color:#aaa89f;font-size:14px;font-style:italic;line-height:1.65}
        .testimonial-person{display:flex;align-items:center;gap:12px}
        .testimonial-avatar{display:grid;place-items:center;flex-shrink:0;width:38px;height:38px;border:1px solid rgba(240,198,58,.25);border-radius:50%;background:#29230c;color:#e6bd00;font-size:12px;font-weight:600}
        .testimonial-person strong{display:block;color:#ddd;font-size:13px;font-weight:500}
        .testimonial-person span{display:block;color:#929292;font-size:11px;margin-top:3px}
        .testimonial-card:nth-child(n+4){gap:16px;padding:22px}
        .landing-qna{max-width:720px;margin:0 auto;padding-top:56px;padding-bottom:56px}
        .qna-list{display:grid;gap:12px;margin-top:28px}
        .qna-item{border:1px solid rgba(240,198,58,.12);border-radius:20px;background:#111;overflow:hidden}
        .qna-item summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;list-style:none;cursor:pointer;color:#aaa89f;font-size:14px}
        .qna-item summary::-webkit-details-marker{display:none}
        .qna-item summary::after{content:'';width:7px;height:7px;flex-shrink:0;border-right:1.5px solid #e6bd00;border-bottom:1.5px solid #e6bd00;transform:rotate(45deg)}
        .qna-item[open] summary::after{transform:rotate(225deg)}
        .qna-item summary:focus-visible{outline:2px solid #ffd000;outline-offset:-4px}
        .qna-item p{margin:0;padding:0 22px 20px;color:#aaa89f;font-size:14px;line-height:1.7}
        @media(max-width:900px){.testimonial-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}}
        @media(max-width:600px){.testimonial-grid{grid-template-columns:1fr;gap:18px}.testimonial-card,.testimonial-card:nth-child(n+4){padding:24px}.testimonial-card blockquote{font-size:16px}.testimonial-person strong{font-size:15px}.testimonial-person span{font-size:12px}.testimonials,.landing-qna{padding-top:40px}.qna-item summary{padding:18px;font-size:16px}.qna-item p{padding:0 18px 20px;font-size:15px}}
        @media(prefers-reduced-motion:reduce){.dance-hero-actions .btn{transition:none}.dance-hero-actions .btn:hover{transform:none}}
    </style>
@endpush

@section('content')
    <div class="landing-intro">
    <main id="home" class="dance-hero">
        <h1><span><strong>Dance</strong> Without</span><span>Limits, <strong>Together</strong></span></h1>
        <p class="dance-hero-description">Orange Dance Academy helps you reserve dance studios, explore available schedules, and join your favorite dance classes faster, easier, and more conveniently through one seamless online platform.</p>
        <div class="dance-hero-actions">
            <a class="btn btn-fill" href="{{ route('user.studios.index') }}">Book Studio Now</a>
            <a class="btn" href="{{ route('user.classes.index') }}">View All Classes</a>
        </div>
        <div class="dance-feature-grid">
            <a class="dance-feature" href="{{ route('user.studios.index') }}">
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M11 18V6h10v12M14 10h4M8 18h16v7H8zM5 28v-7h3m19 7v-7h-3M12 25v3m8-3v3"/></svg>
                <strong>Studio Booking</strong>
            </a>
            <a class="dance-feature" href="{{ route('user.studios.index') }}">
                <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="11"/><path d="M16 8v8l5 3"/></svg>
                <strong>Studio Schedule</strong>
            </a>
            <a class="dance-feature" href="{{ route('user.classes.index') }}">
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="m7 17 1-9 9-1M8 8l16 16m-9 1 9-1 1-9M5 10l6-6m10 24 7-7"/></svg>
                <strong>Dance Classes</strong>
            </a>
            <a class="dance-feature" href="{{ auth()->check() && auth()->user()->can('access-admin') ? route('admin.memberships.index') : route('memberships.index') }}">
                <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="12" r="7"/><path d="m11 18-1 10 6-3 6 3-1-10m-5-10 1.2 2.4 2.6.4-1.9 1.8.5 2.6-2.4-1.3-2.4 1.3.5-2.6-1.9-1.8 2.6-.4Z"/></svg>
                <strong>Membership</strong>
            </a>
        </div>
    </main>
    <script>
        (() => {
            const hero = document.getElementById('home');
            const header = document.querySelector('.site-header');
            if (!hero || !header) return;
            const syncHeaderHeight = () => {
                hero.style.setProperty('--hero-header-height', `${header.getBoundingClientRect().height}px`);
            };
            syncHeaderHeight();
            new ResizeObserver(syncHeaderHeight).observe(header);
        })();
    </script>

    <section id="studio" class="section">
        <h2 class="section-title">Our <span class="gold">Studio</span></h2>
        <p class="sub">Comfortable, bright studios ready for classes and private practice.</p>
        <div class="studio-grid">
            @forelse ($studios as $studio)
                <div class="content-card">
                    <div>
                        <h3>{{ $studio->name }}</h3>
                        @if ($studio->description)
                            <p>{{ __($studio->description) }}</p>
                        @endif
                    </div>
                    <div class="shot studio-photo {{ $loop->even ? 'alt' : '' }}">
                        @if ($studio->image)
                            <img src="{{ asset('storage/'.$studio->image) }}" alt="Photo of {{ $studio->name }}" loading="lazy">
                        @else
                            <span class="studio-image-unavailable">Gambar tidak tersedia</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="studio-empty">No studios are currently available.</p>
            @endforelse
        </div>
        <a class="studio-see-more" href="{{ route('user.studios.index') }}">See More</a>
    </section>

    </div>

    <section id="availability" class="section">
        <div class="availability-panel">
            <div class="availability-heading">
                <div>
                    <h2>Studio Availability Today</h2>
                    <p>Check the nearest available studio slots and reserve your session in real-time.</p>
                </div>
                <a class="availability-see-all" href="{{ route('user.studios.index') }}">SEE ALL <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="availability-slots" id="availability-preview">
                @forelse ($availability as $slot)
                    <a class="availability-slot" href="{{ route('user.bookings.create', ['studio_id' => $slot['studio']->id, 'date' => $slot['date'], 'start_time' => $slot['start'], 'end_time' => $slot['end']]) }}">
                        <span class="availability-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 21h14M7 21V4l9-1v18M16 6h2v15M10 7v2m0 3v2m3-1v1"/></svg></span>
                        <div class="availability-slot-info"><h3>{{ $slot['studio']->name }}</h3><p>{{ $slot['start'] }} - {{ $slot['end'] }} WITA</p></div>
                        <span class="availability-status">Available</span>
                    </a>
                @empty
                    <p class="sub">No available studio slots today. View studios to choose another date.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section">
        <div class="premium">
            <div class="eyebrow">Premium</div>
            <h2 class="section-title" style="margin-top:0">Move Without <span class="gold">Limits</span></h2>
            <p class="sub" style="margin-bottom:18px">Studio bookings, dance classes, and memberships in one simple experience.</p>
            <div class="hero-actions" style="justify-content:center">
                <a class="btn btn-fill" href="{{ route('user.studios.index') }}">Book Studio</a>
                <a class="btn" href="{{ route('user.classes.index') }}">Explore Classes</a>
            </div>
        </div>
    </section>

    <section id="class" class="section dance-classes">
        <h2 class="section-title"><span class="gold">Dance</span> Class</h2>
        <p class="sub">Discover premium dance classes designed to improve your skills, rhythm, and stage presence.</p>
        <div class="dance-class-track" tabindex="0" role="region" aria-label="Dance classes: scroll to explore more classes">
            @forelse ($danceClasses as $danceClass)
                <article class="dance-class-card">
                    <div class="dance-class-photo">
                        @if($danceClass->image)
                            <img src="{{ asset('storage/'.$danceClass->image) }}" alt="{{ $danceClass->name }}" loading="lazy">
                        @endif
                        <span class="dance-class-price">Rp {{ number_format($danceClass->price, 0, ',', '.') }}</span>
                    </div>
                    <div class="dance-class-body">
                        <h3>{{ $danceClass->name }}</h3>
                        <p class="dance-class-coach">{{ \App\Models\DanceClass::LEVELS[$danceClass->level] ?? $danceClass->level }}</p>
                        <a class="dance-class-reserve" href="{{ route('user.classes.index', ['class_id' => $danceClass->id]) }}" aria-label="View schedules - {{ $danceClass->name }}">View Schedules</a>
                        @if($danceClass->description)<p class="dance-class-details">{{ $danceClass->description }}</p>@endif
                    </div>
                </article>
            @empty
                <p class="sub">No dance classes are currently available.</p>
            @endforelse
        </div>
        <a class="dance-class-more" href="{{ route('user.classes.index') }}">SEE MORE</a>
    </section>

    <section class="cta-band">
        <div class="section" style="padding:0">
            <div style="display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap">
                <div>
                    <h2 style="margin:0 0 6px;color:#171717">Get Exclusive Member Benefits</h2>
                    <div style="color:#2a2208">Explore membership plans, prices, and available benefits.</div>
                </div>
                <a class="btn" style="border-color:#171717;color:#171717" href="{{ route('memberships.index') }}">Join Now</a>
            </div>
        </div>
    </section>

    <section id="about" class="section">
        <h2 class="section-title">About <span class="gold">Coach</span></h2>
        <p class="sub">Meet our coach and discover the spirit of our classes.</p>
        <div class="content-card">
            <div class="shot"></div>
            <div>
                <h3>Ricardo</h3>
                <p>A dance coach focused on rhythm, technique, and approachable instruction for beginners and experienced dancers alike.</p>
            </div>
        </div>
    </section>

    <section class="section testimonials" aria-labelledby="testimonial-title">
        <h2 id="testimonial-title" class="section-title">Client Testimonial</h2>
        <p class="sub">Voice of our distinguished performers · Sample testimonials</p>
        <div class="testimonial-grid">
            @foreach ([
                'The most exclusive dance environment I’ve ever experienced. Every detail is perfect.',
                'The most exclusive dance environment I’ve ever experienced. Every detail is perfect.',
                'A sanctuary for artists. The membership benefits offer unparalleled value for serious dancers.',
                'The most exclusive dance environment I’ve ever experienced. Every detail is perfect.',
                'The most exclusive dance environment I’ve ever experienced. Every detail is perfect.',
                'The most exclusive dance environment I’ve ever experienced. Every detail is perfect.',
            ] as $testimonial)
                <figure class="testimonial-card">
                    @if ($loop->index < 3)
                        <blockquote>“{{ $testimonial }}”</blockquote>
                    @endif
                    <figcaption class="testimonial-person">
                        <div class="testimonial-avatar" aria-hidden="true">EV</div>
                        <div><strong>Elena V.</strong><span>Professional Ballerina</span></div>
                    </figcaption>
                    @if ($loop->index >= 3)
                        <blockquote>“{{ $testimonial }}”</blockquote>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>

    <section class="section landing-qna" aria-labelledby="qna-title">
        <h2 id="qna-title" class="section-title">QNA?</h2>
        <div class="qna-list">
            <details class="qna-item">
                <summary>How do I book a private studio?</summary>
                <p>Choose a studio and an available time slot, then log in to continue your reservation. Follow the booking and payment instructions provided.</p>
            </details>
            <details class="qna-item">
                <summary>What are the membership perks?</summary>
                <p>Membership benefits depend on your selected plan. Visit the Membership page to review plans, prices, and benefits before applying.</p>
            </details>
            <details class="qna-item">
                <summary>What amenities are provided in the studios?</summary>
                <p>Amenities and capacity vary by studio. Check the studio description or contact our team to confirm that the facilities you need are available.</p>
            </details>
        </div>
    </section>
@endsection
