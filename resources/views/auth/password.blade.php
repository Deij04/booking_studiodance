@extends('layout.landing')
@section('content')
<style>
.password-page{max-width:560px;min-height:65vh;padding-top:54px;padding-bottom:64px}.password-card{padding:32px;border:1px solid #f0c63a33;border-radius:24px;background:linear-gradient(145deg,#282316,#191919 65%);box-shadow:0 20px 50px #0004}.password-card h1{font-size:clamp(28px,5vw,36px);line-height:1.3;letter-spacing:-.5px}.password-card p{color:var(--muted);font-size:14px;line-height:1.8}.password-field{margin-top:20px}.password-field label{display:block;margin-bottom:8px;font-size:13px}.password-field input{width:100%;min-height:48px;padding:12px 14px;border:1px solid #ffffff30;border-radius:10px;background:#111;color:var(--text);font:inherit;font-size:16px}.password-field input:focus{outline:2px solid var(--gold);outline-offset:2px}.password-card button{width:100%;margin-top:26px;min-height:48px;cursor:pointer;font:600 14px Poppins,sans-serif}.password-back{display:block;margin-top:24px;text-align:center;color:var(--gold);font-size:13px}.password-alert{margin-top:20px;padding:14px;border-radius:10px;background:#382121;color:#ffd0d0;font-size:13px}.password-alert.success{background:#213127;color:#b7e5c3}@media(max-width:480px){.password-card{padding:24px}.password-page{padding-top:32px}}
</style>
<main class="section password-page">
    <section class="password-card" aria-labelledby="password-title">
        <span class="eyebrow">ACCOUNT RECOVERY</span>
        <h1 id="password-title">{{ $reset ? 'Reset' : 'Lupa' }} <span class="gold">password?</span></h1>
        <p>{{ $reset ? 'Buat password baru minimal 8 karakter untuk mengamankan akunmu.' : 'Masukkan email akunmu. Kami akan mengirimkan tautan untuk membuat password baru.' }}</p>
        @if(session('status'))<div class="password-alert success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="password-alert" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route($reset ? 'password.update' : 'password.email') }}">
            @csrf
            @if($reset)<input type="hidden" name="token" value="{{ $token }}">@endif
            <div class="password-field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" maxlength="255" required autofocus></div>
            @if($reset)
                <div class="password-field"><label for="password">Password baru</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
                <div class="password-field"><label for="password_confirmation">Konfirmasi password baru</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
            @endif
            <button class="btn btn-fill" type="submit">{{ $reset ? 'Simpan password baru' : 'Kirim tautan reset' }}</button>
        </form>
        <a class="password-back" href="{{ route('login') }}">&larr; Kembali ke login</a>
    </section>
</main>
@endsection
