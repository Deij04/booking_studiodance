@extends('layout.landing')
@section('content')
@php($editing = request()->boolean('edit') || $errors->any())
<style>
.profile-page{max-width:1100px;min-height:65vh;padding-top:48px;padding-bottom:64px}.profile-grid{display:grid;grid-template-columns:280px minmax(0,1fr);gap:24px;align-items:start}.profile-card{padding:28px;border:1px solid #f0c63a30;border-radius:24px;background:linear-gradient(145deg,#282316,#191919 65%);box-shadow:0 16px 40px #0003}.profile-summary{text-align:center;overflow-wrap:anywhere}.profile-avatar{width:88px;height:88px;margin:0 auto 20px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#f4cf52,#9d7e13);color:#171717;font-size:32px;font-weight:700}.profile-summary h2{font-size:21px;margin:0 0 8px}.profile-summary p,.profile-description{color:var(--muted);font-size:13px;line-height:1.8}.profile-badge{display:inline-block;margin:14px 0 24px;padding:6px 14px;border-radius:99px;background:#f0c63a14;color:var(--gold);font-size:12px}.profile-history{display:block;padding-top:20px;border-top:1px solid #ffffff12;color:var(--gold);font-size:13px}.profile-card h2{margin-top:0}.profile-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:24px}.profile-field{display:flex;flex-direction:column;gap:8px}.profile-field.wide{grid-column:1/-1}.profile-field label{font-size:13px}.profile-field input,.profile-field textarea{width:100%;min-height:48px;padding:12px 14px;border:1px solid #ffffff25;border-radius:10px;background:#111;color:var(--text);font:inherit;font-size:14px}.profile-field textarea{resize:vertical;min-height:100px}.profile-field input:focus,.profile-field textarea:focus{outline:2px solid var(--gold);outline-offset:2px}.profile-error{color:#ffb3b3;font-size:12px}.profile-notice{padding:14px 18px;margin-bottom:24px;border-radius:12px;background:#213127;color:#b7e5c3;font-size:14px}.profile-actions{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:26px;padding-top:22px;border-top:1px solid #ffffff12}.profile-actions span{color:var(--muted);font-size:12px}.profile-actions button{cursor:pointer;font:600 14px Poppins,sans-serif;min-height:48px}@media(max-width:760px){.profile-grid,.profile-fields{grid-template-columns:1fr}.profile-card{padding:24px}.profile-actions{flex-wrap:wrap}.profile-actions button{width:100%}}
</style>
<style>
.profile-membership{padding-top:20px;margin-bottom:24px;border-top:1px solid #ffffff12;font-size:13px;line-height:1.8}.profile-membership strong{color:var(--gold);font-weight:600}
.profile-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;margin:24px 0 0;overflow-wrap:anywhere}.profile-details dt{color:var(--muted);font-size:13px;margin-bottom:8px}.profile-details dd{margin:0;font-size:14px}.profile-details .wide{grid-column:1/-1}.profile-actions a{font-size:14px;min-height:48px}.profile-cancel{color:var(--muted)}@media(max-width:760px){.profile-details{grid-template-columns:1fr}.profile-actions a.btn{width:100%}}
</style>
<main class="section profile-page">
    <h1 class="section-title">My <span class="gold">Profile</span></h1>
    <p class="sub">Kelola informasi pribadimu dan bersiap untuk sesi dance berikutnya.</p>
    @if(session('status'))<div class="profile-notice" role="status">{{ session('status') }}</div>@endif
    <div class="profile-grid">
        <aside class="profile-card profile-summary" aria-label="Ringkasan profil">
            <div class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
            <h2>{{ $user->name }}</h2><p>{{ $user->email }}</p>
            <span class="profile-badge">Orange Dance Academy</span>
            <div class="profile-membership">
                Membership: <strong>{{ $memberships->isNotEmpty() ? $memberships->map(fn ($membership) => $membership->membershipPlan?->name ?? 'Paket membership')->implode(', ') : 'Tidak ada membership aktif' }}</strong>
            </div>
            <a class="profile-history" href="{{ route('user.history') }}">Lihat riwayat booking &rarr;</a>
        </aside>
        <section class="profile-card" aria-labelledby="profile-form-title">
            <h2 id="profile-form-title">{{ $editing ? 'Edit profil' : 'Informasi pribadi' }}</h2><p class="profile-description">Pastikan email dan nomor teleponmu selalu terbaru agar kami dapat menghubungimu.</p>
            @if($editing)
            <form method="POST" action="{{ route('user.profile.update') }}">
                @csrf @method('PATCH')
                <div class="profile-fields">
                    @foreach(['name' => 'Nama lengkap', 'email' => 'Email', 'phone' => 'Nomor telepon', 'state' => 'Provinsi', 'address' => 'Alamat'] as $field => $label)
                        <div class="profile-field {{ $field === 'address' ? 'wide' : '' }}">
                            <label for="profile-{{ $field }}">{{ $label }}{{ in_array($field, ['name', 'email']) ? ' *' : '' }}</label>
                            @if($field === 'address')
                                <textarea id="profile-{{ $field }}" name="{{ $field }}" maxlength="500" autocomplete="street-address" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>{{ old($field, $user->$field) }}</textarea>
                            @else
                                <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" value="{{ old($field, $user->$field) }}" maxlength="{{ $field === 'phone' ? 25 : ($field === 'state' ? 100 : 255) }}" autocomplete="{{ ['name' => 'name', 'email' => 'email', 'phone' => 'tel', 'state' => 'address-level1'][$field] }}" @if(in_array($field, ['name', 'email'])) required @endif @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                            @endif
                            @error($field)<span class="profile-error" id="error-{{ $field }}" role="alert">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="profile-actions"><a class="btn profile-cancel" href="{{ route('user.profile') }}">Batal</a><button class="btn btn-fill" type="submit">Simpan perubahan</button></div>
            </form>
            @else
                <dl class="profile-details">
                    @foreach(['name' => 'Nama lengkap', 'email' => 'Email', 'phone' => 'Nomor telepon', 'state' => 'Provinsi', 'address' => 'Alamat'] as $field => $label)
                        <div class="{{ $field === 'address' ? 'wide' : '' }}"><dt>{{ $label }}</dt><dd>{{ $user->$field ?: 'Belum diisi' }}</dd></div>
                    @endforeach
                </dl>
                <div class="profile-actions"><span>Perbarui data saat diperlukan.</span><a class="btn btn-fill" href="{{ route('user.profile', ['edit' => 1]) }}">Edit profil</a></div>
            @endif
        </section>
    </div>
</main>
@endsection
