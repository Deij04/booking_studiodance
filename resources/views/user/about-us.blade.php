@extends('layout.landing')

@push('styles')
    <style>
        .about-page{position:relative;isolation:isolate;max-width:1180px;margin:0 auto;padding:64px 24px 32px}
        .about-page::before{content:'';position:absolute;z-index:-1;inset:0;pointer-events:none;background:radial-gradient(ellipse 48% 480px at 50% 240px,rgba(190,151,24,.3),rgba(150,116,15,.12) 55%,transparent 100%);filter:blur(32px)}
        .about-heading{text-align:center;max-width:780px;margin:0 auto 48px}
        .about-heading h1{font-size:clamp(38px,6vw,68px);line-height:1.15;letter-spacing:-.02em}
        .about-heading p{color:var(--muted);font-size:17px;line-height:1.8;margin:22px 0 0}
        .about-story{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
        .about-visual{position:relative;isolation:isolate;overflow:hidden;border-radius:28px;min-height:420px;border:1px solid rgba(240,198,58,.3);background:#19170f}
        .about-visual img{display:block;width:100%;height:420px;padding:48px;object-fit:contain}
        .about-copy h2,.about-values h2,.about-invitation h2{font-size:clamp(28px,4vw,38px);line-height:1.25;margin:0 0 18px}
        .about-copy p{color:var(--muted);font-size:16px;line-height:1.85;margin:0 0 16px}
        .about-member-banner{position:relative;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-top:36px;padding:24px 0;color:#17150d}
        .about-member-banner::before{content:'';position:absolute;z-index:-1;inset:0 calc((min(1180px,100vw) - 48px - 100vw)/2);background:linear-gradient(100deg,#82702c,#e0bd43 35%,#8b762c);pointer-events:none}
        .about-member-banner h2{margin:0 0 8px;font-size:clamp(22px,3vw,28px);font-weight:500;line-height:1.25}
        .about-member-banner p{max-width:560px;margin:0;font-size:13px;line-height:1.65}
        .about-member-banner .btn{flex-shrink:0;min-height:44px;padding:12px 24px;background:#111;color:#f0c63a;border:1px solid #111;font-size:12px}
        .about-member-banner .btn:hover{background:#292416}
        @media(max-width:600px){.about-member-banner{flex-direction:column;align-items:flex-start;padding:24px 0;gap:20px}.about-member-banner::before{inset:0 -18px}.about-member-banner p{font-size:14px}.about-member-banner .btn{min-height:48px;width:100%}}
        .about-purpose{max-width:900px;margin:0 auto;padding-top:64px}
        .about-vision{text-align:center}
        .about-purpose h2{margin:0;font-size:clamp(28px,4vw,38px);font-weight:800;line-height:1.2;text-transform:uppercase}
        .about-purpose h2 span{color:var(--gold)}
        .about-vision h2{max-width:720px;margin:0 auto;padding-bottom:12px;border-bottom:2px solid var(--gold)}
        .about-purpose p{color:var(--muted);font-size:17px;line-height:1.6}
        .about-vision p{max-width:780px;margin:16px auto 0}
        .about-mission{display:grid;grid-template-columns:60px minmax(0,1fr);gap:24px;margin-top:48px;align-items:stretch}
        .about-mission h2{writing-mode:vertical-rl;transform:rotate(180deg);text-align:center;justify-self:center;font-size:38px}
        .about-mission-list{list-style:none;margin:0;padding:0}
        .about-mission-list li{padding:24px 0;border-bottom:2px solid var(--gold)}
        .about-mission-list li:first-child{padding-top:0}
        .about-mission-list li:last-child{border-bottom:0;padding-bottom:0}
        .about-mission-list p{margin:0}
        @media(max-width:600px){.about-purpose{padding-top:40px}.about-purpose p{font-size:16px}.about-mission{grid-template-columns:1fr;gap:20px;margin-top:32px}.about-mission h2{writing-mode:horizontal-tb;transform:none;font-size:28px}.about-mission-list li{padding:20px 0}}
        .about-values{padding:64px 0;text-align:center}
        .about-value-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin-top:30px;text-align:left}
        .about-value{padding:28px;border-radius:22px;background:#191919;border:1px solid rgba(240,198,58,.2)}
        .about-value-number{display:block;font-size:13px;letter-spacing:.12em;color:var(--gold);margin-bottom:24px}
        .about-value h3{font-size:20px;margin:0 0 12px;color:#e8e8e8}
        .about-value p{color:var(--muted);font-size:15px;line-height:1.8;margin:0}
        .about-invitation{text-align:center;padding:40px 24px;border-radius:26px;border:1px solid rgba(240,198,58,.25);background:linear-gradient(110deg,rgba(240,198,58,.14),#191919)}
        .about-invitation p{color:var(--muted);line-height:1.8;margin:0 auto 26px;max-width:640px}
        .about-actions{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
        .about-location-map{display:block;width:100%;height:400px;margin:0 0 24px;border:1px solid rgba(240,198,58,.25);border-radius:16px;background:#191919}
        @media(max-width:600px){.about-location-map{height:300px}}
        .about-page a:focus-visible{outline:2px solid var(--gold);outline-offset:5px}
        @media(max-width:900px){.about-story{grid-template-columns:1fr;gap:28px}.about-value-grid{grid-template-columns:1fr}}
        @media(max-width:600px){.about-page{padding:40px 18px 24px}.about-heading{margin-bottom:32px}.about-heading p{font-size:16px}.about-visual,.about-visual img{min-height:300px;height:300px}.about-values{padding:40px 0}.about-value{padding:24px}.about-invitation{padding:32px 20px}.about-actions .btn{width:100%;min-height:54px}}
    </style>
@endpush

@section('content')
    <main class="about-page">
        <header class="about-heading">
            <span class="eyebrow">Orange Dance Academy</span>
            <h1>About <span class="gold">Us</span></h1>
            <p>A space to move, learn, and express yourself. Find your rhythm with Orange Dance Academy.</p>
        </header>

        <section class="about-story" aria-labelledby="about-story-title">
            <div class="about-visual">
                <img src="{{ asset('images/logoorange.png') }}" alt="Orange Dance Academy logo">
            </div>
            <div class="about-copy">
                <span class="eyebrow">Move Without Limits</span>
                <h2 id="about-story-title">Every movement tells a <span style="color:var(--gold)">story.</span></h2>
                <p>Orange Dance Academy is a place to explore dance, develop your technique, and build confidence through movement.</p>
                <p>Whether you are just starting out or refining your skills, discover classes and practice spaces that suit your journey.</p>
                <p>Explore our studios, discover dance classes, and browse membership plans in one place.</p>
            </div>
        </section>

        <section class="about-member-banner" aria-labelledby="about-member-title">
            <div>
                <h2 id="about-member-title">Get Exclusive Member Benefits</h2>
                <p>Unlock priority booking, discounts on merchandise, and exclusive workshops access with our premium membership plans.</p>
            </div>
            <a class="btn" href="{{ route('memberships.index') }}">Join Now</a>
        </section>

        <section class="about-purpose" aria-label="Our vision and mission">
            <div class="about-vision">
                <h2><span>Our</span> Vision</h2>
                <p>To become a leading dance academy and creative studio in Bali that inspires creativity, develops talent, and provides innovative dance and studio services for the community.</p>
            </div>
            <div class="about-mission">
                <h2 id="about-mission-title"><span>Our</span> Mission</h2>
                <ul class="about-mission-list" aria-labelledby="about-mission-title">
                    <li><p>To provide high-quality dance education through professional instructors and structured training programs for all age groups and skill levels.</p></li>
                    <li><p>To create a comfortable, creative, and inclusive environment that supports individuals in developing their talent, confidence, and passion for dance.</p></li>
                    <li><p>To offer efficient and accessible studio rental services that support dance practices, rehearsals, video production, and other creative activities.</p></li>
                </ul>
            </div>
        </section>

        <section class="about-values" aria-labelledby="about-values-title">
            <h2 id="about-values-title">Why <span style="color:var(--gold)">Choose Us</span></h2>
            <div class="about-value-grid">
                <article class="about-value">
                    <span class="about-value-number">01 / STUDIO</span>
                    <h3>Studios that fit your needs</h3>
                    <p>Find a studio for individual or group practice. Choose the space and schedule that work best for you.</p>
                </article>
                <article class="about-value">
                    <span class="about-value-number">02 / DANCE CLASS</span>
                    <h3>Classes that help you grow</h3>
                    <p>Explore classes to improve your technique, discover dance styles, and build confidence with every movement.</p>
                </article>
                <article class="about-value">
                    <span class="about-value-number">03 / CONVENIENCE</span>
                    <h3>Your dance needs in one place</h3>
                    <p>Browse studios, explore classes, and choose a membership plan through one website to make planning your practice easier.</p>
                </article>
            </div>
        </section>

        <section class="about-invitation" aria-labelledby="about-invitation-title">
            <h2 id="about-invitation-title">Our <span style="color:var(--gold)">Location</span></h2>
            <p>Orange Dance Academy<br>Jl. Pura Demak Jl. Pura Demak Lange 3, Pemecutan Klod, Kec. Denpasar Bar., Kota Denpasar</p>
            <iframe class="about-location-map" title="Map of Orange Dance Academy on Jl. Pura Demak, Denpasar" src="https://www.google.com/maps?q=Orange%20Dance%20Academy%2C%20Jl.%20Pura%20Demak%20Jl.%20Pura%20Demak%20Lange%203%2C%20Pemecutan%20Klod%2C%20Kec.%20Denpasar%20Bar.%2C%20Kota%20Denpasar&amp;z=17&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            <div class="about-actions">
                <a class="btn btn-fill" href="https://www.google.com/maps/search/?api=1&amp;query=Orange%20Dance%20Academy%2C%20Jl.%20Pura%20Demak%20Jl.%20Pura%20Demak%20Lange%203%2C%20Pemecutan%20Klod%2C%20Kec.%20Denpasar%20Bar.%2C%20Kota%20Denpasar" target="_blank" rel="noopener noreferrer">Open Google Maps</a>
            </div>
        </section>
    </main>
@endsection
