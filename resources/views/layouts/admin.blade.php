<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — GreenPOS Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('gp-theme');
                if (t !== 'dark' && t !== 'light') t = 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
<body class="pa-body">
@php
    $navSections = [
        [
            'label' => 'Plateforme',
            'tone' => 'emerald',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard*', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z'],
                ['route' => 'admin.registrations.index', 'label' => 'Demandes d’inscription', 'match' => 'admin.registrations.*', 'badge' => 'pending_registrations', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['route' => 'admin.companies.index', 'label' => 'Entreprises', 'match' => 'admin.companies.*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1'],
                ['route' => 'admin.stores.index', 'label' => 'Boutiques', 'match' => 'admin.stores.*', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ],
        ],
        [
            'label' => 'Commercial',
            'tone' => 'amber',
            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'items' => [
                ['route' => 'admin.plans.index', 'label' => 'Plans', 'match' => 'admin.plans.*', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                ['route' => 'admin.modules.index', 'label' => 'Modules', 'match' => 'admin.modules.*', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['route' => 'admin.subscriptions.index', 'label' => 'Abonnements', 'match' => 'admin.subscriptions.*', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ['route' => 'admin.payments.index', 'label' => 'Paiements', 'match' => 'admin.payments.*', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ],
        ],
        [
            'label' => 'Administration',
            'tone' => 'sky',
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'items' => [
                ['route' => 'admin.users.index', 'label' => 'Utilisateurs plateforme', 'match' => 'admin.users.*', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['route' => 'admin.journal.index', 'label' => 'Journal', 'match' => 'admin.journal.*', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'admin.settings.edit', 'label' => 'Paramètres plateforme', 'match' => 'admin.settings.*', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
            ],
        ],
    ];
@endphp
<div class="pa-shell">
    <aside class="pa-sidebar hidden lg:flex">
        <div class="pa-brand">
            <span class="pa-brand-mark">GP</span>
            <div>
                <p class="pa-brand-title">GreenPOS</p>
                <p class="pa-brand-sub">Console Super Admin</p>
            </div>
        </div>
        <nav class="pa-nav">
            @foreach($navSections as $section)
                <div class="pa-nav-section pa-nav-section--{{ $section['tone'] }}">
                    <div class="pa-nav-section-head">
                        <span class="pa-nav-section-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/></svg>
                        </span>
                        <span class="pa-nav-section-label">{{ $section['label'] }}</span>
                    </div>
                    <div class="pa-nav-section-items">
                        @foreach($section['items'] as $item)
                            <a href="{{ route($item['route']) }}" class="pa-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                                <span class="pa-link-main">
                                    <span class="pa-link-icon" aria-hidden="true">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                                    </span>
                                    <span class="pa-link-text">{{ $item['label'] }}</span>
                                </span>
                                @if(($item['badge'] ?? null) === 'pending_registrations' && ($platformPendingRegistrations ?? 0) > 0)
                                    <span class="pa-nav-badge">{{ $platformPendingRegistrations }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <div class="pa-sidebar-foot">
            @php
                $adminUser = $platformAdminUser ?? auth()->user();
                $adminName = $adminUser?->displayName() ?? 'Admin';
                $adminEmail = $adminUser?->email ?? '';
                $adminInitials = method_exists($adminUser, 'initials')
                    ? $adminUser->initials()
                    : strtoupper(substr($adminName, 0, 1));
            @endphp
            <div class="pa-profile-card">
                <div class="pa-profile-card-glow" aria-hidden="true"></div>
                <div class="pa-sidebar-user">
                    <span class="pa-sidebar-avatar" aria-hidden="true">{{ $adminInitials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="pa-user-role">Super Admin</p>
                        <p class="pa-user-name">{{ $adminName }}</p>
                        <p class="pa-user-email">{{ $adminEmail }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="pa-logout-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="pa-main">
        <header class="pa-top">
            <div>
                <p class="pa-crumb">@yield('breadcrumb', 'Platform')</p>
                <h1 class="pa-heading">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="gp-theme-toggle" data-theme-toggle class="pa-theme-btn" aria-label="Changer le thème" title="Thème clair / sombre">
                    <svg data-theme-icon="light" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg data-theme-icon="dark" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                @yield('actions')
            </div>
        </header>
        <main class="pa-content">
            @if(session('success'))
                <div class="gp-flash gp-flash-success mb-4" data-gp-flash="success" role="status">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300">{{ session('error') }}</div>
            @endif
            @if(session('generated_password'))
                <div class="mb-4 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                    Compte admin : <span class="pa-mono">{{ session('admin_email') }}</span>
                    — mot de passe temporaire : <span class="pa-mono font-bold">{{ session('generated_password') }}</span>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
