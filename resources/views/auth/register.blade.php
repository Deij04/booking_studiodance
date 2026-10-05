<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logoorange.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Register | Orange Dance Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --gold:#f2c83f; --gold-dark:#987a18; --panel:#151515; --field:#555; --text:#cbc9c9; }
        *,*::before,*::after { box-sizing:border-box; }
        html,body { width:100%; min-height:100%; margin:0; }
        body { background:#0b0b0b; color:var(--text); font-family:'Poppins',sans-serif; }
        button,input,select { font:inherit; }
        a { color:var(--gold); text-underline-offset:3px; }
        .register-page { display:grid; grid-template-columns:40% 60%; width:100%; min-height:100vh; min-height:100dvh; }
        .visual-panel { position:relative; min-width:0; overflow:hidden; background:#090705 url('/images/Login.jpg') 44% 43% / cover no-repeat; }
        .visual-panel::after { position:absolute; inset:0; content:''; background:linear-gradient(to bottom,rgba(0,0,0,.04) 40%,rgba(0,0,0,.72)); }
        .academy-name { position:absolute; z-index:1; bottom:clamp(40px,7vh,70px); left:clamp(22px,3vw,46px); margin:0; width:clamp(90px,10vw,140px); height:auto; object-fit:contain; }
        .form-panel { position:relative; z-index:2; display:flex; align-items:center; justify-content:center; min-width:0; padding:clamp(18px,3vh,30px) clamp(42px,7vw,110px); background:var(--panel); }
        .form-shell { width:min(100%,600px); }
        header { margin-bottom:clamp(14px,2.2vh,22px); text-align:center; }
        h1 { margin:0; color:var(--gold); font-size:clamp(29px,2.5vw,38px); font-weight:800; line-height:1; }
        header p { margin:clamp(9px,1.3vh,13px) 0 0; font-size:clamp(14px,1.3vw,19px); font-weight:300; }
        form { display:grid; gap:22px; }
        label { display:block; margin-bottom:9px; font-size:clamp(15px,1.35vw,20px); font-weight:300; line-height:1.4; }
        .hint { margin-left:6px; font-size:10px; opacity:.72; }
        input,select { width:100%; height:clamp(44px,5.2vh,52px); border:1px solid transparent; border-radius:9px; outline:none; background:var(--field); color:#fff; padding:0 16px; font-size:clamp(14px,1.15vw,17px); font-weight:300; }
        input::placeholder { color:#d2d2d2; }
        input:focus,select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(242,200,63,.12); }
        select { appearance:none; color:#d7d7d7; background-image:linear-gradient(45deg,transparent 50%,#eee 50%),linear-gradient(135deg,#eee 50%,transparent 50%); background-position:calc(100% - 18px) 50%,calc(100% - 13px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
        .password-wrap { position:relative; }
        .password-wrap input { padding-right:50px; }
        .password-toggle { position:absolute; top:50%; right:0; display:grid; width:46px; height:25px; transform:translateY(-50%); place-items:center; border:0; border-left:1px solid rgba(255,255,255,.75); background:none; color:#ddd; cursor:pointer; }
        .password-toggle svg { width:18px; height:18px; }
        .password-toggle[aria-pressed="true"] { color:var(--gold); }
        .address-row { display:grid; grid-template-columns:1.65fr 1fr; gap:13px; }
        .phone-row { display:grid; grid-template-columns:64px 1fr; gap:12px; }
        .prefix { display:grid; height:clamp(44px,5.2vh,52px); place-items:center; border-radius:9px; background:var(--field); font-size:16px; }
        .terms { display:grid; grid-template-columns:25px 1fr; align-items:center; gap:13px; margin-top:4px; }
        .terms input { width:23px; height:23px; margin:0; accent-color:var(--gold); }
        .terms label { margin:0; font-size:clamp(12px,1.05vw,16px); }
        .error { border:1px solid rgba(255,123,123,.25); border-radius:7px; background:#3c1b1b; color:#ffd7d7; padding:7px 10px; font-size:11px; }
        .submit { width:100%; height:clamp(45px,5.3vh,53px); margin-top:clamp(18px,2.7vh,27px); border:0; border-radius:9px; background:linear-gradient(100deg,#f4ce47,#c6a32f 56%,#8c7119); color:#111; font-size:clamp(20px,1.9vw,27px); font-weight:600; cursor:pointer; }
        .login-link { margin:clamp(11px,1.7vh,17px) 0 0; text-align:center; font-size:clamp(15px,1.45vw,20px); font-weight:300; }

        @media (min-width:521px) {
            body { overflow:hidden; }
            .register-page { height:100vh; height:100dvh; min-height:0; overflow:hidden; }
            .form-panel { min-height:0; overflow-y:auto; align-items:flex-start; }
            .form-shell { margin-block:auto; }
        }

        @media (min-width:521px) and (max-width:900px) {
            .form-panel { padding-right:24px; padding-left:24px; }
            .academy-name { font-size:16px; }
        }

        @media (max-width:520px) {
            .register-page { display:block; position:relative; }
            .visual-panel { position:absolute; inset:0; background-position:48% 35%; }
            .visual-panel::after { background:rgba(0,0,0,.72); }
            .academy-name { display:none; }
            .form-panel { width:100%; min-height:100dvh; padding:max(24px,env(safe-area-inset-top)) 16px max(24px,env(safe-area-inset-bottom)); background:rgba(17,17,17,.7); }
            .form-shell { width:min(100%,420px); border:1px solid rgba(255,255,255,.08); border-radius:16px; background:rgba(18,18,18,.88); padding:28px 20px; backdrop-filter:blur(5px); }
            h1 { font-size:clamp(28px,8vw,36px); }
            header { margin-bottom:28px; }
            header p { margin-top:12px; font-size:13px; }
            form { gap:22px; }
            label { font-size:14px; }
            input,select,.prefix { height:48px; font-size:16px; min-width:0; }
            .address-row { grid-template-columns:1fr; gap:14px; }
            .password-toggle { height:44px; }
            .terms { margin-top:8px; gap:12px; }
            .terms label { font-size:12px; line-height:1.8; }
            .submit { height:48px; margin-top:8px; font-size:18px; }
            .login-link { margin-top:24px; font-size:13px; line-height:1.8; }
        }

        @media (max-height:720px) {
            .form-panel { padding-top:28px; padding-bottom:28px; }
            .form-shell { max-width:600px; }
            header { margin-bottom:28px; }
            h1 { font-size:32px; }
            header p { margin-top:12px; font-size:13px; }
            input,select,.prefix { height:48px; font-size:16px; }
            .submit { height:48px; margin-top:8px; font-size:18px; }
            .login-link { margin-top:24px; font-size:13px; }
        }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <main class="register-page">
        <section class="visual-panel" aria-label="Orange Dance Academy">
            <img class="academy-name" src="{{ asset('images/logoorange.png') }}" alt="Orange Dance Academy" width="120" height="141">
        </section>
        <section class="form-panel" aria-labelledby="register-title">
            <div class="form-shell">
                <header>
                    <h1 id="register-title">Register</h1>
                    <p>Create An Account</p>
                </header>
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="error" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div>
                        <label for="name">Full Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" placeholder="Your full name" autocomplete="name" required>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Example@gmail.com" autocomplete="email" required>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" placeholder="********" autocomplete="new-password" required>
                            <button class="password-toggle" type="button" data-target="password" aria-label="Tampilkan password" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2.5 12s3.5-5.5 9.5-5.5 9.5 5.5 9.5 5.5-3.5 5.5-9.5 5.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg></button>
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm Password <span class="hint">Repeat again</span></label>
                        <div class="password-wrap">
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="********" autocomplete="new-password" required>
                            <button class="password-toggle" type="button" data-target="password_confirmation" aria-label="Tampilkan konfirmasi password" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2.5 12s3.5-5.5 9.5-5.5 9.5 5.5 9.5 5.5-3.5 5.5-9.5 5.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg></button>
                        </div>
                    </div>
                    <div>
                        <label for="address">Address</label>
                        <div class="address-row">
                            <input id="address" name="address" value="{{ old('address') }}" placeholder="Street address" autocomplete="street-address" required>
                            <select name="state" aria-label="Province" required>
                                <option value="" disabled @selected(!old('state'))>Province</option>
                                @foreach (['Bali','DKI Jakarta','Jawa Barat','Jawa Tengah','Jawa Timur','Kalimantan Timur','Sulawesi Selatan','Sulawesi Utara'] as $state)
                                    <option value="{{ $state }}" @selected(old('state') === $state)>{{ $state }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="phone">Phone Number</label>
                        <div class="phone-row"><span class="prefix">+62</span><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" autocomplete="tel-national" required></div>
                    </div>
                    <div class="terms">
                        <input id="terms" type="checkbox" name="terms" value="1" required>
                        <label for="terms">Agreeing to all the <a href="#">rules</a> in Orange Academy</label>
                    </div>
                    <button class="submit" type="submit">Create an Account</button>
                </form>
                <p class="login-link">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('.password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });
    </script>
</body>
</html>
