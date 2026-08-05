<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $user->displayName() }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',system-ui,sans-serif;font-size:12px;color:#1e293b;padding:24px}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;border-bottom:2px solid #16a34a;padding-bottom:16px}
        .header h1{font-size:22px;color:#16a34a}
        .meta{color:#64748b;margin-top:4px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
        .grid dt{color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.4px}
        .grid dd{font-weight:600;margin-top:2px;margin-bottom:10px}
        .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534}
        @media print{button{display:none!important}body{padding:0}}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $user->displayName() }}</h1>
            <p class="meta">{{ $company->name }} · {{ $user->roleLabel($company) }}</p>
        </div>
        <button onclick="window.print()" style="padding:6px 16px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer">Imprimer</button>
    </div>

    <p style="margin-bottom:16px"><span class="badge">{{ $user->statusLabel() }}</span></p>

    <dl class="grid">
        <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
        <div><dt>Téléphone</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
        <div><dt>Username</dt><dd>{{ $user->username ?: '—' }}</dd></div>
        <div><dt>Fonction</dt><dd>{{ $user->job_title ?: '—' }}</dd></div>
        <div><dt>Département</dt><dd>{{ $user->departmentLabel() }}</dd></div>
        <div><dt>Embauche</dt><dd>{{ optional($user->hired_at)->format('d/m/Y') ?: '—' }}</dd></div>
        <div><dt>Dernière connexion</dt><dd>{{ optional($user->last_login_at)->format('d/m/Y H:i') ?: 'Jamais' }}</dd></div>
        <div><dt>Boutiques</dt><dd>{{ $user->stores->pluck('name')->implode(', ') ?: '—' }}</dd></div>
    </dl>
</body>
</html>
