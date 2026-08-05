@extends('layouts.site')

@section('title', 'Suivi de ma demande')
@section('meta_description', 'Suivez l’état de votre demande d’inscription GreenPOS avec votre numéro de référence.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">Acquisition</p>
        <h1>Suivi de ma demande</h1>
        <p>Saisissez votre numéro de demande (exemple : REQ-{{ date('Ymd') }}-XXXX) pour consulter le statut.</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container" style="max-width:720px">
        @if($errors->any())
            <div class="site-alert site-alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register-company.track.lookup') }}" class="site-form" style="margin-bottom:2rem">
            @csrf
            <label class="site-label">Numéro de demande
                <input class="site-input" type="text" name="reference" value="{{ $reference }}" placeholder="REQ-20260805-XXXX" required autofocus style="font-family:ui-monospace,monospace;letter-spacing:.04em;text-transform:uppercase">
            </label>
            <button type="submit" class="site-btn site-btn-primary">Consulter</button>
        </form>

        @if($item)
            @php
                $tone = $message['tone'] ?? 'info';
                $badge = match($tone) {
                    'success' => 'background:rgba(15,143,98,.12);color:#0a5c42;border-color:rgba(15,143,98,.25)',
                    'danger' => 'background:#fff1f2;color:#9f1239;border-color:rgba(225,29,72,.25)',
                    'warn' => 'background:#fffbeb;color:#92400e;border-color:rgba(217,119,6,.3)',
                    default => 'background:var(--site-mist);color:var(--site-green-deep);border-color:rgba(15,143,98,.2)',
                };
            @endphp

            <div class="site-alert" style="{{ $badge }}">
                <strong style="display:block;font-size:1.05rem;margin-bottom:.35rem">{{ $message['title'] }}</strong>
                <span>{{ $message['body'] }}</span>
            </div>

            <div style="border:1px solid var(--site-line);border-radius:1.15rem;background:#fff;padding:1.35rem 1.4rem">
                <p style="margin:0 0 1rem;font-family:ui-monospace,monospace;font-weight:700;color:var(--site-green)">{{ $item->reference }}</p>
                <dl style="display:grid;gap:.85rem;margin:0">
                    <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--site-line);padding-bottom:.65rem">
                        <dt style="color:var(--site-muted)">Entreprise</dt>
                        <dd style="margin:0;font-weight:650;text-align:right">{{ $item->company_name }}</dd>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--site-line);padding-bottom:.65rem">
                        <dt style="color:var(--site-muted)">Responsable</dt>
                        <dd style="margin:0;font-weight:650;text-align:right">{{ $item->owner_name }}</dd>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--site-line);padding-bottom:.65rem">
                        <dt style="color:var(--site-muted)">Date</dt>
                        <dd style="margin:0;text-align:right">{{ $item->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--site-line);padding-bottom:.65rem">
                        <dt style="color:var(--site-muted)">Plan demandé</dt>
                        <dd style="margin:0;text-align:right">{{ $item->plan?->name ?? '—' }}</dd>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--site-line);padding-bottom:.65rem">
                        <dt style="color:var(--site-muted)">Statut</dt>
                        <dd style="margin:0;font-weight:700;text-align:right">{{ $item->statusLabel() }}</dd>
                    </div>
                    @if($item->rejection_reason)
                        <div>
                            <dt style="color:var(--site-muted);margin-bottom:.35rem">Commentaires</dt>
                            <dd style="margin:0;line-height:1.55">{{ $item->rejection_reason }}</dd>
                        </div>
                    @elseif($item->suspend_reason)
                        <div>
                            <dt style="color:var(--site-muted);margin-bottom:.35rem">Commentaires</dt>
                            <dd style="margin:0;line-height:1.55">{{ $item->suspend_reason }}</dd>
                        </div>
                    @else
                        <div>
                            <dt style="color:var(--site-muted);margin-bottom:.35rem">Commentaires</dt>
                            <dd style="margin:0;color:var(--site-muted)">Aucun commentaire pour le moment.</dd>
                        </div>
                    @endif
                </dl>

                <div class="site-cta-actions" style="justify-content:flex-start;margin-top:1.5rem">
                    @if($item->status === 'ACTIVE')
                        <a href="{{ route('login') }}" class="site-btn site-btn-accent">Se connecter à mon espace</a>
                    @elseif($item->status === 'REFUSEE' || $item->status === 'SUSPENDUE')
                        <a href="{{ route('site.contact') }}" class="site-btn site-btn-primary">Contacter GreenPOS</a>
                    @else
                        <a href="{{ route('site.home') }}" class="site-btn site-btn-ghost">Retour au site</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
