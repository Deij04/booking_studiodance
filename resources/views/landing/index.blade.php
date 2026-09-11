@extends('layout.landing')

@section('content')
    <main id="home" class="hero section">
        <div class="hero-grid">
            <div>
                <div class="eyebrow">Orange Dance Academy</div>
                <h1><span class="gold">Dance</span> Without Limits,<br> <span class="gold">Together</span></h1>
                <p class="lead">Orange Dance Academy membantu reservasi studio, cek jadwal, dan mendaftar class dalam satu platform yang rapi, cepat, dan mudah dipakai.</p>
                <div class="hero-actions">
                    <a class="btn btn-fill" href="#studio">Book Studio Now</a>
                    <a class="btn" href="#class">View All Classes</a>
                </div>
                <div class="card-row">
                    <div class="mini-card"><strong>Reservasi Studio</strong><span>Booking slot tanpa ribet</span></div>
                    <div class="mini-card"><strong>Jadwal Studio</strong><span>Lihat ketersediaan realtime</span></div>
                    <div class="mini-card"><strong>Kelas Dance</strong><span>Pilihan class untuk semua level</span></div>
                    <div class="mini-card"><strong>Membership</strong><span>Plan aktif dan upgrade</span></div>
                </div>
            </div>
            <div class="visual"></div>
        </div>
    </main>

    <section id="studio" class="section">
        <h2 class="section-title">Our <span class="gold">Studio</span></h2>
        <p class="sub">Ruang latihan yang nyaman, terang, dan siap dipakai untuk kelas maupun reservasi pribadi.</p>
        <div class="studio-grid">
            <div class="content-card">
                <div>
                    <h3>Studio 2</h3>
                    <p>Studio modern dengan pencahayaan hangat, cocok untuk latihan rutin, rehearsal, dan performance kecil.</p>
                </div>
                <div class="shot"></div>
            </div>
            <div class="content-card">
                <div class="shot alt"></div>
                <div>
                    <h3>Studio 3</h3>
                    <p>Studio yang lebih intimate untuk sesi privat, latihan detail, dan kelas dengan suasana fokus.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Studio <span class="gold">Availability</span> Today</h2>
        <p class="sub">Contoh jadwal tersedia untuk membantu pengecekan slot sebelum reservasi.</p>
        <div class="availability">
            <div class="info-pill"><div>Studio 2 - 08:00 - 10:00</div><span class="tag">Available</span></div>
            <div class="info-pill"><div>Studio 3 - 08:00 - 10:00</div><span class="tag">Available</span></div>
            <div class="info-pill"><div>Studio 2 - 15:00 - 17:00</div><span class="tag">Available</span></div>
            <div class="info-pill"><div>Studio 3 - 18:00 - 20:00</div><span class="tag">Available</span></div>
        </div>
    </section>

    <section class="section">
        <div class="premium">
            <div class="eyebrow">Premium</div>
            <h2 class="section-title" style="margin-top:0">Move Without <span class="gold">Limits</span></h2>
            <p class="sub" style="margin-bottom:18px">Reservasi studio, kelas, dan membership dalam satu alur yang simpel.</p>
            <div class="hero-actions" style="justify-content:center">
                <a class="btn btn-fill" href="#studio">Book Studio</a>
                <a class="btn" href="#class">Explore Classes</a>
            </div>
        </div>
    </section>

    <section id="class" class="section">
        <h2 class="section-title"><span class="gold">Dance</span> Class</h2>
        <p class="sub">Pilihan class untuk berbagai level dan gaya dance.</p>
        <div class="class-grid">
            <div class="class-card"><div class="poster"><div class="price">$15</div></div><div class="class-body"><h4>HipHop Beginner</h4><p>Coach: Alex</p></div></div>
            <div class="class-card"><div class="poster"><div class="price">$30</div></div><div class="class-body"><h4>Heels Class</h4><p>Coach: Sarah</p></div></div>
            <div class="class-card"><div class="poster"><div class="price">$20</div></div><div class="class-body"><h4>Afro Fusion</h4><p>Coach: Zed</p></div></div>
            <div class="class-card"><div class="poster"><div class="price">$35</div></div><div class="class-body"><h4>Contemporary</h4><p>Coach: Elena</p></div></div>
        </div>
        <div class="bar"></div>
    </section>

    <section class="cta-band">
        <div class="section" style="padding:0">
            <div style="display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap">
                <div>
                    <h2 style="margin:0 0 6px;color:#171717">Get Exclusive Member Benefits</h2>
                    <div style="color:#2a2208">Priority booking, discounts, and access to plan membership.</div>
                </div>
                <a class="btn" style="border-color:#171717;color:#171717" href="{{ route('login') }}">Join Now</a>
            </div>
        </div>
    </section>

    <section id="about" class="section">
        <h2 class="section-title">About <span class="gold">Coach</span></h2>
        <p class="sub">Potret singkat pengajar dan suasana kelas.</p>
        <div class="content-card">
            <div class="shot"></div>
            <div>
                <h3>Ricardo</h3>
                <p>Coach dance dengan fokus pada ritme, teknik, dan pengajaran yang ringan dipahami. Cocok untuk pemula hingga level lanjut.</p>
            </div>
        </div>
    </section>

@endsection
