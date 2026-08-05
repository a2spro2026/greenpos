<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $sale->number }}</title>
    <style>
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; max-width: 320px; margin: 0 auto; padding: 16px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
        .muted { color: #666; font-size: 11px; text-align: center; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        td { padding: 4px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals td { border-top: 1px dashed #999; padding-top: 6px; }
        .total { font-weight: 700; font-size: 14px; }
        .pay { margin-top: 12px; font-size: 12px; }
        @media print { button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="margin-bottom:12px;padding:8px 12px;cursor:pointer">Imprimer</button>
    <h1>{{ $sale->store?->name ?? 'GreenPOS' }}</h1>
    <p class="muted">{{ $sale->number }} · {{ optional($sale->completed_at)->format('d/m/Y H:i') }}</p>
    <p class="muted">Caissier : {{ $sale->cashier?->name ?? '—' }}@if($sale->customer) · {{ $sale->customer->name }}@endif</p>

    <table>
        @foreach($sale->lines as $line)
            <tr>
                <td>
                    {{ $line->product_name }}<br>
                    <span style="color:#666">{{ number_format($line->quantity, 3, ',', ' ') }} × {{ number_format($line->unit_price, 2, ',', ' ') }}</span>
                </td>
                <td class="right">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        <tr class="totals"><td>HT</td><td class="right">{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</td></tr>
        <tr><td>TVA</td><td class="right">{{ number_format($sale->tax_total, 2, ',', ' ') }}</td></tr>
        <tr><td class="total">TOTAL TTC</td><td class="right total">{{ number_format($sale->total_ttc, 2, ',', ' ') }} {{ $sale->currency }}</td></tr>
    </table>

    <div class="pay">
        @foreach($sale->payments as $pay)
            <div>{{ $pay->methodLabel() }} : {{ number_format($pay->amount, 2, ',', ' ') }}</div>
            @if($pay->change_amount > 0)
                <div>Rendu : {{ number_format($pay->change_amount, 2, ',', ' ') }}</div>
            @endif
        @endforeach
    </div>
    @if($sale->notes)
        <p class="muted" style="margin-top:12px;text-align:left">Note : {{ $sale->notes }}</p>
    @endif
    <p class="muted" style="margin-top:16px">Merci de votre visite</p>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
</body>
</html>
