<header class="site-header">
<div class="nav">
    <a class="brand" href="{{ route('landing') }}" aria-label="Orange Dance Academy - Home">
        <img class="brand-logo" src="{{ asset('images/logoorange.png') }}" alt="" width="48" height="56">
        <div>ORANGE DANCE ACADEMY</div>
    </a>
    <button class="nav-toggle" type="button" aria-controls="landing-navigation" aria-expanded="false" aria-label="Open navigation menu">
        <svg class="menu-open-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg class="menu-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>
    </button>
    <nav id="landing-navigation" class="nav-links" aria-label="Main navigation">
        <a href="{{ route('landing') }}#home" data-home-section="home" @if(request()->routeIs('landing')) aria-current="page" @endif>Home</a>
        <a href="{{ route('user.about') }}" @if(request()->routeIs('user.about')) aria-current="page" @endif>About Us</a>
        <a href="{{ route('user.studios.index') }}" @if(request()->routeIs('user.studios.*')) aria-current="page" @endif>Studio</a>
        <a href="{{ route('user.classes.index') }}" @if(request()->routeIs('user.classes.*')) aria-current="page" @endif>Class</a>
        <a href="{{ auth()->check() && auth()->user()->can('access-admin') ? route('admin.memberships.index') : route('memberships.index') }}" @if(request()->routeIs('memberships.*', 'admin.memberships.*')) aria-current="page" @endif>Membership</a>
        @guest
            <a class="btn btn-login" href="{{ route('login') }}">Login</a>
        @else
            <details class="account-dropdown">
                <summary class="account-trigger"><span>{{ auth()->user()->name }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/></svg><svg class="account-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></summary>
                <div class="account-menu">
                    <a href="{{ route('user.profile') }}">Profile</a>
                    <a href="{{ route('user.history') }}">History</a>
                    @can('access-admin')<a href="{{ route('admin.dashboard') }}">Dashboard</a>@endcan
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log Out</button></form>
                </div>
            </details>
        @endguest
    </nav>
</div>
</header>
<script>
    (() => {
        const header = document.querySelector('.site-header');
        const toggle = header.querySelector('.nav-toggle');
        const navigation = header.querySelector('.nav-links');
        const mobile = window.matchMedia('(max-width: 1024px)');
        const syncNavigationHeight = () => header.style.setProperty('--navigation-header-height', `${header.getBoundingClientRect().height}px`);
        syncNavigationHeight();
        new ResizeObserver(syncNavigationHeight).observe(header);
        const account = header.querySelector('.account-dropdown');
        document.addEventListener('click', event => {
            if (account && !account.contains(event.target)) account.open = false;
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && account?.open) {
                account.open = false;
                account.querySelector('summary').focus();
            }
        });
        account?.addEventListener('focusout', event => {
            if (!account.contains(event.relatedTarget)) account.open = false;
        });
        const isHomePage = @json(request()->routeIs('landing'));
        const syncActiveSection = () => {
            if (!isHomePage) return;
            const section = 'home';
            navigation.querySelectorAll('[data-home-section]').forEach((link) => {
                if (link.dataset.homeSection === section) {
                    link.setAttribute('aria-current', section === 'home' ? 'page' : 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };
        syncActiveSection();
        window.addEventListener('hashchange', syncActiveSection);
        const setOpen = (open) => {
            if (!open && account) account.open = false;
            header.classList.toggle('menu-is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        };
        header.classList.add('nav-ready');
        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) setOpen(false);
        });
        document.addEventListener('click', (event) => {
            if (!header.contains(event.target)) setOpen(false);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });
        header.addEventListener('focusout', (event) => {
            if (!header.contains(event.relatedTarget)) setOpen(false);
        });
        mobile.addEventListener('change', () => setOpen(false));
    })();
</script>
