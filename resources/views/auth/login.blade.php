<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logoorange.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login | Orange Dance Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --gold:#f2c83f; --gold-dark:#987a18; --panel:#151515; --field:#555; --text:#cbc9c9; }
        *,*::before,*::after { box-sizing:border-box; }
        html,body { width:100%; height:100%; margin:0; overflow:hidden; }
        body { background:#0b0b0b; color:var(--text); font-family:'Poppins',sans-serif; }
        button,input { font:inherit; }
        .login-page { display:grid; grid-template-columns:60% 40%; width:100%; height:100vh; height:100dvh; min-height:0; }
        .login-panel { position:relative; z-index:2; display:flex; min-height:0; flex-direction:column; padding:clamp(40px,7vh,72px) clamp(52px,6.2vw,96px) clamp(26px,6vh,62px); background:var(--panel); }
        .login-content { width:min(100%,400px); margin:auto; }
        .back-link { display:inline-flex; align-items:center; gap:8px; align-self:flex-start; flex-shrink:0; margin-bottom:20px; padding:9px 13px; border:1px solid rgba(242,200,63,.3); border-radius:10px; color:var(--gold); text-decoration:none; font-size:13px; transition:background-color .2s,color .2s; }
        .back-link svg { width:18px; height:18px; }
        .back-link:hover { background:var(--gold); color:#111; }
        .back-link:focus-visible { outline:2px solid var(--gold); outline-offset:4px; }
        h1 { margin:0 0 clamp(24px,4.6vh,46px); transform:translateY(-20px); color:var(--gold); font-size:clamp(29px,2.5vw,38px); font-weight:800; line-height:1; text-align:center; }
        .field + .field { margin-top:clamp(14px,2.3vh,24px); }
        label { display:block; margin-bottom:clamp(5px,.8vh,8px); font-size:clamp(13px,1.05vw,16px); font-weight:300; line-height:1.25; }
        .input-wrap { position:relative; }
        input { display:block; width:100%; height:clamp(43px,4.7vh,48px); border:1px solid transparent; border-radius:9px; outline:none; background:var(--field); color:#fff; padding:0 14px; font-size:clamp(11px,.85vw,13px); font-weight:300; transition:border-color .2s,box-shadow .2s; }
        input::placeholder { color:#d2d2d2; opacity:1; }
        input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(242,200,63,.12); }
        .password-input { padding-right:58px; }
        .password-toggle { position:absolute; top:50%; right:0; display:grid; width:52px; height:28px; padding:0; transform:translateY(-50%); place-items:center; border:0; border-left:1px solid rgba(255,255,255,.8); background:transparent; color:#d4d4d4; cursor:pointer; }
        .password-toggle svg { width:21px; height:21px; }
        .password-toggle[aria-pressed="true"] { color:var(--gold); }
        .forgot { margin-top:clamp(8px,1.5vh,15px); text-align:right; }
        a { color:var(--gold); text-decoration-color:currentColor; text-underline-offset:2px; }
        .forgot a { font-size:clamp(13px,1.15vw,16px); }
        .error { margin-bottom:16px; border:1px solid rgba(255,123,123,.25); border-radius:8px; background:#3c1b1b; color:#ffd7d7; padding:10px 13px; font-size:13px; }
        .submit-button { width:100%; height:clamp(43px,4.7vh,48px); margin-top:clamp(48px,10vh,100px); border:0; border-radius:9px; background:linear-gradient(100deg,#f4ce47 0%,#c6a32f 56%,#8c7119 100%); color:#111; font-size:clamp(18px,1.5vw,23px); font-weight:600; cursor:pointer; transition:filter .2s,transform .2s; }
        .submit-button:hover { filter:brightness(1.08); }
        .submit-button:active { transform:translateY(1px); }
        .register { margin:clamp(7px,1vh,10px) 0 0; font-size:clamp(12px,1.15vw,16px); font-weight:300; text-align:center; }
        .register a { text-decoration:none; }
        footer { position:absolute; right:clamp(52px,6.2vw,96px); bottom:clamp(26px,6vh,62px); left:clamp(52px,6.2vw,96px); width:min(calc(100% - 104px),400px); margin:0 auto; border-top:1px solid rgba(255,255,255,.08); padding-top:clamp(12px,2.2vh,21px); color:var(--gold); font-size:clamp(8px,.75vw,11px); text-align:center; }
        .visual-panel { position:relative; min-width:0; min-height:0; overflow:hidden; background:#090705 url('/images/Login.jpg') 70% 44% / cover no-repeat; }
        .visual-panel::after { position:absolute; inset:0; content:''; background:linear-gradient(to bottom,rgba(0,0,0,.06) 45%,rgba(0,0,0,.72) 100%); pointer-events:none; }
        .academy-name { position:absolute; z-index:1; right:clamp(22px,2.7vw,42px); bottom:clamp(45px,8vh,82px); margin:0; width:clamp(90px,10vw,140px); height:auto; object-fit:contain; }

        @media (max-width:700px) {
            .login-page { display:block; position:relative; }
            .visual-panel { position:absolute; inset:0; background-position:70% 34%; }
            .visual-panel::after { background:rgba(0,0,0,.68); }
            .academy-name { display:none; }
            .login-panel { width:100%; height:100%; padding:max(22px,env(safe-area-inset-top)) 22px max(14px,env(safe-area-inset-bottom)); background:linear-gradient(90deg,rgba(17,17,17,.78),rgba(17,17,17,.68)); }
            .login-content { width:min(100%,360px); margin:auto; border:1px solid rgba(255,255,255,.08); border-radius:16px; background:rgba(18,18,18,.86); padding:clamp(20px,3.5vh,28px) 18px; box-shadow:0 22px 55px rgba(0,0,0,.35); backdrop-filter:blur(5px); }
            h1 { margin-bottom:clamp(22px,4.2vh,36px); transform:translateY(-14px); font-size:clamp(28px,8vw,36px); }
            label { font-size:clamp(13px,3.7vw,15px); }
            input { height:46px; font-size:12px; }
            .submit-button { height:46px; margin-top:clamp(38px,7vh,60px); }
            .register { font-size:clamp(12px,3.5vw,15px); }
            footer { right:22px; bottom:max(14px,env(safe-area-inset-bottom)); left:22px; width:min(calc(100% - 44px),360px); padding-top:11px; font-size:8px; }
        }

        @media (max-height:680px) {
            .login-panel { padding-top:22px; padding-bottom:14px; }
            h1 { margin-bottom:22px; font-size:32px; }
            .field + .field { margin-top:11px; }
            label { margin-bottom:4px; font-size:14px; }
            input,.submit-button { height:44px; }
            .forgot { margin-top:6px; }
            .submit-button { margin-top:36px; font-size:21px; }
            .register { margin-top:5px; font-size:13px; }
            footer { padding-top:9px; font-size:9px; }
        }
        @media (max-width:700px), (max-height:520px) {
            html,body{height:auto;min-height:100%;overflow:auto}
            .login-page{height:auto;min-height:100vh;min-height:100dvh}
            .login-panel{height:auto;min-height:100vh;min-height:100dvh;gap:24px}
            .login-content{flex-shrink:0;margin:auto;width:min(100%,400px)}
            .back-link{min-height:44px;margin-bottom:0}
            footer{position:static;width:100%;max-width:400px;margin:0 auto;flex-shrink:0;line-height:1.6}
        }
        @media (max-width:700px) {
            .login-panel{padding:max(16px,env(safe-area-inset-top)) 16px max(20px,env(safe-area-inset-bottom))}
            .login-content{padding:28px 20px}
            h1{transform:none;margin-bottom:28px}
            input{height:48px;font-size:16px}
            label{font-size:14px}
            .field + .field{margin-top:20px}
            .password-toggle{height:44px}
            .forgot a{display:inline-flex;align-items:center;min-height:44px;font-size:13px}
            .submit-button{height:48px;margin-top:24px;font-size:18px}
            .register{font-size:13px;line-height:1.8;margin-top:12px}
            .register a{display:inline-flex;align-items:center;min-height:44px}
            footer{font-size:9px}
        }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <main class="login-page">
        <section class="login-panel" aria-labelledby="login-title">
            <a class="back-link" href="{{ route('landing') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h14"/></svg>
                Back to Home
            </a>
            <div class="login-content">
                <h1 id="login-title">LOGIN</h1>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="error" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Example@gmail.com" autocomplete="email" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input class="password-input" id="password" type="password" name="password" placeholder="*****" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5 9.5 5.5 9.5 5.5-3.5 5.5-9.5 5.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="forgot"><a href="#">Forgot Password</a></div>
                    <button class="submit-button" type="submit">Login</button>
                    <p class="register">Don't have an account? <a href="{{ route('register') }}">Register</a></p>
                </form>
            </div>
            <footer>&copy; 2026 ORANGEDANCEACADEMY . All rights reserved.</footer>
        </section>
        <section class="visual-panel" aria-label="Orange Dance Academy">
            <img class="academy-name" src="{{ asset('images/logoorange.png') }}" alt="Orange Dance Academy" width="120" height="141">
        </section>
    </main>
    <script>
        const toggle = document.querySelector('.password-toggle');
        const password = document.querySelector('#password');
        toggle.addEventListener('click', () => {
            const willShow = password.type === 'password';
            password.type = willShow ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(willShow));
            toggle.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
