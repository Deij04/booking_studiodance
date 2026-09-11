<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Orange Dance Academy') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#111111;--panel:#191919;--gold:#f0c63a;--gold2:#9d7e13;--text:#e7e2d0;--muted:#b5b0a1}
        *{box-sizing:border-box} html,body{margin:0;font-family:Poppins,sans-serif;background:var(--bg);color:var(--text)}
        a{text-decoration:none;color:inherit}
        .nav,.section,.footer{max-width:1180px;margin:0 auto;padding:0 24px}
        .nav{display:flex;justify-content:space-between;align-items:center;padding-top:18px}
        .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.5px}
        .brand-badge{width:34px;height:34px;border-radius:50%;background:radial-gradient(circle at 30% 30%,#ffe27a,var(--gold2));box-shadow:0 0 0 2px rgba(240,198,58,.18)}
        .nav-links{display:flex;gap:26px;align-items:center;font-size:13px;text-transform:uppercase}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:13px 22px;border-radius:999px;border:1px solid rgba(240,198,58,.45);font-weight:700}
        .btn-fill{background:linear-gradient(180deg,#f4cf52,var(--gold2));color:#171717;border:none}
        .hero{padding:56px 0 24px;position:relative;overflow:hidden}
        .hero:before,.hero:after{content:"";position:absolute;inset:auto;pointer-events:none}
        .hero:before{width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(240,198,58,.12),transparent 70%);left:-120px;top:-120px}
        .hero:after{width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(240,198,58,.08),transparent 70%);right:-120px;bottom:-120px}
        .hero-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:34px;align-items:center}
        .eyebrow{display:inline-block;padding:6px 12px;border:1px solid rgba(240,198,58,.25);border-radius:999px;color:var(--gold);font-size:12px;margin-bottom:18px}
        h1{margin:0;font-size:clamp(44px,6vw,82px);line-height:.92;letter-spacing:-1px}
        h1 .gold{color:var(--gold)}
        .lead{max-width:760px;color:var(--muted);line-height:1.8;font-size:15px;margin:18px 0 26px}
        .hero-actions{display:flex;gap:14px;flex-wrap:wrap}
        .card-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:34px}
        .mini-card,.content-card,.feature-card,.class-card,.faq-item,.info-pill{background:rgba(25,25,25,.95);border:1px solid rgba(240,198,58,.16);box-shadow:0 8px 30px rgba(0,0,0,.22)}
        .mini-card{min-height:108px;border-radius:18px;padding:20px;display:flex;flex-direction:column;justify-content:space-between}
        .mini-card strong{font-size:14px}
        .mini-card span{color:var(--muted);font-size:12px}
        .visual{min-height:520px;border-radius:28px;background:
            radial-gradient(circle at 60% 28%,rgba(240,198,58,.20),transparent 20%),
            radial-gradient(circle at 58% 48%,rgba(255,186,100,.18),transparent 18%),
            linear-gradient(180deg,rgba(49,36,12,.9),rgba(15,15,15,.95)),
            url('/images/login-reference.png');
            background-size:cover;background-position:center;border:1px solid rgba(240,198,58,.22);position:relative;overflow:hidden}
        .visual:after{content:"ORANGE DANCE ACADEMY";position:absolute;right:18px;bottom:18px;font-size:22px;font-weight:800;color:rgba(240,198,58,.35);letter-spacing:.5px}
        .section{padding:40px 24px}
        .section-title{font-size:40px;text-align:center;margin:0 0 6px}
        .section-title .gold{color:var(--gold)}
        .sub{color:var(--muted);text-align:center;max-width:680px;margin:0 auto 26px;line-height:1.7}
        .studio-grid,.class-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
        .content-card{border-radius:22px;padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:center}
        .shot{min-height:220px;border-radius:18px;background:linear-gradient(135deg,#46301b,#d7b36b 50%,#2b2315);border:1px solid rgba(240,198,58,.28)}
        .shot.alt{background:linear-gradient(135deg,#1e1b16,#83602f 45%,#edd28b)}
        .content-card h3{margin:0 0 8px;font-size:24px;color:var(--gold)}
        .content-card p{margin:0;color:var(--muted);line-height:1.8}
        .availability{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
        .info-pill{border-radius:20px;padding:18px 20px;display:flex;justify-content:space-between;align-items:center}
        .tag{font-size:12px;color:#171717;background:var(--gold);padding:6px 10px;border-radius:999px;font-weight:700}
        .premium{border-radius:24px;padding:34px;text-align:center;background:linear-gradient(90deg,rgba(240,198,58,.18),rgba(25,25,25,.98) 55%);border:1px solid rgba(240,198,58,.22)}
        .class-card{border-radius:22px;overflow:hidden}
        .poster{height:190px;background:linear-gradient(180deg,rgba(0,0,0,.12),rgba(0,0,0,.8)), linear-gradient(135deg,#2b2b2b,#946b18);position:relative}
        .price{position:absolute;top:12px;right:12px;background:var(--gold);color:#171717;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:700}
        .class-body{padding:16px}
        .class-body h4{margin:0 0 4px}
        .class-body p{margin:0;color:var(--muted);font-size:13px}
        .bar{height:1px;background:rgba(240,198,58,.2);margin:28px 0}
        .cta-band{background:linear-gradient(90deg,#d3ae2f,#9e7c12);color:#171717;border-radius:0;padding:32px 24px;margin-top:34px}
        .cta-band .section{padding-top:0;padding-bottom:0}
        .faq-item{border-radius:16px;padding:16px 18px;color:var(--text);margin-bottom:12px}
        .footer{padding:38px 24px 28px;color:var(--muted)}
        .footer-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:24px}
        .small{font-size:13px;line-height:1.8}
        @media (max-width: 900px){
            .hero-grid,.card-row,.studio-grid,.class-grid,.availability,.content-card,.footer-grid{grid-template-columns:1fr}
            .nav{gap:16px;flex-direction:column}
            .nav-links{flex-wrap:wrap;justify-content:center}
        }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <x-navbar />

    @yield('content')

    <x-footer />
</body>
</html>
