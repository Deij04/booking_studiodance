@extends('layout.landing')
@section('content')
<main class="section">
    <h2 class="section-title">My Membership</h2>
    <p class="sub">Choose a plan, submit your application, and contact our team for payment. Your membership becomes active once your payment is verified and your application is approved.</p>
    @if(session('success'))<p role="status" class="info-pill">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="info-pill"><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div>@endif
    <div class="class-grid">
        @forelse($plans as $plan)
            <article class="class-card"><div class="class-body">
                <h3>{{ $plan->name }}</h3>
                <p style="color:var(--gold);font-size:22px;font-weight:700">Rp {{ number_format($plan->price, 2, ',', '.') }}</p>
                <p>{{ $plan->duration_days }} days</p>
                <p style="white-space:pre-line;margin:16px 0;overflow-wrap:anywhere">{{ $plan->description ? __($plan->description) : '' }}</p>
                <form method="POST" action="{{ route('memberships.store') }}">
                    @csrf
                    <input type="hidden" name="membership_plan_id" value="{{ $plan->id }}">
                    <label for="start-date-{{ $plan->id }}" class="small">Start date</label>
                    <input id="start-date-{{ $plan->id }}" name="start_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('membership_plan_id') == $plan->id ? old('start_date', today()->toDateString()) : today()->toDateString() }}" required style="display:block;width:100%;padding:12px;margin:8px 0 16px;border:1px solid #555;border-radius:12px;background:#111;color:var(--text);color-scheme:dark">
                    <button class="btn btn-fill" type="submit" style="cursor:pointer">Apply for Membership</button>
                </form>
            </div></article>
        @empty
            <p>No membership plans are currently available.</p>
        @endforelse
    </div>
    <div class="bar"></div>
    <h2>Application History</h2>
    <p class="small">The end date is included in your membership period. Applications with a future start date can be activated by our team once that date arrives.</p>
    @forelse($memberships as $membership)
        <article class="faq-item">
            <h3>{{ $membership->membershipPlan->name }}</h3>
            <p class="small">{{ $membership->start_date?->format('d/m/Y') ?? '-' }} &ndash; {{ $membership->end_date?->format('d/m/Y') ?? '-' }} &middot; Rp {{ number_format($membership->price, 2, ',', '.') }}</p>
            <p class="small"><strong>{{ $membership->effective_status === 'cancelled' ? 'Rejected / Cancelled' : (['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'][$membership->effective_status] ?? ucfirst($membership->effective_status)) }}</strong> &middot; {{ $membership->payment_status === 'paid' ? 'Paid' : 'Unpaid' }}</p>
        </article>
    @empty
        <p>You have no membership applications yet.</p>
    @endforelse
    <nav aria-label="Membership history pagination" style="display:flex;justify-content:space-between;gap:16px;margin-top:20px">
        @if($memberships->previousPageUrl())<a class="btn" href="{{ $memberships->previousPageUrl() }}">Previous</a>@endif
        @if($memberships->nextPageUrl())<a class="btn" href="{{ $memberships->nextPageUrl() }}">Next</a>@endif
    </nav>
</main>
@endsection
