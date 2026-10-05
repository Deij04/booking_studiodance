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
        <a href="{{ route('landing') }}#class" data-home-section="class">Class</a>
        <a href="{{ auth()->check() && auth()->user()->can('access-admin') ? route('admin.memberships.index') : route('memberships.index') }}" @if(request()->routeIs('memberships.*', 'admin.memberships.*')) aria-current="page" @endif>Membership</a>
        @guest
            <a class="btn btn-login" href="{{ route('login') }}">Login</a>
        @else
            @can('access-admin')
                <a class="btn" href="{{ route('admin.dashboard') }}">Dashboard</a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit">Logout</button>
            </form>
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
        const isHomePage = @json(request()->routeIs('landing'));
        const syncActiveSection = () => {
            if (!isHomePage) return;
            const section = location.hash === '#class' ? 'class' : 'home';
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
