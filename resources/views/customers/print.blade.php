<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $customer->displayName() }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 32px; color: #0f172a; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #64748b; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
        .label { font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .actions { margin-bottom: 20px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / PDF</button>
        <a href="{{ route('customers.show', $customer) }}">Retour</a>
    </div>
    <h1>{{ $customer->displayName() }}</h1>
    <p class="muted">{{ $customer->code }} · {{ $customer->typeLabel() }} · {{ $customer->statusLabel() }} · {{ $workspaceCompany->name ?? 'GreenPOS' }}</p>
    <div class="grid">
        <div><div class="label">Nom</div>{{ $customer->name }}</div>
        <div><div class="label">Société</div>{{ $customer->company_name ?: '—' }}</div>
        <div><div class="label">Email</div>{{ $customer->email ?: '—' }}</div>
        <div><div class="label">Téléphone</div>{{ $customer->phone ?: '—' }}</div>
        <div><div class="label">Adresse</div>{{ $customer->address ?: '—' }}</div>
        <div><div class="label">Ville / Pays</div>{{ collect([$customer->city, $customer->country])->filter()->implode(', ') ?: '—' }}</div>
        <div><div class="label">Limite crédit</div>{{ number_format($customer->credit_limit, 2, ',', ' ') }} {{ $customer->currency }}</div>
        <div><div class="label">Solde</div>{{ number_format($customer->balance, 2, ',', ' ') }}</div>
    </div>
    @if($customer->contacts->isNotEmpty())
        <h2 style="margin-top:28px;font-size:16px">Contacts</h2>
        @foreach($customer->contacts as $contact)
            <p class="muted">{{ $contact->name }} — {{ $contact->role }} — {{ $contact->email }} — {{ $contact->phone }}</p>
        @endforeach
    @endif
</body>
</html>
