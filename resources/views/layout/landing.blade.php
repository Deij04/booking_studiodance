<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logoorange.png') }}">
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
        html{scroll-padding-top:100px;scroll-behavior:smooth}
        .site-header{position:sticky;top:0;z-index:50;background:rgba(17,17,17,.75);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-bottom:1px solid rgba(240,198,58,.16);box-shadow:0 6px 24px rgba(0,0,0,.18)}
        .nav{display:flex;justify-content:space-between;align-items:center;gap:20px;padding-top:16px;padding-bottom:16px}
        .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.5px}
        .brand-logo{display:block;width:48px;height:56px;object-fit:contain;flex-shrink:0}
        .nav-links{display:flex;gap:26px;align-items:center;font-size:13px;text-transform:uppercase}
        .nav-links a,.nav-links button{transition:color .2s,background-color .2s,border-color .2s,box-shadow .2s}
        .nav-links>a:not(.btn){position:relative;padding:10px 0}
        .nav-links>a:not(.btn)::after{content:"";position:absolute;left:0;right:0;bottom:3px;height:2px;background:var(--gold);transform:scaleX(0);transition:transform .2s}
        .nav-links>a:not(.btn):hover,.nav-links>a:not(.btn):focus-visible{color:var(--gold)}
        .nav-links>a:not(.btn):hover::after,.nav-links>a:not(.btn):focus-visible::after{transform:scaleX(1)}
        .nav-links .btn{background:transparent;color:var(--text);font:inherit;font-weight:700;cursor:pointer}
        .nav-links .btn:hover,.nav-links .btn:focus-visible{background:var(--gold);border-color:var(--gold);color:#171717;box-shadow:0 4px 16px rgba(240,198,58,.15)}
        .site-header a:focus-visible,.site-header button:focus-visible{outline:2px solid var(--gold);outline-offset:5px}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:13px 22px;border-radius:999px;border:1px solid rgba(240,198,58,.45);font-weight:700}
        .nav-links .btn-login{border-radius:6px;border-color:var(--gold);color:var(--gold)}
        .nav-links .btn-login:hover,.nav-links .btn-login:focus-visible{background:rgba(240,198,58,.12);color:var(--gold)}
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
        .visual:after{content:"";position:absolute;right:18px;bottom:18px;width:120px;height:140px;background:url('{{ asset('images/logoorange.png') }}') center / contain no-repeat}
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
            html{scroll-padding-top:180px}
            .hero-grid,.card-row,.studio-grid,.class-grid,.availability,.content-card,.footer-grid{grid-template-columns:1fr}
            .nav{gap:16px;flex-direction:column}
            .nav-links{flex-wrap:wrap;justify-content:center}
        }
        @media (max-width: 480px){
            .nav{padding:12px 16px;gap:8px}
            .nav-links{gap:8px 16px;font-size:11px}
            .nav-links .btn{padding:9px 14px}
            .nav .brand{font-size:13px}
        }
        .nav-toggle{display:none;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;border:1px solid rgba(240,198,58,.35);border-radius:12px;background:transparent;color:var(--gold);cursor:pointer}
        .nav-toggle svg{width:24px;height:24px}
        .nav-toggle .menu-close-icon{display:none}
        @media (max-width:1024px){
            html{scroll-padding-top:90px}
            .nav{position:relative;flex-direction:row;flex-wrap:wrap;gap:12px;padding:12px 20px}
            .nav .brand{font-size:13px;min-width:0}
            .nav-links{width:100%;flex-direction:column;align-items:stretch;gap:6px;font-size:14px}
            .nav-ready .nav-toggle{display:flex}
            .nav-ready .nav-links{display:none;position:absolute;top:100%;left:0;right:0;width:auto;margin:0;padding:12px 20px 20px;background:#171717;border-bottom:1px solid rgba(240,198,58,.25);box-shadow:0 14px 24px rgba(0,0,0,.3);max-height:calc(100dvh - 80px);overflow-y:auto;overscroll-behavior:contain}
            .nav-ready.menu-is-open .nav-links{display:flex}
            .menu-is-open .menu-open-icon{display:none}
            .menu-is-open .menu-close-icon{display:block}
            .nav-links>a:not(.btn){display:flex;align-items:center;min-height:44px;padding:12px 14px;border-radius:10px}
            .nav-links>a:not(.btn)::after{display:none}
            .nav-links>a:not(.btn):hover,.nav-links>a:not(.btn):focus-visible{background:rgba(240,198,58,.1)}
            .nav-links .btn{min-height:44px;width:100%;padding:12px 16px}
            .nav-links form{margin:0}
        }
        @media (max-width:600px){
            .section{padding:32px 18px}
            .hero{padding-top:32px}
            .hero-grid{gap:24px}
            h1{font-size:clamp(36px,10vw,56px);line-height:1.08;overflow-wrap:break-word}
            .section-title{font-size:30px}
            .lead{font-size:14px}
            .visual{min-height:280px}
            .hero-actions .btn{flex:1;min-height:44px;text-align:center}
            .card-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:24px}
            .mini-card{padding:14px}
            .content-card{padding:16px}
            .premium{padding:24px 16px}
            .info-pill{gap:12px;padding:16px;font-size:13px}
            .tag{flex-shrink:0}
        }
        @media (max-width:360px){.nav{padding:10px 12px}.nav .brand{font-size:11px;gap:7px}.brand-logo{width:36px;height:42px}}
        @media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}.nav-links a,.nav-links button,.nav-links>a::after{transition:none}}
    </style>
    @stack('styles')
    <style>
        .academy-footer{margin-top:36px;background:#1b1b1b;color:#969696;font-size:13px;line-height:1.8}
        .academy-footer-inner{max-width:1180px;margin:0 auto;padding:42px 24px 30px}
        .academy-footer-grid{display:grid;grid-template-columns:1.15fr 1fr 1fr 1fr;gap:64px}
        .academy-footer-brand{display:inline-flex;align-items:center;gap:6px;color:#d8b433;font-size:11px;font-weight:700;letter-spacing:-.2px;white-space:nowrap}
        .academy-footer-brand img{display:block;width:38px;height:44px;object-fit:contain;flex-shrink:0}
        .academy-footer-intro p{max-width:230px;margin:18px 0;color:#929292;line-height:1.65}
        .academy-footer-social{display:flex;gap:10px}
        .academy-footer-social button{display:grid;place-items:center;width:32px;height:32px;border:0;border-radius:50%;background:#323232;color:#aaa;cursor:pointer}
        .academy-footer-social button:disabled{cursor:default}
        .academy-footer-social svg{width:15px;height:15px}
        .academy-footer-share-status{display:block;font-size:11px;margin-top:6px}
        .academy-footer-column{display:flex;flex-direction:column;align-items:flex-start;gap:8px}
        .academy-footer-column h2{margin:0 0 10px;color:#b8b8b8;font-size:14px;font-weight:500}
        .academy-footer-column address{display:flex;flex-direction:column;gap:10px;font-style:normal}
        .academy-footer-contact{display:flex;align-items:center;gap:10px}
        .academy-footer-contact svg{width:17px;height:17px;flex-shrink:0;color:#c9a600}
        .academy-footer a:hover,.academy-footer-social button:not(:disabled):hover{color:#f0c63a}
        .academy-footer a:focus-visible,.academy-footer button:focus-visible{outline:2px solid #f0c63a;outline-offset:4px}
        .academy-footer-bottom{border-top:1px solid rgba(255,255,255,.035);padding:16px 24px;text-align:center;color:#aa8919;font-size:11px}
        @media(max-width:900px){.academy-footer-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:32px 40px}}
        @media(max-width:520px){.academy-footer-inner{padding:32px 20px 24px}.academy-footer-grid{gap:28px 20px}.academy-footer-intro{grid-column:1 / -1}.academy-footer-intro p{max-width:300px}.academy-footer-column{font-size:12px}.academy-footer-column:last-child{grid-column:1 / -1}.academy-footer-bottom{font-size:10px;padding:14px 16px}}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <x-navbar />

    @yield('content')

    <x-footer />
</body>
</html>
