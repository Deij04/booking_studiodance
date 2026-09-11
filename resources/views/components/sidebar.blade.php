<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/70 lg:hidden"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[260px] -translate-x-full flex-col border-r border-white/10 bg-[#151515] px-5 py-6 transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2">
        <span class="grid size-11 place-items-center rounded-xl bg-gradient-to-br from-[#f5d154] to-[#9b7d18] text-lg font-extrabold text-black">O</span>
        <span>
            <span class="block text-sm font-bold tracking-wide text-[#f2c83f]">ORANGE</span>
            <span class="block text-[10px] tracking-[.18em] text-white/55">DANCE ACADEMY</span>
        </span>
    </a>

    <nav class="mt-10 space-y-2" aria-label="Navigasi utama">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-[#f2c83f] font-semibold text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>
            Dashboard
        </a>
        @can('access-admin')
            <a href="{{ route('admin.studios.index') }}" @if(request()->routeIs('admin.studios.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.studios.*') ? 'bg-[#f2c83f] font-semibold text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 21h18M5 21V5l7-3 7 3v16M9 21v-5h6v5M8 8h2M14 8h2M8 12h2M14 12h2"/></svg>
                Kelola Studio
            </a>
        @endcan
        <a href="{{ route('admin.dance-classes.index') }}" @if(request()->routeIs('admin.dance-classes.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.dance-classes.*') ? 'bg-[#f2c83f] font-semibold text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
            Dance Classes
        </a>
        <a href="{{ route('admin.bookings.index') }}" @if(request()->routeIs('admin.bookings.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.bookings.*') ? 'bg-[#f2c83f] font-semibold text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 2v4M16 2v4M4 9h16"/></svg>
            Kelola Booking
        </a>
        <a href="{{ route('admin.dashboard') }}#membership" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-white/60 transition hover:bg-white/5 hover:text-white">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12v8H4v-8M2 7h20v5H2zM12 7v13M12 7H7.5a2.5 2.5 0 1 1 2.2-3.7L12 7Zm0 0h4.5a2.5 2.5 0 1 0-2.2-3.7L12 7Z"/></svg>
            Membership
        </a>
        @can('viewAny', \App\Models\User::class)
            <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-[#f2c83f] font-semibold text-black' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/></svg>
                Kelola User
            </a>
        @endcan
    </nav>

    <div class="mt-auto rounded-2xl border border-[#f2c83f]/20 bg-[#f2c83f]/5 p-4">
        <p class="text-xs font-semibold text-[#f2c83f]">Need some help?</p>
        <p class="mt-1 text-[11px] leading-5 text-white/45">Hubungi tim Orange Academy untuk bantuan booking.</p>
        <button type="button" class="mt-3 w-full rounded-lg border border-[#f2c83f]/30 py-2 text-xs font-medium text-[#f2c83f] transition hover:bg-[#f2c83f] hover:text-black">Contact Us</button>
    </div>
</aside>
