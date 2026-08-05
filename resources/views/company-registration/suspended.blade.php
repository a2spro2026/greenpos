<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entreprise suspendue — GreenPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body {
            min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 1.5rem;
            font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(180deg, #fafafa, #f4f4f5); color: #18181b;
        }
        .box {
            max-width: 28rem; width: 100%; text-align: center;
            border-radius: 1.25rem; border: 1px solid rgba(24,24,27,.1);
            background: #fff; padding: 2rem 1.5rem;
            box-shadow: 0 20px 50px -30px rgba(0,0,0,.3);
        }
        h1 { font-size: 1.35rem; font-weight: 800; margin: 0 0 .75rem; letter-spacing: -.02em; }
        p { color: #71717a; line-height: 1.6; margin: 0 0 .75rem; }
        form { margin-top: 1.25rem; }
        button {
            border: 0; border-radius: .75rem; background: #18181b; color: #fff;
            padding: .7rem 1.1rem; font-weight: 600; cursor: pointer;
        }
    </style>
</head>
<body>
<div class="box">
    <h1>Votre entreprise est actuellement suspendue.</h1>
    <p>Veuillez contacter GreenPOS.</p>
    @if(!empty($companyName))
        <p><strong>{{ $companyName }}</strong></p>
    @endif
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</div>
</body>
</html>
