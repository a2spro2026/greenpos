<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscrire mon entreprise — GreenPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('gp-theme');
                if (t !== 'dark' && t !== 'light') t = 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
    <style>
        .reg-shell {
            min-height: 100vh;
            font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(1000px 520px at 10% -10%, rgba(34,197,94,.16), transparent 55%),
                radial-gradient(800px 420px at 100% 0%, rgba(16,185,129,.1), transparent 50%),
                linear-gradient(180deg, #fafafa 0%, #f4f4f5 100%);
            color: #18181b;
        }
        html.dark .reg-shell {
            background:
                radial-gradient(1000px 520px at 10% -10%, rgba(34,197,94,.12), transparent 55%),
                radial-gradient(800px 420px at 100% 0%, rgba(250,204,21,.06), transparent 50%),
                #09090b;
            color: #f4f4f5;
        }
        .reg-wrap { max-width: 920px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        .reg-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; }
        .reg-brand { display: flex; align-items: center; gap: .75rem; }
        .reg-mark {
            width: 2.5rem; height: 2.5rem; border-radius: .85rem;
            background: linear-gradient(135deg, #22c55e, #a3e635);
            color: #052e16; font-weight: 800; font-size: .8rem;
            display: grid; place-items: center;
        }
        .reg-brand strong { display: block; font-size: 1.05rem; letter-spacing: -.02em; }
        .reg-brand span { font-size: .7rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #16a34a; }
        .reg-steps { display: grid; grid-template-columns: repeat(5, 1fr); gap: .5rem; margin-bottom: 1.5rem; }
        .reg-step-dot {
            height: .35rem; border-radius: 999px; background: rgba(24,24,27,.08);
            transition: background .25s ease;
        }
        html.dark .reg-step-dot { background: rgba(255,255,255,.08); }
        .reg-step-dot.on { background: #22c55e; }
        .reg-step-dot.done { background: #86efac; }
        .reg-card {
            border-radius: 1.25rem;
            border: 1px solid rgba(24,24,27,.08);
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(10px);
            padding: 1.75rem;
            box-shadow: 0 20px 50px -30px rgba(0,0,0,.25);
        }
        html.dark .reg-card {
            border-color: rgba(255,255,255,.08);
            background: rgba(18,18,20,.92);
        }
        .reg-card h1 { font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; margin-bottom: .35rem; }
        .reg-card .lead { color: #71717a; margin-bottom: 1.5rem; font-size: .95rem; }
        .reg-grid { display: grid; gap: 1rem; }
        @media (min-width: 640px) { .reg-grid-2 { grid-template-columns: 1fr 1fr; } }
        .reg-label { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #71717a; margin-bottom: .4rem; }
        .reg-input, .reg-select {
            width: 100%; border-radius: .75rem; border: 1px solid rgba(24,24,27,.12);
            background: #fff; color: #18181b; padding: .75rem .9rem; font-size: .925rem;
        }
        html.dark .reg-input, html.dark .reg-select {
            border-color: rgba(255,255,255,.12); background: #0c0c0e; color: #f4f4f5;
        }
        .reg-input:focus, .reg-select:focus { outline: 2px solid rgba(34,197,94,.35); border-color: #22c55e; }
        .reg-actions { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: space-between; margin-top: 1.75rem; }
        .reg-btn {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: .75rem; padding: .7rem 1.15rem; font-weight: 600; font-size: .9rem;
            transition: transform .15s ease, background .15s ease;
        }
        .reg-btn:active { transform: scale(.98); }
        .reg-btn-primary { background: #16a34a; color: #fff; }
        .reg-btn-primary:hover { background: #15803d; }
        .reg-btn-ghost { background: transparent; color: #71717a; border: 1px solid rgba(24,24,27,.1); }
        html.dark .reg-btn-ghost { border-color: rgba(255,255,255,.12); color: #a1a1aa; }
        .reg-panel { display: none; animation: regIn .28s ease; }
        .reg-panel.active { display: block; }
        @keyframes regIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .reg-plan {
            border: 1px solid rgba(24,24,27,.1); border-radius: 1rem; padding: 1rem 1.1rem;
            cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
            background: #fff;
        }
        html.dark .reg-plan { border-color: rgba(255,255,255,.1); background: #0c0c0e; }
        .reg-plan:hover { border-color: rgba(34,197,94,.45); }
        .reg-plan.selected {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34,197,94,.18);
            background: rgba(34,197,94,.06);
        }
        .reg-plan h3 { font-weight: 700; font-size: 1.05rem; }
        .reg-plan .price { font-size: 1.35rem; font-weight: 800; letter-spacing: -.03em; margin: .35rem 0; }
        .reg-plan ul { margin-top: .65rem; display: grid; gap: .3rem; color: #71717a; font-size: .85rem; }
        .reg-plan ul li::before { content: '✓ '; color: #16a34a; font-weight: 700; }
        .reg-summary { display: grid; gap: .65rem; font-size: .925rem; }
        .reg-summary div { display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px dashed rgba(24,24,27,.08); padding-bottom: .5rem; }
        html.dark .reg-summary div { border-bottom-color: rgba(255,255,255,.08); }
        .reg-summary span { color: #71717a; }
        .reg-error {
            margin-bottom: 1rem; border-radius: .85rem; border: 1px solid rgba(244,63,94,.25);
            background: rgba(244,63,94,.08); color: #be123c; padding: .75rem 1rem; font-size: .9rem;
        }
        html.dark .reg-error { color: #fda4af; }
        .reg-meta { font-size: .75rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #16a34a; margin-bottom: .5rem; }
    </style>
</head>
<body class="reg-shell">
<div class="reg-wrap">
    <div class="reg-top">
        <div class="reg-brand">
            <a href="{{ route('site.home') }}" style="display:flex;align-items:center;gap:.75rem;color:inherit;text-decoration:none">
                <span class="reg-mark">GP</span>
                <div>
                    <strong>GreenPOS</strong>
                    <span>Inscription entreprise</span>
                </div>
            </a>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="gp-theme-toggle" data-theme-toggle class="pa-theme-btn" aria-label="Thème">◐</button>
            <a href="{{ route('site.home') }}" class="text-sm font-semibold text-zinc-500 hover:text-emerald-600">Site</a>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-zinc-500 hover:text-emerald-600">Connexion</a>
        </div>
    </div>

    <div class="reg-steps" id="reg-progress">
        @for($i = 1; $i <= 5; $i++)
            <div class="reg-step-dot {{ $i === 1 ? 'on' : '' }}" data-step-dot="{{ $i }}"></div>
        @endfor
    </div>

    <div class="reg-card">
        @if($errors->any())
            <div class="reg-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register-company.store') }}" id="reg-form" novalidate>
            @csrf

            <div class="reg-panel active" data-panel="1">
                <p class="reg-meta">Étape 1 / 5</p>
                <h1>Informations du responsable</h1>
                <p class="lead">Le compte administrateur de votre espace GreenPOS.</p>
                <div class="reg-grid reg-grid-2">
                    <div class="sm:col-span-2">
                        <label class="reg-label">Nom complet</label>
                        <input class="reg-input" type="text" name="owner_name" value="{{ old('owner_name') }}" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Téléphone</label>
                        <input class="reg-input" type="tel" name="owner_phone" value="{{ old('owner_phone') }}" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Email</label>
                        <input class="reg-input" type="email" name="owner_email" value="{{ old('owner_email') }}" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Mot de passe</label>
                        <input class="reg-input" type="password" name="password" minlength="6" required data-required autocomplete="new-password">
                    </div>
                    <div>
                        <label class="reg-label">Confirmation du mot de passe</label>
                        <input class="reg-input" type="password" name="password_confirmation" minlength="6" required data-required autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="reg-panel" data-panel="2">
                <p class="reg-meta">Étape 2 / 5</p>
                <h1>Entreprise</h1>
                <p class="lead">Les informations légales et commerciales de votre société.</p>
                <div class="reg-grid reg-grid-2">
                    <div class="sm:col-span-2">
                        <label class="reg-label">Nom de l’entreprise</label>
                        <input class="reg-input" type="text" name="company_name" value="{{ old('company_name') }}" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Activité</label>
                        <input class="reg-input" type="text" name="activity" value="{{ old('activity') }}" placeholder="Retail, restauration, services…" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Devise</label>
                        <select class="reg-select" name="currency" required data-required>
                            @foreach($currencies as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency', 'MAD') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="reg-label">Pays</label>
                        <input class="reg-input" type="text" name="country" value="{{ old('country', 'Maroc') }}" required data-required>
                    </div>
                    <div>
                        <label class="reg-label">Ville</label>
                        <input class="reg-input" type="text" name="city" value="{{ old('city') }}" required data-required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="reg-label">Adresse</label>
                        <input class="reg-input" type="text" name="address" value="{{ old('address') }}" required data-required>
                    </div>
                </div>
            </div>

            <div class="reg-panel" data-panel="3">
                <p class="reg-meta">Étape 3 / 5</p>
                <h1>Boutique principale</h1>
                <p class="lead">Votre premier point de vente. Vous pourrez en ajouter d’autres selon le plan.</p>
                <div class="reg-grid">
                    <div>
                        <label class="reg-label">Nom de la boutique</label>
                        <input class="reg-input" type="text" name="store_name" value="{{ old('store_name', 'Boutique principale') }}" required data-required>
                    </div>
                </div>
            </div>

            <div class="reg-panel" data-panel="4">
                <p class="reg-meta">Étape 4 / 5</p>
                <h1>Choix du plan</h1>
                <p class="lead">Sélectionnez l’offre adaptée à votre activité.</p>
                <div class="reg-grid" style="gap: .85rem">
                    @foreach($plans as $plan)
                        <label class="reg-plan {{ (string) old('saas_plan_id') === (string) $plan->id ? 'selected' : '' }}" data-plan-card>
                            <input type="radio" name="saas_plan_id" value="{{ $plan->id }}" class="sr-only" @checked((string) old('saas_plan_id') === (string) $plan->id) required data-required
                                   data-plan-name="{{ $plan->name }}"
                                   data-plan-price="{{ number_format((float) $plan->price_monthly, 0, ',', ' ') }} {{ $plan->currency }}/mois">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3>{{ $plan->name }}</h3>
                                    <p class="text-sm text-zinc-500">{{ $plan->tagline }}</p>
                                </div>
                                <div class="price">{{ number_format((float) $plan->price_monthly, 0, ',', ' ') }} <span class="text-sm font-semibold text-zinc-500">{{ $plan->currency }}/mois</span></div>
                            </div>
                            <ul>
                                @foreach(($plan->features ?? []) as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                                <li>{{ $plan->max_users }} utilisateurs · {{ $plan->max_stores }} boutique(s)</li>
                            </ul>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="reg-panel" data-panel="5">
                <p class="reg-meta">Étape 5 / 5</p>
                <h1>Résumé</h1>
                <p class="lead">Vérifiez vos informations avant d’envoyer la demande. Aucune entreprise active ne sera créée avant validation.</p>
                <div class="reg-summary" id="reg-summary"></div>
            </div>

            <div class="reg-actions">
                <button type="button" class="reg-btn reg-btn-ghost" id="reg-prev" hidden>Retour</button>
                <div class="ml-auto flex gap-2">
                    <button type="button" class="reg-btn reg-btn-primary" id="reg-next">Continuer</button>
                    <button type="submit" class="reg-btn reg-btn-primary" id="reg-submit" hidden>Envoyer la demande</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var step = 1;
    var total = 5;
    var form = document.getElementById('reg-form');
    var prev = document.getElementById('reg-prev');
    var next = document.getElementById('reg-next');
    var submit = document.getElementById('reg-submit');

    function panel(n) { return form.querySelector('[data-panel="' + n + '"]'); }

    function show(n) {
        step = n;
        form.querySelectorAll('.reg-panel').forEach(function (el) {
            el.classList.toggle('active', Number(el.getAttribute('data-panel')) === n);
        });
        document.querySelectorAll('[data-step-dot]').forEach(function (dot) {
            var s = Number(dot.getAttribute('data-step-dot'));
            dot.classList.toggle('on', s === n);
            dot.classList.toggle('done', s < n);
        });
        prev.hidden = n === 1;
        next.hidden = n === total;
        submit.hidden = n !== total;
        if (n === total) buildSummary();
    }

    function validateStep(n) {
        var p = panel(n);
        var fields = p.querySelectorAll('[data-required]');
        for (var i = 0; i < fields.length; i++) {
            var f = fields[i];
            if (f.type === 'radio') {
                var name = f.name;
                if (!form.querySelector('input[name="' + name + '"]:checked')) {
                    alert('Veuillez sélectionner un plan.');
                    return false;
                }
                continue;
            }
            if (!f.checkValidity()) {
                f.reportValidity();
                return false;
            }
        }
        if (n === 1) {
            var a = form.password.value;
            var b = form.password_confirmation.value;
            if (a !== b) {
                alert('La confirmation du mot de passe ne correspond pas.');
                return false;
            }
        }
        return true;
    }

    function buildSummary() {
        var plan = form.querySelector('input[name="saas_plan_id"]:checked');
        var rows = [
            ['Responsable', form.owner_name.value],
            ['Email', form.owner_email.value],
            ['Téléphone', form.owner_phone.value],
            ['Entreprise', form.company_name.value],
            ['Activité', form.activity.value],
            ['Localisation', form.city.value + ', ' + form.country.value],
            ['Adresse', form.address.value],
            ['Devise', form.currency.value],
            ['Boutique', form.store_name.value],
            ['Plan', plan ? (plan.getAttribute('data-plan-name') + ' — ' + plan.getAttribute('data-plan-price')) : '—']
        ];
        var html = '';
        rows.forEach(function (r) {
            html += '<div><span>' + r[0] + '</span><strong>' + (r[1] || '—') + '</strong></div>';
        });
        document.getElementById('reg-summary').innerHTML = html;
    }

    next.addEventListener('click', function () {
        if (!validateStep(step)) return;
        show(Math.min(total, step + 1));
    });
    prev.addEventListener('click', function () { show(Math.max(1, step - 1)); });

    form.querySelectorAll('[data-plan-card]').forEach(function (card) {
        card.addEventListener('click', function () {
            form.querySelectorAll('[data-plan-card]').forEach(function (c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            var radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    form.addEventListener('submit', function (e) {
        if (!validateStep(5)) {
            e.preventDefault();
            return;
        }
    });

    @if($errors->any())
        show(1);
    @endif
})();
</script>
</body>
</html>
