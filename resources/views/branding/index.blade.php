@extends('layouts.app')
@section('title', 'Branding')
@section('breadcrumb', 'Paramètres')
@section('heading', 'Branding & Personnalisation')
@section('subtitle')
Identité visuelle isolée pour {{ $company->name }}.
@endsection
@section('actions')
    <a href="{{ route('settings.index') }}" class="gp-btn-secondary">Paramètres</a>
@endsection
@section('content')
@vite(['resources/css/branding.css', 'resources/js/branding.js'])

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
@endif

<div class="branding-layout" data-branding-root
     data-primary="{{ $branding['primary_color'] }}"
     data-secondary="{{ $branding['secondary_color'] }}"
     data-button="{{ $branding['button_color'] }}"
     data-link="{{ $branding['link_color'] }}"
     data-theme="{{ $branding['theme'] }}"
     data-density="{{ $branding['density'] }}"
     data-trade="{{ $branding['trade_name'] }}"
     data-tagline="{{ $branding['tagline'] }}"
     data-welcome="{{ $branding['login_welcome'] }}"
     data-footer="{{ $branding['login_footer'] }}">

    <div class="branding-tabs">
        @foreach([
            'identity' => 'Identité',
            'appearance' => 'Apparence',
            'login' => 'Login',
            'invoices' => 'Factures',
            'emails' => 'Emails',
            'locale' => 'Paramètres',
        ] as $key => $label)
            <a href="{{ route('branding.index', ['tab' => $key]) }}" class="branding-tab {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="branding-grid">
        <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="branding-form gp-card space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" value="{{ $tab }}">

            @if($tab === 'identity')
                <h2 class="text-sm font-bold">Identité visuelle</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold sm:col-span-2">Nom commercial
                        <input class="gp-input mt-1" type="text" name="trade_name" value="{{ old('trade_name', $branding['trade_name']) }}" data-brand-field="trade">
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">Slogan
                        <input class="gp-input mt-1" type="text" name="tagline" value="{{ old('tagline', $branding['tagline']) }}" data-brand-field="tagline">
                    </label>
                    <label class="block text-sm font-semibold">Logo principal
                        <input class="mt-1 block w-full text-sm" type="file" name="logo" accept="image/*">
                        @if($urls['logo_path'])<img src="{{ $urls['logo_path'] }}" alt="" class="mt-2 h-12 object-contain">@endif
                    </label>
                    <label class="block text-sm font-semibold">Logo réduit
                        <input class="mt-1 block w-full text-sm" type="file" name="logo_compact" accept="image/*">
                        @if($urls['logo_compact_path'])<img src="{{ $urls['logo_compact_path'] }}" alt="" class="mt-2 h-10 object-contain">@endif
                    </label>
                    <label class="block text-sm font-semibold">Favicon
                        <input class="mt-1 block w-full text-sm" type="file" name="favicon" accept="image/*">
                        @if($urls['favicon_path'])<img src="{{ $urls['favicon_path'] }}" alt="" class="mt-2 h-8 w-8 object-contain">@endif
                    </label>
                    <label class="block text-sm font-semibold">Couleur principale
                        <input class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-gp-border p-1" type="color" name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}" data-brand-field="primary">
                    </label>
                    <label class="block text-sm font-semibold">Couleur secondaire
                        <input class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-gp-border p-1" type="color" name="secondary_color" value="{{ old('secondary_color', $branding['secondary_color']) }}" data-brand-field="secondary">
                    </label>
                    <label class="block text-sm font-semibold">Couleur des boutons
                        <input class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-gp-border p-1" type="color" name="button_color" value="{{ old('button_color', $branding['button_color']) }}" data-brand-field="button">
                    </label>
                    <label class="block text-sm font-semibold">Couleur des liens
                        <input class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-gp-border p-1" type="color" name="link_color" value="{{ old('link_color', $branding['link_color']) }}" data-brand-field="link">
                    </label>
                </div>
            @elseif($tab === 'appearance')
                <h2 class="text-sm font-bold">Apparence</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Thème
                        <select name="theme" class="gp-input mt-1" data-brand-field="theme">
                            <option value="light" @selected($branding['theme']==='light')>Clair</option>
                            <option value="dark" @selected($branding['theme']==='dark')>Sombre</option>
                            <option value="auto" @selected(in_array($branding['theme'], ['auto','system'], true))>Automatique</option>
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Densité
                        <select name="density" class="gp-input mt-1" data-brand-field="density">
                            <option value="compact" @selected($branding['density']==='compact')>Compact</option>
                            <option value="standard" @selected($branding['density']==='standard')>Standard</option>
                            <option value="comfortable" @selected($branding['density']==='comfortable')>Confortable</option>
                        </select>
                    </label>
                </div>
                {{-- Keep colors when saving appearance tab --}}
                <input type="hidden" name="primary_color" value="{{ $branding['primary_color'] }}">
                <input type="hidden" name="secondary_color" value="{{ $branding['secondary_color'] }}">
                <input type="hidden" name="button_color" value="{{ $branding['button_color'] }}">
                <input type="hidden" name="link_color" value="{{ $branding['link_color'] }}">
            @elseif($tab === 'login')
                <h2 class="text-sm font-bold">Page de connexion</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold sm:col-span-2">Message d’accueil
                        <input class="gp-input mt-1" type="text" name="login_welcome" value="{{ old('login_welcome', $branding['login_welcome']) }}" data-brand-field="welcome">
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">Pied de page
                        <input class="gp-input mt-1" type="text" name="login_footer" value="{{ old('login_footer', $branding['login_footer']) }}" data-brand-field="footer">
                    </label>
                    <label class="block text-sm font-semibold">Image de fond
                        <input class="mt-1 block w-full text-sm" type="file" name="login_background" accept="image/*">
                        @if($urls['login_background_path'])<img src="{{ $urls['login_background_path'] }}" alt="" class="mt-2 h-20 w-full rounded-lg object-cover">@endif
                    </label>
                    <label class="block text-sm font-semibold">Logo login
                        <input class="mt-1 block w-full text-sm" type="file" name="login_logo" accept="image/*">
                        @if($urls['login_logo_path'])<img src="{{ $urls['login_logo_path'] }}" alt="" class="mt-2 h-12 object-contain">@endif
                    </label>
                </div>
            @elseif($tab === 'invoices')
                <h2 class="text-sm font-bold">Documents de facturation</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Couleur facture
                        <input class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-gp-border p-1" type="color" name="invoice_primary_color" value="{{ old('invoice_primary_color', $branding['invoice_primary_color']) }}" data-brand-field="invoice_color">
                    </label>
                    <label class="block text-sm font-semibold">Logo facture
                        <input class="mt-1 block w-full text-sm" type="file" name="invoice_logo" accept="image/*">
                        @if($urls['invoice_logo_path'] ?? $urls['logo_path'])<img src="{{ $urls['invoice_logo_path'] ?: $urls['logo_path'] }}" alt="" class="mt-2 h-12 object-contain">@endif
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">En-tête
                        <textarea class="gp-input mt-1" name="invoice_header" rows="2" data-brand-field="invoice_header">{{ old('invoice_header', $branding['invoice_header']) }}</textarea>
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">Pied de page
                        <textarea class="gp-input mt-1" name="invoice_footer" rows="2" data-brand-field="invoice_footer">{{ old('invoice_footer', $branding['invoice_footer']) }}</textarea>
                    </label>
                    <label class="block text-sm font-semibold sm:col-span-2">Mentions légales
                        <textarea class="gp-input mt-1" name="invoice_legal" rows="3" data-brand-field="invoice_legal">{{ old('invoice_legal', $branding['invoice_legal']) }}</textarea>
                    </label>
                    <label class="block text-sm font-semibold">Signature
                        <input class="mt-1 block w-full text-sm" type="file" name="invoice_signature" accept="image/*">
                        @if($urls['invoice_signature_path'])<img src="{{ $urls['invoice_signature_path'] }}" alt="" class="mt-2 h-12 object-contain">@endif
                    </label>
                    <label class="block text-sm font-semibold">Cachet
                        <input class="mt-1 block w-full text-sm" type="file" name="invoice_stamp" accept="image/*">
                        @if($urls['invoice_stamp_path'])<img src="{{ $urls['invoice_stamp_path'] }}" alt="" class="mt-2 h-12 object-contain">@endif
                    </label>
                </div>
            @elseif($tab === 'emails')
                <h2 class="text-sm font-bold">Modèles d’e-mails</h2>
                <p class="text-xs text-gp-muted">Variables : {{name}}, {{company}}, {{number}}, {{amount}}, {{link}}, {{message}}</p>
                <div class="space-y-4">
                    @foreach([
                        'welcome' => 'Bienvenue',
                        'password_reset' => 'Réinitialisation mot de passe',
                        'invoice' => 'Facture',
                        'quote' => 'Devis',
                        'payment' => 'Paiement',
                        'notification' => 'Notification',
                    ] as $key => $label)
                        @php $tpl = $branding['emails'][$key] ?? ['subject'=>'','body'=>'']; @endphp
                        <details class="rounded-xl border border-gp-border p-3" {{ $loop->first ? 'open' : '' }}>
                            <summary class="cursor-pointer font-semibold">{{ $label }}</summary>
                            <div class="mt-3 grid gap-3">
                                <label class="text-xs font-semibold">Objet
                                    <input class="gp-input mt-1" type="text" name="emails[{{ $key }}][subject]" value="{{ $tpl['subject'] }}">
                                </label>
                                <label class="text-xs font-semibold">Corps
                                    <textarea class="gp-input mt-1" name="emails[{{ $key }}][body]" rows="5">{{ $tpl['body'] }}</textarea>
                                </label>
                            </div>
                        </details>
                    @endforeach
                </div>
            @else
                <h2 class="text-sm font-bold">Paramètres régionaux</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Fuseau horaire
                        <select name="timezone" class="gp-input mt-1">
                            @foreach(['Africa/Casablanca','Europe/Paris','UTC','Africa/Tunis','Africa/Algiers'] as $tz)
                                <option value="{{ $tz }}" @selected($branding['timezone']===$tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Langue
                        <select name="locale" class="gp-input mt-1">
                            <option value="fr" @selected($branding['locale']==='fr')>Français</option>
                            <option value="ar" @selected($branding['locale']==='ar')>Arabe</option>
                            <option value="en" @selected($branding['locale']==='en')>English</option>
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Devise
                        <select name="currency" class="gp-input mt-1">
                            @foreach(['MAD','EUR','USD'] as $c)
                                <option value="{{ $c }}" @selected($branding['currency']===$c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Format de date
                        <select name="date_format" class="gp-input mt-1">
                            @foreach(['d/m/Y','Y-m-d','m/d/Y','d-m-Y'] as $fmt)
                                <option value="{{ $fmt }}" @selected($branding['date_format']===$fmt)>{{ $fmt }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Format des nombres
                        <select name="number_format" class="gp-input mt-1">
                            <option value="fr" @selected($branding['number_format']==='fr')>1 234,56</option>
                            <option value="en" @selected($branding['number_format']==='en')>1,234.56</option>
                        </select>
                    </label>
                </div>
            @endif

            <div class="flex justify-end gap-2 border-t border-gp-border pt-4">
                <button type="submit" class="gp-btn-primary">Enregistrer</button>
            </div>
        </form>

        <aside class="branding-preview gp-card">
            <p class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gp-muted">Aperçu en temps réel</p>
            <div class="branding-preview-shell" data-preview-shell>
                <div class="branding-preview-bar" data-preview-bar>
                    <span data-preview-trade>{{ $branding['trade_name'] ?: $company->name }}</span>
                </div>
                <div class="branding-preview-body" data-preview-body>
                    <p class="text-xs text-gp-muted" data-preview-tagline>{{ $branding['tagline'] ?: 'Slogan de votre marque' }}</p>
                    <p class="mt-3 text-sm font-semibold" data-preview-welcome>{{ $branding['login_welcome'] }}</p>
                    <button type="button" class="branding-preview-btn mt-4" data-preview-btn>Bouton principal</button>
                    <a href="#" class="branding-preview-link mt-3 inline-block text-sm" data-preview-link onclick="return false">Lien d’exemple</a>
                    <div class="branding-preview-invoice mt-5" data-preview-invoice>
                        <p class="text-[10px] font-bold uppercase" data-preview-invoice-title>Facture FAC-0001</p>
                        <p class="mt-1 text-[11px]" data-preview-invoice-header>{{ $branding['invoice_header'] ?: 'En-tête document' }}</p>
                        <div class="mt-2 h-1.5 rounded" data-preview-invoice-accent></div>
                        <p class="mt-2 text-[10px] text-gp-muted" data-preview-invoice-footer>{{ $branding['invoice_footer'] ?: 'Pied de page' }}</p>
                    </div>
                    <p class="mt-4 text-[10px] text-gp-muted" data-preview-footer>{{ $branding['login_footer'] }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-gp-muted">Entreprise active : <strong>{{ $company->name }}</strong> — isolation stricte.</p>
        </aside>
    </div>
</div>
@endsection
