@extends('layout.landing')
@section('content')
<main class="section" style="max-width:900px;margin:0 auto;min-height:60vh">
    <h1 class="section-title">My <span class="gold">Profile</span></h1>
    <p class="sub">Your Orange Dance Academy account details.</p>
    <div class="faq-item">
        <dl style="display:grid;gap:20px;overflow-wrap:anywhere">
            @foreach(['name' => 'Full Name', 'email' => 'Email', 'phone' => 'Phone Number', 'address' => 'Address', 'state' => 'Province'] as $field => $label)
                <div><dt style="color:var(--muted);font-size:13px">{{ $label }}</dt><dd style="margin:6px 0 0">{{ $user->$field ?: 'Not provided' }}</dd></div>
            @endforeach
        </dl>
    </div>
</main>
@endsection
