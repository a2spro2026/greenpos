<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ ($workspaceBranding['trade_name'] ?? null) ?: 'GreenPOS' }}</title>
    @if(!empty($workspaceBrandingFavicon))
        <link rel="icon" href="{{ $workspaceBrandingFavicon }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var brandingTheme = @json($workspaceBranding['theme'] ?? null);
                var brandingDensity = @json($workspaceBranding['density'] ?? null);
                var userChose = localStorage.getItem('gp-theme-user');
                var t = localStorage.getItem('gp-theme');
                if (!userChose && brandingTheme && (brandingTheme === 'light' || brandingTheme === 'dark')) {
                    t = brandingTheme;
                }
                if (t !== 'dark' && t !== 'light') t = 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
                document.documentElement.dataset.theme = t;
                var sidebarStyle = @json($workspaceSidebarStyle ?? 'auto');
                if (sidebarStyle !== 'light' && sidebarStyle !== 'dark') sidebarStyle = 'auto';
                document.documentElement.dataset.sidebarStyle = sidebarStyle;
                var sidebarDark = sidebarStyle === 'dark' || (sidebarStyle !== 'light' && t === 'dark');
                document.documentElement.classList.toggle('gp-sidebar-dark', sidebarDark);
                document.documentElement.classList.toggle('gp-sidebar-light', !sidebarDark);
                var d = localStorage.getItem('gp-density') || brandingDensity || 'comfortable';
                if (d === 'standard') d = 'comfortable';
                document.documentElement.classList.add('gp-density-' + d);
            } catch (e) {}
        })();
    </script>
    @if(!empty($workspaceBrandingCss))
        <style id="gp-branding-vars">:root { @foreach($workspaceBrandingCss as $k => $v){{ $k }}: {{ $v }};@endforeach }</style>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    @if(session('admin_impersonator_id'))
        <div class="pa-impersonate-bar" style="position:sticky;top:0;z-index:60;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.65rem 1rem;background:linear-gradient(90deg,#14532d,#166534);color:#ecfdf5;font-size:.8125rem;font-weight:600;">
            <span>Console Super Admin · ERP de <strong>{{ session('admin_impersonating_company_name') }}</strong></span>
            <form method="POST" action="{{ route('admin.leave-impersonation') }}">
                @csrf
                <button type="submit" style="border-radius:.5rem;background:#fff;color:#14532d;padding:.35rem .85rem;font-weight:700;">Retour à la Console GreenPOS</button>
            </form>
        </div>
    @endif
    <div id="gp-overlay" class="gp-overlay lg:hidden" aria-hidden="true"></div>

    {{-- Command palette --}}
    <div id="gp-cmd" class="gp-cmd" role="dialog" aria-modal="true" aria-label="Recherche globale" hidden>
        <div class="gp-cmd-panel">
            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gp-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                <input id="gp-cmd-input" type="search" class="w-full border-0 bg-transparent py-4 pl-11 pr-4 text-sm text-gp-text outline-none placeholder:text-gp-muted" placeholder="Rechercher produits, clients, factures… ou taper une commande" autocomplete="off">
            </div>
            <div id="gp-cmd-results" class="gp-cmd-results" role="listbox"></div>
            <div class="flex items-center justify-between border-t border-gp-border px-4 py-2 text-[11px] text-gp-muted">
                <span>↑↓ naviguer · ↵ ouvrir · esc fermer</span>
                <span>Recherche universelle · Ctrl+K</span>
            </div>
        </div>
    </div>

    <div class="gp-shell">
        <aside id="gp-sidebar" class="gp-sidebar" aria-label="Navigation principale">
            <div class="gp-sidebar-head flex h-[var(--gp-header-height)] items-center justify-between gap-2 border-b px-3">
                <a href="{{ route('home') }}" class="gp-sidebar-brand flex min-w-0 items-center gap-3 rounded-xl px-1 py-1 transition">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-400 to-emerald-700 text-sm font-extrabold text-white shadow-lg shadow-teal-900/40">G</span>
                    <span class="gp-brand-text min-w-0">
                        <span class="gp-sidebar-heading block truncate text-sm font-bold tracking-tight">GreenPOS</span>
                        <span class="block truncate text-[11px] text-gp-sidebar-text">Enterprise</span>
                    </span>
                </a>
                <button type="button" id="gp-sidebar-close" class="gp-btn-ghost lg:hidden" aria-label="Fermer le menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <button type="button" id="gp-sidebar-collapse" class="gp-btn-ghost hidden text-gp-sidebar-text lg:inline-flex" aria-label="Replier la sidebar" title="Replier">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>

            <div class="gp-nav-search relative">
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gp-sidebar-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                <input id="gp-nav-filter" type="search" placeholder="Filtrer les menus…" aria-label="Filtrer les menus">
            </div>

            <nav id="gp-nav" class="flex-1 overflow-y-auto px-2 pb-4" data-gp-nav>
                <div id="gp-favorites" class="gp-nav-group hidden" data-fav-group>
                    <div class="gp-nav-section-title">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <span>Favoris</span>
                    </div>
                    <div id="gp-favorites-list"></div>
                </div>

                @include('partials.module-sidebar')
                @if(false)
                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                        <span>Pilotage</span>
                    </div>
                    <a href="{{ route('home') }}" class="gp-nav-link {{ request()->routeIs('home') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Tableau de bord" data-nav-href="{{ route('home') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Tableau de bord</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    <a href="{{ route('ai.dashboard') }}" class="gp-nav-link {{ request()->routeIs('ai.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="GreenPOS AI" data-nav-href="{{ route('ai.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 15.5l.6 1.7 1.7.6-1.7.6-.6 1.7-.6-1.7-1.7-.6 1.7-.6.6-1.7z"/></svg>
                        <span>GreenPOS AI</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @can('reports.view')
                    <a href="{{ route('reports.dashboard') }}" class="gp-nav-link {{ request()->routeIs('reports.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Rapports" data-nav-href="{{ route('reports.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5"/></svg>
                        <span>Rapports</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('documents.view')
                    <a href="{{ route('documents.dashboard') }}" class="gp-nav-link {{ request()->routeIs('documents.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Documents" data-nav-href="{{ route('documents.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        <span>Documents</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>

                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9"/></svg>
                        <span>Ventes</span>
                    </div>
                    @can('pos.view')
                    <a href="{{ route('pos.dashboard') }}" class="gp-nav-link {{ request()->routeIs('pos.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="POS / Caisse" data-nav-href="{{ route('pos.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>
                        <span>POS / Caisse</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('sales.view')
                    <a href="{{ route('sales.dashboard') }}" class="gp-nav-link {{ request()->routeIs('sales.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Ventes" data-nav-href="{{ route('sales.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                        <span>Ventes</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>

                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"/></svg>
                        <span>Catalogue</span>
                    </div>
                    @can('products.view')
                    <a href="{{ route('products.index') }}" class="gp-nav-link {{ request()->routeIs('products.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Produits" data-nav-href="{{ route('products.index') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"/></svg>
                        <span>Produits</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('stock.view')
                    <a href="{{ route('stock.dashboard') }}" class="gp-nav-link {{ request()->routeIs('stock.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Stock" data-nav-href="{{ route('stock.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"/></svg>
                        <span>Stock</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('purchases.view')
                    <a href="{{ route('purchases.dashboard') }}" class="gp-nav-link {{ request()->routeIs('purchases.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Achats" data-nav-href="{{ route('purchases.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9"/></svg>
                        <span>Achats</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('suppliers.view')
                    <a href="{{ route('suppliers.dashboard') }}" class="gp-nav-link {{ request()->routeIs('suppliers.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Fournisseurs" data-nav-href="{{ route('suppliers.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Fournisseurs</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>

                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1"/></svg>
                        <span>Relation Client</span>
                    </div>
                    @can('customers.view')
                    <a href="{{ route('customers.dashboard') }}" class="gp-nav-link {{ request()->routeIs('customers.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Clients" data-nav-href="{{ route('customers.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Clients</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    <a href="{{ route('crm.dashboard') }}" class="gp-nav-link {{ request()->routeIs('crm.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="CRM Enterprise" data-nav-href="{{ route('crm.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h12M3 17h18"/></svg>
                        <span>CRM Enterprise</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @can('notifications.view')
                    <a href="{{ route('notifications.dashboard') }}" class="gp-nav-link {{ request()->routeIs('notifications.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Notifications" data-nav-href="{{ route('notifications.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span>Notifications</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>

                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/></svg>
                        <span>Finance</span>
                    </div>
                    @can('reports.financial')
                    <a href="{{ route('reports.payments') }}" class="gp-nav-link {{ request()->routeIs('reports.payments') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Paiements" data-nav-href="{{ route('reports.payments') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6"/></svg>
                        <span>Paiements</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @elsecan('payments.view')
                    <a href="{{ route('sales.dashboard') }}" class="gp-nav-link {{ request()->routeIs('sales.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Paiements" data-nav-href="{{ route('sales.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6"/></svg>
                        <span>Paiements</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('invoices.view')
                    <a href="{{ route('invoices.dashboard') }}" class="gp-nav-link {{ request()->routeIs('invoices.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Facturation" data-nav-href="{{ route('invoices.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/></svg>
                        <span>Facturation</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('quotes.view')
                    <a href="{{ route('quotes.dashboard') }}" class="gp-nav-link {{ request()->routeIs('quotes.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Devis" data-nav-href="{{ route('quotes.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Devis</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>

                <div class="gp-nav-group" data-nav-group>
                    <div class="gp-nav-group-label">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0"/></svg>
                        <span>Administration</span>
                    </div>
                    @can('companies.view')
                    <a href="{{ route('companies.dashboard') }}" class="gp-nav-link {{ request()->routeIs('companies.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Entreprises" data-nav-href="{{ route('companies.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Entreprises</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('stores.view')
                    <a href="{{ route('stores.dashboard') }}" class="gp-nav-link {{ request()->routeIs('stores.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Boutiques" data-nav-href="{{ route('stores.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Boutiques</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('users.view')
                    <a href="{{ route('users.dashboard') }}" class="gp-nav-link {{ request()->routeIs('users.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Utilisateurs" data-nav-href="{{ route('users.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/></svg>
                        <span>Utilisateurs</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('roles.view')
                    <a href="{{ route('roles.dashboard') }}" class="gp-nav-link {{ request()->routeIs('roles.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Rôles & Permissions" data-nav-href="{{ route('roles.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Rôles & Permissions</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('audit.view')
                    <a href="{{ route('audit.dashboard') }}" class="gp-nav-link {{ request()->routeIs('audit.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Journal d'audit" data-nav-href="{{ route('audit.dashboard') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Journal d'audit</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                    @can('settings.view')
                    <a href="{{ route('settings.index') }}" class="gp-nav-link {{ request()->routeIs('settings.*') ? 'gp-nav-link-active' : '' }}" data-nav-item data-nav-label="Paramètres" data-nav-href="{{ route('settings.index') }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Paramètres</span>
                        <span role="button" tabindex="0" class="gp-fav-star" data-fav-toggle aria-label="Ajouter aux favoris"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg></span>
                    </a>
                    @endcan
                </div>
                @endif
            </nav>

            <div class="gp-sidebar-foot border-t p-4">
                <div class="gp-sidebar-footer-text space-y-2">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gp-sidebar-text/70">Entreprise active</p>
                        <p class="gp-sidebar-heading truncate text-sm font-semibold">{{ $workspaceCompany->name ?? 'Entreprise Démo' }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px] text-gp-sidebar-text">
                        <span>Enterprise · UX</span>
                        <span class="rounded-md bg-teal-500/15 px-1.5 py-0.5 font-semibold text-teal-800 dark:text-teal-300">{{ ucfirst($workspaceRole ?? 'owner') }}</span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="gp-main">
            <header class="gp-header">
                <button type="button" id="gp-sidebar-open" class="gp-btn-ghost lg:hidden" aria-label="Ouvrir le menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="min-w-0 flex-1">
                    <label class="gp-search">
                        <span class="sr-only">Recherche globale</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gp-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                        <input id="gp-global-search" type="search" placeholder="Recherche universelle…" readonly aria-haspopup="dialog">
                        <span class="gp-search-kbd" aria-hidden="true"><kbd>⌘</kbd><kbd>K</kbd></span>
                    </label>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <div class="relative">
                        <button type="button" class="gp-topbar-chip" data-dropdown-trigger="gp-company-menu" title="Entreprise active" aria-haspopup="true" aria-expanded="false">
                            <span class="h-1.5 w-1.5 rounded-full bg-gp-primary"></span>
                            <span class="max-w-[7rem] truncate sm:max-w-[10rem]">{{ $workspaceCompany->name ?? 'Entreprise' }}</span>
                            <svg class="h-3 w-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="gp-company-menu" class="gp-dropdown right-0 w-72" role="menu">
                            <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-gp-muted">Changer d'entreprise</p>
                            @foreach(($workspaceSwitchableCompanies ?? collect()) as $co)
                                <form method="POST" action="{{ route('companies.switch', $co) }}">
                                    @csrf
                                    <button type="submit" class="gp-dropdown-item {{ ($workspaceCompany->id ?? null) === $co->id ? 'bg-gp-primary-soft text-gp-primary' : '' }}">
                                        @if($co->logoUrl())
                                            <img src="{{ $co->logoUrl() }}" alt="" class="h-7 w-7 rounded-lg object-cover">
                                        @else
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gp-bg text-[10px] font-bold">{{ $co->initials() }}</span>
                                        @endif
                                        <span class="min-w-0 flex-1 truncate text-left">
                                            <span class="block font-semibold">{{ $co->name }}</span>
                                            <span class="block text-[11px] text-gp-muted">{{ $co->currency }} · {{ $co->country ?: '—' }}</span>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                            @can('companies.view')
                                <div class="my-1 border-t border-gp-border"></div>
                                <a href="{{ route('companies.dashboard') }}" class="gp-dropdown-item">Gérer les entreprises</a>
                            @endcan
                            @can('companies.create')
                                <a href="{{ route('companies.create') }}" class="gp-dropdown-item">Nouvelle entreprise</a>
                            @endcan
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" class="gp-topbar-chip" data-dropdown-trigger="gp-store-menu" title="Boutique active" aria-haspopup="true" aria-expanded="false">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4"/></svg>
                            <span class="max-w-[7rem] truncate sm:max-w-[10rem]">
                                {{ !empty($workspaceStoreFilterAll) ? 'Toutes les boutiques' : ($workspaceStore->name ?? 'Boutique') }}
                            </span>
                            <svg class="h-3 w-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="gp-store-menu" class="gp-dropdown right-0 w-64" role="menu">
                            <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-gp-muted">Changer de boutique</p>
                            @foreach(($workspaceAccessibleStores ?? collect()) as $st)
                                <form method="POST" action="{{ route('stores.switch', $st) }}">
                                    @csrf
                                    <button type="submit" class="gp-dropdown-item {{ (!$workspaceStoreFilterAll && ($workspaceStore->id ?? null) === $st->id) ? 'bg-gp-primary-soft text-gp-primary' : '' }}">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gp-bg text-[10px] font-bold">{{ $st->initials() }}</span>
                                        <span class="min-w-0 flex-1 truncate text-left">
                                            <span class="block font-semibold">{{ $st->name }}</span>
                                            <span class="block text-[11px] text-gp-muted">{{ $st->city ?: '—' }}</span>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                            @if(\App\Support\Workspace::canAccessAllStores())
                                <div class="my-1 border-t border-gp-border"></div>
                                <form method="POST" action="{{ route('stores.switch-all') }}">
                                    @csrf
                                    <button type="submit" class="gp-dropdown-item {{ !empty($workspaceStoreFilterAll) ? 'bg-gp-primary-soft text-gp-primary' : '' }}">Toutes les boutiques</button>
                                </form>
                            @endif
                            @can('stores.view')
                                <div class="my-1 border-t border-gp-border"></div>
                                <a href="{{ route('stores.dashboard') }}" class="gp-dropdown-item">Gérer les boutiques</a>
                            @endcan
                        </div>
                    </div>

                    <div class="relative hidden sm:block">
                        <button type="button" class="gp-btn-secondary !py-2" data-dropdown-trigger="gp-new-menu" title="Actions rapides" aria-haspopup="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span class="hidden md:inline">Nouveau</span>
                        </button>
                        <div id="gp-new-menu" class="gp-dropdown right-0 w-56" role="menu">
                            @can('products.create')<a href="{{ route('products.create') }}" class="gp-dropdown-item">Produit</a>@endcan
                            @can('customers.create')<a href="{{ route('customers.create') }}" class="gp-dropdown-item">Client</a>@endcan
                            @can('sales.create')<a href="{{ route('sales.create') }}" class="gp-dropdown-item">Vente</a>@endcan
                            @can('invoices.create')<a href="{{ route('invoices.create') }}" class="gp-dropdown-item">Facture</a>@endcan
                            @can('quotes.create')<a href="{{ route('quotes.create') }}" class="gp-dropdown-item">Devis</a>@endcan
                            @can('pos.sell')<a href="{{ route('pos.terminal') }}" class="gp-dropdown-item">Ticket POS</a>@endcan
                            @can('documents.create')<a href="{{ route('documents.upload') }}" class="gp-dropdown-item">Document</a>@endcan
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" id="gp-theme-toggle" data-theme-toggle class="gp-icon-btn" aria-label="Changer le thème" title="Thème clair / sombre">
                            <svg data-theme-icon="light" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <svg data-theme-icon="dark" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        </button>
                    </div>

                    <button type="button" id="gp-shortcuts-open" class="gp-icon-btn hidden sm:inline-flex" aria-label="Raccourcis clavier" title="Raccourcis (?)">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 4h.01M9 3H5a2 2 0 00-2 2v4m0 6v4a2 2 0 002 2h4m6-18h4a2 2 0 012 2v4m0 6v4a2 2 0 01-2 2h-4"/></svg>
                    </button>
                    <button type="button" id="gp-help-open" class="gp-icon-btn" aria-label="Aide contextuelle" title="Aide">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>

                    <div class="relative">
                        <button type="button" data-dropdown-trigger="gp-notif-menu" class="gp-icon-btn" aria-label="Notifications" aria-haspopup="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if(($workspaceUnreadNotifications ?? 0) > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white ring-2 ring-gp-surface">{{ $workspaceUnreadNotifications > 9 ? '9+' : $workspaceUnreadNotifications }}</span>
                            @endif
                        </button>
                        <div id="gp-notif-menu" class="gp-dropdown w-80" role="menu">
                            <div class="flex items-center justify-between border-b border-gp-border px-3 py-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Notifications</span>
                                @if(($workspaceUnreadNotifications ?? 0) > 0)
                                    <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-600">{{ $workspaceUnreadNotifications }} non lue(s)</span>
                                @endif
                            </div>
                            @forelse(($workspaceLatestNotifications ?? collect()) as $n)
                                <a href="{{ route('notifications.show', $n) }}" class="gp-dropdown-item {{ $n->isUnread() ? 'bg-gp-primary-soft/40' : '' }}">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $n->typeColor() }} text-[10px] font-bold uppercase">{{ substr($n->type, 0, 1) }}</span>
                                    <span class="min-w-0 flex-1 text-left">
                                        <span class="block truncate text-sm font-semibold">{{ $n->title }}</span>
                                        <span class="block truncate text-[11px] text-gp-muted">{{ $n->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <div class="px-3 py-8 text-center text-xs text-gp-muted">Aucune notification</div>
                            @endforelse
                            <div class="my-1 border-t border-gp-border"></div>
                            @can('notifications.view')
                                <a href="{{ route('notifications.dashboard') }}" class="gp-dropdown-item font-semibold text-gp-primary">Ouvrir le centre</a>
                            @endcan
                            @can('notifications.update')
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                    @csrf
                                    <button type="submit" class="gp-dropdown-item">Tout marquer comme lu</button>
                                </form>
                            @endcan
                        </div>
                    </div>

                    <div class="relative">
                        @php
                            $authUser = auth()->user();
                            $authInitials = $authUser ? (method_exists($authUser, 'initials') ? $authUser->initials() : strtoupper(substr($authUser->name ?? 'U', 0, 2))) : 'GP';
                            $authName = $authUser ? (method_exists($authUser, 'displayName') ? $authUser->displayName() : ($authUser->name ?? 'Utilisateur')) : 'Invité';
                            $authPhoto = $authUser && method_exists($authUser, 'photoUrl') ? $authUser->photoUrl() : null;
                            $authRoleLabel = $authUser && method_exists($authUser, 'roleLabel')
                                ? $authUser->roleLabel($workspaceCompany ?? null)
                                : ucfirst($workspaceRole ?? 'user');
                            $canSwitchCompany = ($workspaceSwitchableCompanies ?? collect())->count() > 1;
                            $canSwitchStore = ($workspaceAccessibleStores ?? collect())->count() > 1 || \App\Support\Workspace::canAccessAllStores();
                        @endphp
                        <button type="button" data-dropdown-trigger="gp-user-menu" class="gp-user-chip" aria-haspopup="true" aria-expanded="false" title="{{ $authName }}">
                            <span class="gp-user-avatar">
                                @if($authPhoto)
                                    <img src="{{ $authPhoto }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ $authInitials }}
                                @endif
                            </span>
                            <span class="hidden min-w-0 text-left sm:block">
                                <span class="block max-w-[9rem] truncate text-xs font-semibold text-gp-text">{{ $authName }}</span>
                                <span class="block max-w-[9rem] truncate text-[10px] text-gp-muted">{{ $authRoleLabel }}</span>
                            </span>
                            <svg class="hidden h-3.5 w-3.5 text-gp-muted sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="gp-user-menu" class="gp-dropdown gp-user-menu-panel" role="menu">
                            <div class="gp-user-menu-head">
                                <span class="gp-user-avatar gp-user-avatar-lg">
                                    @if($authPhoto)
                                        <img src="{{ $authPhoto }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        {{ $authInitials }}
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-gp-text">{{ $authName }}</p>
                                    <p class="truncate text-xs text-gp-muted">{{ $authUser?->email }}</p>
                                    <p class="mt-1 truncate text-[11px] font-medium text-gp-primary">{{ $authRoleLabel }}</p>
                                </div>
                            </div>
                            <div class="gp-user-menu-meta">
                                <div>
                                    <span class="gp-user-menu-meta-label">Entreprise</span>
                                    <span class="gp-user-menu-meta-value">{{ $workspaceCompany->name ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="gp-user-menu-meta-label">Boutique</span>
                                    <span class="gp-user-menu-meta-value">{{ !empty($workspaceStoreFilterAll) ? 'Toutes' : ($workspaceStore->name ?? '—') }}</span>
                                </div>
                            </div>
                            <div class="my-1 border-t border-gp-border"></div>
                            @if($authUser)
                                @can('users.view')
                                    <a href="{{ route('users.show', $authUser) }}" class="gp-dropdown-item">
                                        <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Mon Profil
                                    </a>
                                @else
                                    <a href="{{ route('account.index') }}" class="gp-dropdown-item">
                                        <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Mon Profil
                                    </a>
                                @endcan
                            @endif
                            <a href="{{ route('account.index') }}" class="gp-dropdown-item">
                                <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Mon Compte
                            </a>
                            <a href="{{ route('account.preferences') }}" class="gp-dropdown-item">
                                <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Préférences
                            </a>
                            @if($canSwitchCompany)
                                <button type="button" class="gp-dropdown-item w-full" data-dropdown-trigger="gp-company-menu" data-close-parent="gp-user-menu">
                                    <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg>
                                    Changer d'entreprise
                                </button>
                            @endif
                            @if($canSwitchStore)
                                <button type="button" class="gp-dropdown-item w-full" data-dropdown-trigger="gp-store-menu" data-close-parent="gp-user-menu">
                                    <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4"/></svg>
                                    Changer de boutique
                                </button>
                            @endif
                            <div class="my-1 border-t border-gp-border"></div>
                            <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gp-muted">Densité tableau</p>
                            <button type="button" class="gp-dropdown-item gp-density-btn" data-density="compact">Compacte</button>
                            <button type="button" class="gp-dropdown-item gp-density-btn" data-density="comfortable">Confortable</button>
                            <button type="button" class="gp-dropdown-item gp-density-btn" data-density="spacious">Aérée</button>
                            <div class="my-1 border-t border-gp-border"></div>
                            <form method="POST" action="{{ route('session.lock.store') }}">
                                @csrf
                                <button type="submit" class="gp-dropdown-item w-full">
                                    <svg class="h-4 w-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Verrouiller la session
                                </button>
                            </form>
                            <button type="button" class="gp-dropdown-item w-full text-rose-600" data-logout-open>
                                <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Déconnexion
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="gp-page">
                <div class="gp-page-header">
                    <p class="gp-page-eyebrow">@yield('breadcrumb', 'GreenPOS')</p>
                    <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="gp-page-title">@yield('heading', 'Tableau de bord')</h1>
                                <button
                                    type="button"
                                    id="gp-pin-page"
                                    class="gp-icon-btn gp-pin-btn"
                                    data-href="{{ url()->current() }}"
                                    data-label="@yield('heading', 'Page')"
                                    data-type="page"
                                    aria-label="Épingler aux favoris"
                                    title="Épingler aux favoris"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                </button>
                            </div>
                            @hasSection('subtitle')
                                <p class="gp-page-subtitle">@yield('subtitle')</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="gp-flash gp-flash-success" data-gp-flash="success" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="gp-flash gp-flash-error" data-gp-flash="error" role="alert">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                    <div class="gp-flash gp-flash-warning" data-gp-flash="warning" role="status">{{ session('warning') }}</div>
                @endif
                @if(session('info'))
                    <div class="gp-flash gp-flash-info" data-gp-flash="info" role="status">{{ session('info') }}</div>
                @endif

                @if($errors->any())
                    <div class="gp-flash gp-flash-error" role="alert">
                        <div>
                            <p class="font-semibold">Veuillez corriger les erreurs suivantes :</p>
                            <ul class="mt-1 list-disc pl-4 text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div id="gp-toasts" class="gp-toasts" aria-live="polite"></div>

    {{-- Help panel --}}
    <div id="gp-help" class="gp-help-panel" hidden role="dialog" aria-modal="true" aria-label="Aide">
        <div class="gp-help-backdrop"></div>
        <aside class="gp-help-drawer">
            <div class="flex items-center justify-between border-b border-gp-border px-5 py-4">
                <h2 class="text-sm font-bold text-gp-text">Aide GreenPOS</h2>
                <button type="button" class="gp-icon-btn" data-gp-close="gp-help" aria-label="Fermer">×</button>
            </div>
            <div class="space-y-4 overflow-y-auto p-5 text-sm">
                <p class="text-gp-muted">Conseils pour travailler plus vite dans l’ERP.</p>
                <div class="gp-card gp-card-flat space-y-2 p-4">
                    <p class="font-semibold text-gp-text">Recherche universelle</p>
                    <p class="text-xs text-gp-muted">Ctrl+K pour retrouver produits, clients, factures, devis, ventes, documents…</p>
                </div>
                <div class="gp-card gp-card-flat space-y-2 p-4">
                    <p class="font-semibold text-gp-text">Favoris</p>
                    <p class="text-xs text-gp-muted">Étoile dans le menu ou pin à côté du titre pour épingler pages et modules.</p>
                </div>
                <div class="gp-card gp-card-flat space-y-2 p-4">
                    <p class="font-semibold text-gp-text">Tableaux</p>
                    <p class="text-xs text-gp-muted">Masquez / réordonnez les colonnes. Densité via le menu profil.</p>
                </div>
                <div class="gp-card gp-card-flat space-y-2 p-4">
                    <p class="font-semibold text-gp-text">Documentation</p>
                    <ul class="space-y-1 text-xs">
                        <li><a class="text-gp-primary hover:underline" href="{{ route('settings.index') }}">Paramètres</a></li>
                        @can('reports.view')
                            <li><a class="text-gp-primary hover:underline" href="{{ route('reports.dashboard') }}">Rapports</a></li>
                        @endcan
                        <li><button type="button" class="text-gp-primary hover:underline" data-gp-close="gp-help" id="gp-open-shortcuts-from-help">Voir les raccourcis clavier</button></li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    {{-- Shortcuts cheatsheet --}}
    <div id="gp-shortcuts" class="gp-help-panel" hidden role="dialog" aria-modal="true" aria-label="Raccourcis">
        <div class="gp-help-backdrop"></div>
        <div class="gp-modal mx-auto mt-[12vh]">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold">Raccourcis clavier</h2>
                <button type="button" class="gp-icon-btn" data-gp-close="gp-shortcuts" aria-label="Fermer">×</button>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Recherche / Commandes</dt><dd><kbd class="gp-kbd">Ctrl</kbd> + <kbd class="gp-kbd">K</kbd></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Nouveau produit</dt><dd><kbd class="gp-kbd">Ctrl</kbd> + <kbd class="gp-kbd">N</kbd></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Sauvegarder</dt><dd><kbd class="gp-kbd">Ctrl</kbd> + <kbd class="gp-kbd">S</kbd></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Imprimer</dt><dd><kbd class="gp-kbd">Ctrl</kbd> + <kbd class="gp-kbd">P</kbd></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Fermer panneaux</dt><dd><kbd class="gp-kbd">Esc</kbd></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gp-muted">Aide raccourcis</dt><dd><kbd class="gp-kbd">?</kbd></dd></div>
            </dl>
        </div>
    </div>

    <script type="application/json" id="gp-ux-config">
        {!! json_encode([
            'searchUrl' => route('search'),
            'actions' => array_values(array_filter([
                \App\Support\Workspace::can('products.create') ? [
                    'id' => 'create-product',
                    'label' => 'Créer un produit',
                    'href' => route('products.create'),
                    'group' => 'Création rapide',
                    'icon' => '+',
                    'keywords' => 'nouveau produit create',
                ] : null,
                \App\Support\Workspace::can('customers.create') ? [
                    'id' => 'create-customer',
                    'label' => 'Créer un client',
                    'href' => route('customers.create'),
                    'group' => 'Création rapide',
                    'icon' => '+',
                    'keywords' => 'nouveau client',
                ] : null,
                \App\Support\Workspace::can('invoices.create') ? [
                    'id' => 'create-invoice',
                    'label' => 'Créer une facture',
                    'href' => route('invoices.create'),
                    'group' => 'Création rapide',
                    'icon' => '+',
                    'keywords' => 'nouvelle facture',
                ] : null,
                \App\Support\Workspace::can('invoices.view') ? [
                    'id' => 'open-invoices',
                    'label' => 'Ouvrir les factures',
                    'href' => route('invoices.index'),
                    'group' => 'Navigation',
                    'icon' => '€',
                    'keywords' => 'facture liste',
                ] : null,
                \App\Support\Workspace::can('sales.view') ? [
                    'id' => 'open-sales',
                    'label' => 'Rechercher une vente / commande',
                    'href' => route('sales.index'),
                    'group' => 'Navigation',
                    'icon' => 'V',
                    'keywords' => 'commande vente order',
                ] : null,
                \App\Support\Workspace::can('purchases.view') ? [
                    'id' => 'open-orders',
                    'label' => 'Commandes d’achat',
                    'href' => route('purchases.orders.index'),
                    'group' => 'Navigation',
                    'icon' => 'A',
                    'keywords' => 'commande achat order',
                ] : null,
                \App\Support\Workspace::can('settings.view') ? [
                    'id' => 'open-settings',
                    'label' => 'Paramètres',
                    'href' => route('settings.index'),
                    'group' => 'Navigation',
                    'icon' => '⚙',
                    'keywords' => 'settings configuration',
                ] : null,
                [
                    'id' => 'open-home',
                    'label' => 'Tableau de bord',
                    'href' => route('home'),
                    'group' => 'Navigation',
                    'icon' => '⌂',
                    'keywords' => 'home dashboard',
                ],
            ])),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script>
        document.getElementById('gp-open-shortcuts-from-help')?.addEventListener('click', () => {
            document.getElementById('gp-help')?.classList.remove('open');
            document.getElementById('gp-help').hidden = true;
            const s = document.getElementById('gp-shortcuts');
            if (s) { s.hidden = false; s.classList.add('open'); }
        });
    </script>
    @include('ai.widget')
    @include('partials.logout-modal')
</body>
</html>
