<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Session verrouillée — GreenPOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gp-lock-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background:
                linear-gradient(180deg, rgba(11, 31, 28, 0.92), rgba(11, 31, 28, 0.88)),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2314b8a6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"),
                #0b1f1c;
            backdrop-filter: blur(8px);
        }
        .gp-lock-card {
            width: 100%;
            max-width: 24rem;
            text-align: center;
            color: #ecfdf8;
        }
        .gp-lock-avatar {
            width: 5.5rem; height: 5.5rem; margin: 0 auto 1.25rem;
            border-radius: 1.5rem; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 700;
            background: linear-gradient(135deg, #14b8a6, #0f766e);
            box-shadow: 0 0 0 4px rgba(20,184,166,0.25), 0 20px 40px rgba(0,0,0,0.35);
        }
        .gp-lock-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .gp-lock-input {
            width: 100%; border-radius: 0.85rem; border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08); color: #fff; padding: 0.8rem 1rem;
            text-align: center; font-size: 0.95rem;
        }
        .gp-lock-input::placeholder { color: rgba(236,253,248,0.45); }
        .gp-lock-input:focus { outline: 2px solid rgba(45,212,191,0.45); border-color: #2dd4bf; }
        .gp-lock-btn {
            width: 100%; margin-top: 0.75rem; border: 0; border-radius: 0.85rem;
            background: #14b8a6; color: #042f2e; font-weight: 700; padding: 0.8rem;
            cursor: pointer;
        }
        .gp-lock-btn:hover { background: #2dd4bf; }
        .gp-lock-link {
            display: inline-block; margin-top: 1rem; color: rgba(204,251,241,0.75);
            font-size: 0.8rem; text-decoration: none;
        }
        .gp-lock-link:hover { color: #fff; }
        .gp-lock-error {
            margin-bottom: 0.75rem; border-radius: 0.75rem; padding: 0.65rem;
            background: rgba(244,63,94,0.15); color: #fecdd3; font-size: 0.8rem;
        }
    </style>
</head>
<body class="h-full antialiased">
<div class="gp-lock-shell">
    <div class="gp-lock-card">
        <div class="gp-lock-avatar">
            @if($user->photoUrl())
                <img src="{{ $user->photoUrl() }}" alt="">
            @else
                {{ $user->initials() }}
            @endif
        </div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300/80">Session verrouillée</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $user->displayName() }}</h1>
        <p class="mt-1 text-sm text-teal-100/60">{{ $user->email }}</p>
        @if($company)
            <p class="mt-3 text-xs text-teal-200/50">{{ $company->name }}@if($store) · {{ $store->name }}@endif · {{ $role }}</p>
        @endif

        <form method="POST" action="{{ route('session.unlock') }}" class="mt-8 text-left">
            @csrf
            @if($errors->any())
                <div class="gp-lock-error">{{ $errors->first() }}</div>
            @endif
            <label class="mb-1 block text-left text-xs font-semibold text-teal-100/70">Mot de passe</label>
            <input class="gp-lock-input" type="password" name="password" required autofocus autocomplete="current-password" placeholder="Saisissez votre mot de passe">
            <button type="submit" class="gp-lock-btn">Déverrouiller</button>
        </form>

        <form method="POST" action="{{ route('login.switch') }}">
            @csrf
            <button type="submit" class="gp-lock-link" style="background:none;border:0;cursor:pointer;width:100%">Changer de compte</button>
        </form>
    </div>
</div>
</body>
</html>
