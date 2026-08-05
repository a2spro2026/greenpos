<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    @php
        $brand = $workspaceBranding ?? app(\App\Services\BrandingService::class)->forCompany($invoice->company ?? $workspaceCompany ?? null);
        $brandSvc = app(\App\Services\BrandingService::class);
        $color = $brand['invoice_primary_color'] ?? $brand['primary_color'] ?? '#0f766e';
        $logo = $brandSvc->assetUrl($brand['invoice_logo_path'] ?? null)
            ?: $brandSvc->assetUrl($brand['logo_path'] ?? null)
            ?: ($workspaceCompany?->logoUrl());
        $sig = $brandSvc->assetUrl($brand['invoice_signature_path'] ?? null);
        $stamp = $brandSvc->assetUrl($brand['invoice_stamp_path'] ?? null);
        $trade = $brand['trade_name'] ?? ($workspaceCompany->name ?? 'GreenPOS');
    @endphp
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #0f172a; margin: 32px; }
        h1 { margin: 0 0 4px; font-size: 22px; color: {{ $color }}; }
        .muted { color: #64748b; font-size: 13px; }
        .header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 20px; }
        .logo { max-height: 56px; max-width: 180px; object-fit: contain; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 13px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid {{ $color }}; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 280px; margin-left: auto; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { color: {{ $color }}; font-size: 15px; }
        .actions { margin-bottom: 20px; }
        .signs { display: flex; gap: 40px; margin-top: 36px; align-items: flex-end; }
        .signs img { max-height: 64px; max-width: 140px; object-fit: contain; }
        .legal { margin-top: 28px; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        @media print { .actions { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / Enregistrer PDF</button>
        <a href="{{ route('invoices.show', $invoice) }}">Retour</a>
    </div>
    <div class="header">
        <div>
            @if($logo)<img src="{{ $logo }}" alt="" class="logo"><br>@endif
            <strong>{{ $trade }}</strong>
            @if(!empty($brand['invoice_header']))
                <p class="muted">{!! nl2br(e($brand['invoice_header'])) !!}</p>
            @endif
        </div>
        <div style="text-align:right">
            <h1>{{ $invoice->typeLabel() }} {{ $invoice->number }}</h1>
            <p class="muted">{{ optional($invoice->invoiced_at)->format($brand['date_format'] ?? 'd/m/Y') }} · {{ $invoice->statusLabel() }}</p>
        </div>
    </div>
    <p><strong>Client :</strong> {{ $invoice->customer?->displayName() }}<br>
       <strong>Boutique :</strong> {{ $invoice->store?->name }}<br>
       <strong>Échéance :</strong> {{ optional($invoice->due_at)->format($brand['date_format'] ?? 'd/m/Y') ?: '—' }}</p>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Qté</th>
                <th class="right">Prix</th>
                <th class="right">Remise</th>
                <th class="right">TVA</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->product_name }} <span class="muted">{{ $line->sku }}</span></td>
                    <td class="right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                    <td class="right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($line->discount_percent, 1, ',', ' ') }}%</td>
                    <td class="right">{{ number_format($line->tax_rate, 1, ',', ' ') }}%</td>
                    <td class="right">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div><span>Total HT</span><strong>{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }}</strong></div>
        <div><span>TVA</span><strong>{{ number_format($invoice->tax_total, 2, ',', ' ') }}</strong></div>
        <div class="grand"><span>Total TTC</span><strong>{{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}</strong></div>
        @if($invoice->amount_paid > 0)
            <div><span>Payé</span><strong>{{ number_format($invoice->amount_paid, 2, ',', ' ') }}</strong></div>
            <div><span>Reste</span><strong>{{ number_format($invoice->balance_due, 2, ',', ' ') }}</strong></div>
        @endif
    </div>
    @if($invoice->payment_terms)
        <p class="muted" style="margin-top:24px">Conditions : {{ $invoice->payment_terms }}</p>
    @endif
    @if($invoice->customer_notes)
        <p class="muted">Note : {{ $invoice->customer_notes }}</p>
    @endif
    @if(!empty($brand['invoice_footer']))
        <p class="muted" style="margin-top:16px">{!! nl2br(e($brand['invoice_footer'])) !!}</p>
    @endif
    @if($sig || $stamp)
        <div class="signs">
            @if($sig)<div><img src="{{ $sig }}" alt="Signature"><div class="muted">Signature</div></div>@endif
            @if($stamp)<div><img src="{{ $stamp }}" alt="Cachet"><div class="muted">Cachet</div></div>@endif
        </div>
    @endif
    @if(!empty($brand['invoice_legal']))
        <div class="legal">{!! nl2br(e($brand['invoice_legal'])) !!}</div>
    @endif
</body>
</html>
