@extends('layout.landing')
@push('styles')
<style>
    .membership-page{background:radial-gradient(ellipse 600px 380px at 50% 170px,rgba(190,151,24,.24),transparent 100%),var(--bg);color:var(--text);padding:64px 24px 56px;min-height:70vh}
    .membership-shell{max-width:1060px;margin:0 auto}
    .membership-heading{text-align:center;max-width:940px;margin:0 auto 56px}
    .membership-brand{display:inline-block;padding:8px 16px;border:1px solid rgba(240,198,58,.3);border-radius:999px;background:rgba(240,198,58,.04);color:var(--gold);font-size:14px;margin-bottom:24px}
    .membership-heading h1{font-size:clamp(38px,6vw,68px);line-height:1.15;letter-spacing:-.02em;margin:0;font-weight:800}
    .membership-heading h1 span{color:var(--gold)}
    .membership-heading p{font-family:Poppins,sans-serif;color:var(--muted);font-size:17px;line-height:1.8;margin:28px 0 0}
    .membership-plan-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;align-items:stretch}
    .membership-plan{display:flex;flex-direction:column;min-width:0;padding:30px 24px;border:1px solid rgba(240,198,58,.22);border-radius:28px;background:var(--panel);box-shadow:0 12px 35px rgba(0,0,0,.2)}
    .membership-plan-name{align-self:flex-start;max-width:100%;overflow-wrap:anywhere;margin:0 0 28px;padding:8px 14px;border-radius:999px;background:rgba(240,198,58,.1);color:var(--gold);border:1px solid rgba(240,198,58,.3);font:600 11px Poppins,sans-serif;letter-spacing:.1em;text-transform:uppercase}
    .membership-plan-price{display:flex;align-items:baseline;flex-wrap:wrap;gap:10px;font-family:Poppins,sans-serif;margin:0 0 12px}
    .membership-plan-price span{font-size:13px;color:var(--muted)}
    .membership-plan-price strong{font-size:clamp(30px,3.4vw,44px);font-weight:600;color:var(--gold);letter-spacing:-.03em;overflow-wrap:anywhere}
    .membership-duration{font:14px Poppins,sans-serif;color:var(--muted);margin:0}
    .membership-features{list-style:none;padding:24px 0;margin:26px 0 20px;border-top:1px solid rgba(240,198,58,.2);flex:1}
    .membership-features li{position:relative;padding-left:18px;margin-bottom:18px;color:var(--muted);font:14px/1.8 Poppins,sans-serif;overflow-wrap:anywhere}
    .membership-features li::before{content:'✦';position:absolute;left:0;color:var(--gold);font-size:11px}
    .membership-plan form{margin-top:auto}
    .membership-plan label{display:block;color:var(--muted);font-size:12px;margin-bottom:8px}
    .membership-plan input[type=date]{display:block;width:100%;min-height:44px;margin:0 0 18px;padding:10px 12px;border:1px solid rgba(240,198,58,.25);border-radius:10px;background:#111;color:var(--text);font:14px Poppins,sans-serif;color-scheme:dark}
    .membership-book{display:block;width:100%;min-height:44px;border:0;border-radius:999px;background:linear-gradient(100deg,#f4ce47,#c6a32f 56%,#8c7119);color:#171717;font:700 12px Poppins,sans-serif;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;padding:12px 16px}
    .membership-book:hover{background:var(--gold)}
    .membership-notice{padding:18px 22px;border:1px solid rgba(240,198,58,.25);border-radius:16px;background:rgba(240,198,58,.06);margin-bottom:24px;color:var(--text);line-height:1.7}
    .membership-notice-error{border-color:rgba(255,123,123,.3);color:#ffd7d7;background:#3c1b1b}
    .membership-history{margin-top:48px;padding-top:32px;border-top:1px solid rgba(240,198,58,.2)}
    .membership-history h2{font-size:26px;margin:0 0 14px}
    .membership-history-intro{color:var(--muted);font-size:13px;line-height:1.8;margin-bottom:24px}
    .membership-history-card{padding:20px 24px;margin-bottom:16px;border:1px solid rgba(240,198,58,.16);border-radius:18px;background:var(--panel)}
    .membership-history-card h3{margin:0 0 12px;font-size:18px}
    .membership-history-card p{color:var(--muted);font-size:13px;line-height:1.7;margin:8px 0}
    .membership-pagination{display:flex;justify-content:space-between;gap:16px;margin-top:20px}
    .membership-pagination .btn{color:#947126;border-color:var(--gold);font-size:13px}
    .membership-page button:focus-visible,.membership-page input:focus-visible,.membership-page a:focus-visible{outline:2px solid var(--gold);outline-offset:4px}
    .academy-footer{margin-top:0}
    @media(max-width:900px){.membership-plan-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.membership-page{padding:40px 18px}.membership-heading{margin-bottom:32px}.membership-plan-grid{grid-template-columns:1fr;gap:20px}.membership-plan{padding:28px 24px}.membership-plan-price strong{font-size:40px}.membership-features li{font-size:16px}.membership-book{min-height:48px;font-size:12px}.membership-plan input[type=date]{min-height:48px;font-size:16px}}
</style>
@endpush
@section('content')
<main class="membership-page">
<div class="membership-shell">
    <header class="membership-heading">
        <span class="membership-brand">Orange Dance Academy</span>
        <h1>Our <span>Membership</span></h1>
        <p>Choose your next step in dance. Explore our membership plans and find the right fit for your journey.</p>
    </header>
    @if(session('success'))<p role="status" class="membership-notice">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="membership-notice membership-notice-error"><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div>@endif
    <div class="membership-plan-grid">
        @forelse($plans as $plan)
            <article class="membership-plan">
                <h2 class="membership-plan-name">{{ $plan->name }}</h2>
                <p class="membership-plan-price"><span>IDR</span><strong>{{ number_format($plan->price, 0, '.', ',') }}</strong></p>
                <p class="membership-duration">{{ $plan->duration_days }} days of membership</p>
                <ul class="membership-features">
                    @if($plan->description)
                        @foreach(preg_split('/\r\n|\r|\n/', __($plan->description)) as $benefit)
                            @if(trim($benefit) !== '')<li>{{ $benefit }}</li>@endif
                        @endforeach
                    @else
                        <li>Contact our team for plan benefits.</li>
                    @endif
                    <li>Valid for {{ $plan->duration_days }} days from admin approval.</li>
                </ul>
                <form method="POST" action="{{ route('memberships.store') }}">
                    @csrf
                    <input type="hidden" name="membership_plan_id" value="{{ $plan->id }}">
                    <button class="membership-book" type="submit">Book Membership</button>
                </form>
            </article>
        @empty
            <p>No membership plans are currently available.</p>
        @endforelse
    </div>
    <section class="membership-history" aria-label="Membership application history">
    <p class="membership-history-intro">Choose a plan, submit your application, and contact our team for payment. Your membership starts on the day our team verifies your payment and approves your application.</p>
    <h2>Application History</h2>
    <p class="membership-history-intro">Start and end dates are assigned upon approval. The end date is included in your membership period.</p>
    @forelse($memberships as $membership)
        <article class="membership-history-card">
            <h3>{{ $membership->membershipPlan->name }}</h3>
            <p class="small">@if($membership->start_date && $membership->end_date){{ $membership->start_date->format('d/m/Y') }} &ndash; {{ $membership->end_date->format('d/m/Y') }}@else Dates assigned upon approval @endif &middot; Rp {{ number_format($membership->price, 2, ',', '.') }}</p>
            <p class="small"><strong>{{ $membership->effective_status === 'cancelled' ? 'Rejected / Cancelled' : (['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'][$membership->effective_status] ?? ucfirst($membership->effective_status)) }}</strong> &middot; {{ $membership->payment_status === 'paid' ? 'Paid' : 'Unpaid' }}</p>
        </article>
    @empty
        <p>You have no membership applications yet.</p>
    @endforelse
    <nav aria-label="Membership history pagination" class="membership-pagination">
        @if($memberships->previousPageUrl())<a class="btn" href="{{ $memberships->previousPageUrl() }}">Previous</a>@endif
        @if($memberships->nextPageUrl())<a class="btn" href="{{ $memberships->nextPageUrl() }}">Next</a>@endif
    </nav>
    </section>
</div>
</main>
@endsection
