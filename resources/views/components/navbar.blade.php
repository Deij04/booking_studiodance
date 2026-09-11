<header class="nav">
    <div class="brand">
        <div class="brand-badge"></div>
        <div>ORANGE DANCE ACADEMY</div>
    </div>
    <nav class="nav-links">
        <a href="{{ route('landing') }}#home">Home</a>
        <a href="{{ route('landing') }}#studio">Studio</a>
        <a href="{{ route('landing') }}#class">Class</a>
        <a href="{{ route('landing') }}#about">About</a>
        @guest
            <a class="btn" href="{{ route('login') }}">Login</a>
        @else
            @can('access-admin')
                <a class="btn" href="{{ route('admin.dashboard') }}">Dashboard</a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit" style="background:transparent;color:inherit;font:inherit;cursor:pointer">Logout</button>
            </form>
        @endguest
    </nav>
</header>
