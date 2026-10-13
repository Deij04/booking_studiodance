<!doctype html>
<html lang="id" class="admin-dashboard">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logoorange.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | Orange Dance Academy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .admin-dashboard,.admin-dashboard *{scrollbar-color:#f2c83f #1b1b17;scrollbar-width:thin}
        .admin-dashboard::-webkit-scrollbar,.admin-dashboard *::-webkit-scrollbar{width:10px;height:10px}
        .admin-dashboard::-webkit-scrollbar-track,.admin-dashboard *::-webkit-scrollbar-track{background:#1b1b17;border-radius:10px}
        .admin-dashboard::-webkit-scrollbar-thumb,.admin-dashboard *::-webkit-scrollbar-thumb{background:#f2c83f;border:2px solid #1b1b17;border-radius:10px}
        .admin-dashboard::-webkit-scrollbar-thumb:hover,.admin-dashboard *::-webkit-scrollbar-thumb:hover{background:#ffda58}
        .admin-dashboard::-webkit-scrollbar-corner,.admin-dashboard *::-webkit-scrollbar-corner{background:#1b1b17}
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-[#0d0d0d] font-sans text-white antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <x-sidebar />

        <main class="min-w-0">
            <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-white/10 bg-[#0d0d0d]/90 px-4 backdrop-blur-xl sm:px-7 lg:px-10">
                <div class="flex items-center gap-3">
                    <button id="menu-button" type="button" class="grid size-10 place-items-center rounded-xl border border-white/10 text-white/70 lg:hidden" aria-controls="sidebar" aria-label="Buka menu" aria-expanded="false">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div>
                        <p class="text-xs text-white/40">Welcome back,</p>
                        <h1 class="text-lg font-semibold sm:text-xl">{{ auth()->user()->name }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" class="relative grid size-10 place-items-center rounded-xl border border-white/10 text-white/65 transition hover:border-[#f2c83f]/40 hover:text-[#f2c83f]" aria-label="Notifikasi">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>
                        <span class="absolute right-2 top-2 size-1.5 rounded-full bg-[#f2c83f]"></span>
                    </button>
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] capitalize text-white/40">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                    </div>
                    <div class="grid size-10 place-items-center rounded-full bg-gradient-to-br from-[#f5d154] to-[#957613] font-bold text-black">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="grid size-10 place-items-center rounded-xl border border-white/10 text-white/55 transition hover:border-red-400/40 hover:text-red-400" aria-label="Logout">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
                        </button>
                    </form>
                </div>
            </header>

            @yield('content')
        </main>
    </div>

    <script>
        const menuButton = document.getElementById('menu-button');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const setMenu = (open) => {
            sidebar.classList.toggle('-translate-x-full', !open);
            backdrop.classList.toggle('hidden', !open);
            menuButton.setAttribute('aria-expanded', String(open));
        };
        menuButton.addEventListener('click', () => setMenu(menuButton.getAttribute('aria-expanded') !== 'true'));
        backdrop.addEventListener('click', () => setMenu(false));
    </script>
    @stack('scripts')
</body>
</html>
