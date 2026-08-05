<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Déconnecté — GreenPOS</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6f5; color: #0f1c19; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .card { background: #fff; border: 1px solid #e2e8e5; border-radius: 1rem; padding: 2rem; max-width: 24rem; text-align: center; box-shadow: 0 8px 24px rgba(15,28,25,.06); }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #64748b; font-size: .875rem; margin: 0 0 1.25rem; }
        a { display: inline-flex; background: #0f766e; color: #fff; text-decoration: none; padding: .65rem 1rem; border-radius: .75rem; font-weight: 600; font-size: .875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Vous êtes déconnecté</h1>
        <p>Session GreenPOS fermée. En environnement local, la reconnexion démo reste disponible.</p>
        <a href="{{ url('/') }}">Retour à l'application</a>
    </div>
</body>
</html>
