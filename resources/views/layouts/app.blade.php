<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#070d1e">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name').' · Your football, in focus')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <aside class="sidebar" id="site-sidebar" aria-label="Main navigation">
        <a class="brand" href="{{ route('home') }}" aria-label="Touchline home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none">
                    <rect x="3" y="3" width="26" height="26" rx="9" stroke="currentColor" stroke-width="2"></rect>
                    <path d="M8 17h4l3-7 4 12 3-7h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
            </span>
            <span class="brand-name">{{ config('app.name', 'Touchline') }}<small>THE MATCHDAY CLUB</small></span>
        </a>

        <div class="sidebar-label">YOUR GAME</div>
        <nav class="sidebar-nav">
            <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}" data-matchday-link>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.7"></rect><path d="M12 7v5l3 2" stroke="currentColor" stroke-linecap="round" stroke-width="1.7"></path></svg>
                <span>Matchday</span><span class="nav-arrow">↗</span>
            </a>
            <a class="nav-link {{ request()->routeIs('fixtures.index') || request()->routeIs('matches.index') ? 'is-active' : '' }}" href="{{ route('fixtures.index') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5v4m10-4v4M4.5 9h15m-14-4h13A1.5 1.5 0 0 1 20 6.5v12A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5v-12A1.5 1.5 0 0 1 5.5 5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path><path d="M8 13h3m2 0h3m-8 3h3" stroke="currentColor" stroke-linecap="round" stroke-width="1.7"></path></svg>
                <span>All fixtures</span>
            </a>
            <a class="nav-link {{ request()->routeIs('results.index') ? 'is-active' : '' }}" href="{{ route('results.index') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 19.5h14m-12-3V12m5 4.5v-9m5 9V9" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"></path></svg>
                <span>Results</span>
            </a>
            <a class="nav-link" href="{{ route('home') }}#saved" data-saved-link>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7.5-4.4-7.5-10A4.2 4.2 0 0 1 12 7.7a4.2 4.2 0 0 1 7.5 2.3c0 5.6-7.5 10-7.5 10Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path></svg>
                <span>My watchlist</span><span class="watchlist-count" data-watchlist-count>0</span>
            </a>
        </nav>

        <div class="sidebar-label sidebar-label-spaced">YOUR MATCHDAY</div>
        <section class="sidebar-callout">
            <span class="callout-ball" aria-hidden="true">◌</span>
            <span class="callout-title">All the build-up.</span>
            <span class="callout-copy">Find kick-off times, match details and trusted ways to watch.</span>
            <a href="{{ route('fixtures.index') }}">Explore fixtures <span aria-hidden="true">→</span></a>
        </section>

        <div class="sidebar-footer">
            <span class="status-pip"></span>
            <span><strong>Here for the game.</strong><small>Official streams only.</small></span>
        </div>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="site-sidebar" aria-expanded="false" data-menu-toggle>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"></path></svg>
            </button>
            <form class="global-search" action="{{ route('matches.index') }}" method="GET" role="search">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"></circle><path d="m16 16 4.1 4.1" stroke="currentColor" stroke-linecap="round" stroke-width="1.7"></path></svg>
                <label class="sr-only" for="site-search">Search teams and fixtures</label>
                <input id="site-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search teams or fixtures…" autocomplete="off">
                <kbd>/</kbd>
            </form>
            <div class="topbar-meta"><span class="topbar-date">{{ now()->translatedFormat('l, j F') }}</span><span class="topbar-timezone">{{ config('app.timezone') }}</span></div>
            <a class="topbar-avatar" href="{{ route('fixtures.index') }}" aria-label="Browse match fixtures">T</a>
        </header>

        @if (session('status'))
            <div class="flash-message" role="status">{{ session('status') }}</div>
        @endif

        <main class="page-content" id="main-content">
            @yield('content')
        </main>

        <footer class="site-footer">
            <span>Made for people who love the game.</span>
            <span>Only watch matches you have permission to view.</span>
        </footer>
    </div>

    <div class="sidebar-backdrop" data-menu-close></div>
</body>
</html>
