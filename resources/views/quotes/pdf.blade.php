<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quote->number }} — Devis</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', ui-sans-serif, system-ui, sans-serif; color: #0f172a; margin: 0; background: #f1f5f9; }
        .page { max-width: 820px; margin: 24px auto; background: #fff; box-shadow: 0 10px 40px rgba(15,23,42,.08); }
        .inner { padding: 40px 48px; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 3px solid #0ea5e9; padding-bottom: 24px; }
        .logo { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg,#38bdf8,#0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; }
        .brand h1 { margin: 0; font-size: 20px; }
        .brand p { margin: 4px 0 0; color: #64748b; font-size: 12px; line-height: 1.5; }
        .doc-meta { text-align: right; }
        .doc-meta h2 { margin: 0; font-size: 26px; color: #0284c7; }
        .doc-meta p { margin: 4px 0; font-size: 13px; color: #64748b; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 28px; }
        .box { background: #f8fafc; border-radius: 12px; padding: 16px 18px; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .box p { margin: 2px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 28px; font-size: 13px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; padding: 10px 8px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .right { text-align: right; }
        .footer { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0; }
        .totals { width: 280px; margin-left: auto; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals .grand { font-size: 18px; font-weight: 800; color: #0284c7; border-top: 2px solid #0284c7; margin-top: 8px; padding-top: 10px; }
        .qr { text-align: center; }
        .qr img { width: 110px; height: 110px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; }
        .qr p { font-size: 10px; color: #94a3b8; margin-top: 6px; }
        .signature { margin-top: 40px; text-align: right; }
        .signature .line { width: 200px; border-top: 1px solid #cbd5e1; margin-left: auto; padding-top: 8px; font-size: 11px; color: #64748b; }
        .toolbar { max-width: 820px; margin: 16px auto; display: flex; gap: 8px; }
        .toolbar button, .toolbar a { padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #0284c7; color: #fff; }
        .btn-secondary { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; }
        @media print { body { background: #fff; } .toolbar { display: none; } .page { margin: 0; box-shadow: none; } .inner { padding: 24px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-primary" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        @if(auth()->check())
            <a class="btn-secondary" href="{{ route('quotes.show', $quote) }}">Retour</a>
        @endif
    </div>
    <div class="page">
        <div class="inner">
            <header class="header">
                <div style="display:flex;gap:14px;align-items:flex-start">
                    <div class="logo">G</div>
                    <div class="brand">
                        <h1>{{ $workspaceCompany->name ?? 'GreenPOS' }}</h1>
                        <p>{{ $quote->store?->name }}<br>{{ $quote->store?->city ?? 'Maroc' }}</p>
                    </div>
                </div>
                <div class="doc-meta">
                    <h2>DEVIS</h2>
                    <p><strong>{{ $quote->number }}</strong></p>
                    <p>Date : {{ optional($quote->quoted_at)->format('d/m/Y') }}</p>
                    <p>Validité : {{ optional($quote->valid_until)->format('d/m/Y') ?: '—' }}</p>
                    <p class="badge">{{ $quote->statusLabel() }}</p>
                </div>
            </header>
            <div class="grid">
                <div class="box">
                    <h3>Destinataire</h3>
                    <p><strong>{{ $quote->customer?->displayName() }}</strong></p>
                    <p>{{ $quote->customer?->address }}</p>
                    <p>{{ $quote->customer?->email }}</p>
                    @if($quote->customer?->tax_id)<p>ICE : {{ $quote->customer->tax_id }}</p>@endif
                </div>
                <div class="box">
                    <h3>Informations</h3>
                    <p>Commercial : {{ $quote->salesperson?->name ?? '—' }}</p>
                    <p>Devise : {{ $quote->currency }}</p>
                    <p>Conditions : {{ $quote->terms ?: '—' }}</p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="right">Qté</th>
                        <th class="right">P.U. HT</th>
                        <th class="right">Rem.</th>
                        <th class="right">TVA</th>
                        <th class="right">Total TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->lines as $line)
                        <tr>
                            <td><strong>{{ $line->product_name }}</strong>@if($line->sku)<br><span style="color:#94a3b8;font-size:11px">{{ $line->sku }}</span>@endif</td>
                            <td class="right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                            <td class="right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                            <td class="right">{{ number_format($line->discount_percent, 0) }}%</td>
                            <td class="right">{{ number_format($line->tax_rate, 0) }}%</td>
                            <td class="right"><strong>{{ number_format($line->line_total, 2, ',', ' ') }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="footer">
                <div class="qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data={{ urlencode($quote->publicUrl()) }}" alt="QR">
                    <p>Scanner pour consulter</p>
                </div>
                <div class="totals">
                    <div><span>Total HT</span><span>{{ number_format($quote->subtotal_ht, 2, ',', ' ') }}</span></div>
                    <div><span>Remises</span><span>{{ number_format($quote->discount_total, 2, ',', ' ') }}</span></div>
                    <div><span>TVA</span><span>{{ number_format($quote->tax_total, 2, ',', ' ') }}</span></div>
                    <div class="grand"><span>Total TTC</span><span>{{ number_format($quote->total_ttc, 2, ',', ' ') }} {{ $quote->currency }}</span></div>
                </div>
            </div>
            @if($quote->customer_notes)<p style="margin-top:20px;font-size:12px;color:#64748b"><strong>Note :</strong> {{ $quote->customer_notes }}</p>@endif
            <div class="signature"><div class="line">Bon pour accord — Signature client</div></div>
            <p style="margin-top:32px;font-size:11px;color:#94a3b8;text-align:center">Devis généré par GreenPOS · {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
