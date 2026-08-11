<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — GreenPOS Super Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body.sa-body { font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif; background: #070b14; color: #e8eef7; }
        .sa-sidebar { background: linear-gradient(180deg, #0b1220 0%, #070b14 100%); border-right: 1px solid rgba(148,163,184,.12); }
        .sa-nav-link { display:flex; align-items:center; gap:.75rem; border-radius:.75rem; padding:.65rem .85rem; font-size:.8125rem; font-weight:500; color:#94a3b8; transition:.15s; }
        .sa-nav-link:hover { background: rgba(255,255,255,.04); color:#f8fafc; }
        .sa-nav-link.active { background: rgba(56,189,248,.12); color:#7dd3fc; box-shadow: inset 2px 0 0 #38bdf8; }
        .sa-card { border-radius:1rem; border:1px solid rgba(148,163,184,.12); background:rgba(15,23,42,.72); padding:1.25rem; }
        .sa-kpi { position:relative; overflow:hidden; border-radius:1rem; border:1px solid rgba(148,163,184,.12); background:linear-gradient(145deg,rgba(15,23,42,.9),rgba(15,23,42,.55)); padding:1.25rem; }
        .sa-kpi::after { content:''; position:absolute; inset:auto -20% -40% auto; width:8rem; height:8rem; border-radius:999px; background:radial-gradient(circle,rgba(56,189,248,.18),transparent 70%); }
        .sa-input, .sa-select { width:100%; border-radius:.75rem; border:1px solid rgba(148,163,184,.2); background:#0b1220; color:#e8eef7; padding:.65rem .85rem; font-size:.875rem; }
        .sa-input:focus, .sa-select:focus { outline:none; border-color:#38bdf8; box-shadow:0 0 0 3px rgba(56,189,248,.15); }
        .sa-btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; border-radius:.75rem; padding:.6rem 1rem; font-size:.8125rem; font-weight:600; transition:.15s; }
        .sa-btn-primary { background:#0ea5e9; color:#041018; } .sa-btn-primary:hover { background:#38bdf8; }
        .sa-btn-ghost { border:1px solid rgba(148,163,184,.2); color:#cbd5e1; } .sa-btn-ghost:hover { background:rgba(255,255,255,.04); }
        .sa-btn-danger { background:rgba(244,63,94,.15); color:#fb7185; border:1px solid rgba(244,63,94,.25); }
        .sa-table { width:100%; font-size:.8125rem; } .sa-table th { text-align:left; color:#64748b; font-size:.6875rem; text-transform:uppercase; letter-spacing:.08em; padding:.75rem; border-bottom:1px solid rgba(148,163,184,.12); }
        .sa-table td { padding:.85rem .75rem; border-bottom:1px solid rgba(148,163,184,.08); vertical-align:middle; }
        .sa-table tr:hover td { background:rgba(255,255,255,.02); }
        .sa-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.2rem .55rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .sa-mono { font-family:'IBM Plex Mono', ui-monospace, monospace; }
    </style>
</head>
<body class="sa-body min-h-screen">
<div class="flex min-h-screen">
    <aside class="sa-sidebar fixed inset-y-0 left-0 z-40 flex w-64 flex-col lg:static">
        <div class="flex h-16 items-center gap-3 border-b border-white/5 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/20 text-sm font-bold text-sky-300">SA</span>
            <div>
                <p class="text-sm font-bold tracking-tight text-white">GreenPOS</p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-sky-400/80">Super Admin</p>
            </div>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @php
                $nav = [
                    ['route' => 'superadmin.dashboard', 'label' => 'Dashboard', 'match' => 'superadmin.dashboard'],
                    ['route' => 'superadmin.tenants.index', 'label' => 'Entreprises', 'match' => 'superadmin.tenants.*'],
                    ['route' => 'superadmin.billing.dashboard', 'label' => 'Billing SaaS', 'match' => 'superadmin.billing.*'],
                    ['route' => 'superadmin.subscriptions.dashboard', 'label' => 'Abonnements', 'match' => 'superadmin.subscriptions.*'],
                    ['route' => 'superadmin.plans.index', 'label' => 'Plans', 'match' => 'superadmin.plans.*'],
                    ['route' => 'superadmin.modules.index', 'label' => 'Module Manager', 'match' => 'superadmin.modules.*'],
                    ['route' => 'superadmin.invoices.index', 'label' => 'Factures SaaS', 'match' => 'superadmin.invoices.*'],
                    ['route' => 'superadmin.licenses.index', 'label' => 'Licences', 'match' => 'superadmin.licenses.*'],
                    ['route' => 'superadmin.payments.index', 'label' => 'Paiements SaaS', 'match' => 'superadmin.payments.*'],
                    ['route' => 'superadmin.domains.index', 'label' => 'Domaines', 'match' => 'superadmin.domains.*'],
                    ['route' => 'superadmin.users.index', 'label' => 'Utilisateurs globaux', 'match' => 'superadmin.users.*'],
                    ['route' => 'superadmin.monitoring.index', 'label' => 'Surveillance', 'match' => 'superadmin.monitoring.*'],
                    ['route' => 'superadmin.journal.index', 'label' => 'Journal global', 'match' => 'superadmin.journal.*'],
                ];
            @endphp
            @foreach($nav as $item)
                <a href="{{ route($item['route']) }}" class="sa-nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ request()->routeIs($item['match']) ? 'bg-sky-400' : 'bg-slate-600' }}"></span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/5 p-4 text-xs text-slate-500">
            <p class="font-semibold text-slate-300">{{ ($superAdminUser ?? auth()->user())?->displayName() ?? 'Admin' }}</p>
            <p class="mt-0.5 truncate">{{ ($superAdminUser ?? auth()->user())?->email ?? '' }}</p>
            <p class="mt-2 text-[10px] uppercase tracking-wider text-slate-600">Enterprise · Isolated</p>
            <a href="{{ route('admin.dashboard') }}" class="mt-3 inline-block text-sky-400 hover:underline">← Nouvel espace /admin</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-white/5 bg-[#070b14]/85 px-4 backdrop-blur md:px-8">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">@yield('breadcrumb', 'Platform')</p>
                <h1 class="text-lg font-bold text-white md:text-xl">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2">@yield('actions')</div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            @if(session('success'))
                <div class="gp-flash gp-flash-success mb-4" data-gp-flash="success" role="status">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
