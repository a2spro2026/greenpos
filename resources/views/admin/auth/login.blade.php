<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion Super Admin — GreenPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
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
<div class="pa-login relative">
    <a href="{{ route('site.home') }}" class="pa-login-back absolute left-4 top-4">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour
    </a>
    <button type="button" id="gp-theme-toggle" data-theme-toggle class="pa-theme-btn absolute right-4 top-4" aria-label="Changer le thème">
        <svg data-theme-icon="light" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <svg data-theme-icon="dark" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
    </button>
    <div class="pa-login-card">
        <div class="mb-6 flex items-center gap-3">
            <span class="pa-brand-mark">GP</span>
            <div>
                <p class="pa-brand-title text-lg">GreenPOS</p>
                <p class="pa-brand-sub">Super Admin</p>
            </div>
        </div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Connexion plateforme</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Espace réservé au propriétaire de GreenPOS. Séparé de l’ERP client.</p>

        @if(session('status'))
            <div class="mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-sm text-rose-700 dark:text-rose-300">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Identifiant</label>
                <input type="text" name="email" value="{{ old('email', \App\Services\PlatformBootstrapService::SUPER_ADMIN_USERNAME) }}" required autofocus autocomplete="username" class="pa-input" placeholder="bilal">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Mot de passe</label>
                <input type="password" name="password" value="{{ \App\Services\PlatformBootstrapService::SUPER_ADMIN_PASSWORD }}" required class="pa-input" placeholder="0661755048" autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300 dark:border-zinc-600">
                Se souvenir de moi
            </label>
            <button type="submit" class="pa-btn pa-btn-primary w-full py-3">Accéder au Super Admin</button>
        </form>
        <p class="mt-6 text-center text-xs text-zinc-500">{{ \App\Services\PlatformBootstrapService::SUPER_ADMIN_USERNAME }}</p>
    </div>
</div>
</body>
</html>
