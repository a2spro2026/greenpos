<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($channel ?? 'customer') === 'kitchen' ? 'Cuisine' : 'Ticket' }} {{ $sale->number }}</title>
    @php
        $channel = $channel ?? 'customer';
        $defaults = \App\Models\Printer::defaultConfigs();
        $ticket = array_merge($defaults['ticket_config'], $printer->ticket_config ?? []);
        $kitchen = array_merge($defaults['kitchen_config'], $printer->kitchen_config ?? []);
        $advanced = array_merge($defaults['advanced_config'], $printer->advanced_config ?? []);
        $isKitchen = $channel === 'kitchen';
        $showPrices = ! $isKitchen || ! empty($kitchen['show_prices']);
        $autoPrint = $isKitchen ? ! empty($kitchen['auto_print']) : ! empty($ticket['auto_print']);
        $header = $isKitchen ? ($kitchen['header'] ?? 'CUISINE') : ($ticket['header'] ?? '');
        $footer = $isKitchen ? '' : ($ticket['footer'] ?? 'Merci de votre visite');
        $width = (int) ($advanced['paper_width'] ?? 80);
        $maxWidth = $width <= 58 ? '220px' : '320px';
        $lines = $sale->lines;
        $grouped = $isKitchen && ! empty($kitchen['group_by_category']);
    @endphp
    <style>
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; max-width: {{ $maxWidth }}; margin: 0 auto; padding: 16px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
        .muted { color: #666; font-size: 11px; text-align: center; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        td { padding: 4px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals td { border-top: 1px dashed #999; padding-top: 6px; }
        .total { font-weight: 700; font-size: 14px; }
        .pay { margin-top: 12px; font-size: 12px; }
        .code { margin: 8px auto; width: 72px; height: 72px; border: 2px solid #111; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }
        @media print { button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="margin-bottom:12px;padding:8px 12px;cursor:pointer">Imprimer</button>
    @if($header)
        <p class="muted" style="font-weight:700;letter-spacing:.08em">{{ $header }}</p>
    @endif
    @if(! $isKitchen && ! empty($ticket['show_logo']))
        <h1>{{ $sale->store?->name ?? $sale->company?->name ?? 'GreenPOS' }}</h1>
    @else
        <h1>{{ $isKitchen ? ($header ?: 'CUISINE') : ($sale->store?->name ?? 'GreenPOS') }}</h1>
    @endif
    <p class="muted">{{ $sale->number }} · {{ optional($sale->completed_at)->format('d/m/Y H:i') }}</p>
    @if($sale->ticket_name)
        <p class="muted">{{ $sale->ticket_name }}@if($sale->service_mode) · {{ $sale->service_mode }}@endif</p>
    @endif
    @if(! $isKitchen && ! empty($ticket['show_ice']) && $sale->company?->ice)
        <p class="muted">ICE {{ $sale->company->ice }}</p>
    @endif
    <p class="muted">Caissier : {{ $sale->cashier?->name ?? '—' }}@if($sale->customer) · {{ $sale->customer->name }}@endif</p>
    @if(! $isKitchen && ! empty($ticket['show_qr']))
        <div class="code">{{ $sale->number }}</div>
    @endif

    @if($grouped)
        @foreach($lines->groupBy(fn ($line) => $line->product?->category?->name ?? 'Autres') as $category => $group)
            <p style="margin:10px 0 4px;font-size:11px;font-weight:700">{{ $category }}</p>
            <table>
                @foreach($group as $line)
                    <tr>
                        <td>
                            {{ $line->product_name }}<br>
                            <span style="color:#666">{{ number_format($line->quantity, 3, ',', ' ') }}@if($showPrices) × {{ number_format($line->unit_price, 2, ',', ' ') }}@endif</span>
                        </td>
                        @if($showPrices)
                            <td class="right">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @endforeach
    @else
        <table>
            @foreach($lines as $line)
                <tr>
                    <td>
                        {{ $line->product_name }}<br>
                        <span style="color:#666">{{ number_format($line->quantity, 3, ',', ' ') }}@if($showPrices) × {{ number_format($line->unit_price, 2, ',', ' ') }}@endif</span>
                    </td>
                    @if($showPrices)
                        <td class="right">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                    @endif
                </tr>
            @endforeach
            @if($showPrices)
                <tr class="totals"><td>HT</td><td class="right">{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</td></tr>
                <tr><td>TVA</td><td class="right">{{ number_format($sale->tax_total, 2, ',', ' ') }}</td></tr>
                <tr><td class="total">TOTAL TTC</td><td class="right total">{{ number_format($sale->total_ttc, 2, ',', ' ') }} {{ $sale->currency }}</td></tr>
            @endif
        </table>
    @endif

    @if($showPrices)
        <div class="pay">
            @foreach($sale->payments as $pay)
                <div>{{ $pay->methodLabel() }} : {{ number_format($pay->amount, 2, ',', ' ') }}@if($pay->is_deferred) (différé)@endif</div>
                @if($pay->method === 'cash' && $pay->change_amount > 0)
                    <div>Rendu : {{ number_format($pay->change_amount, 2, ',', ' ') }}</div>
                @endif
            @endforeach
        </div>
    @endif
    @if(! empty($advanced['open_drawer']) && ! $isKitchen)
        <p class="muted">Tiroir caisse</p>
    @endif
    @if($sale->notes)
        <p class="muted" style="margin-top:12px;text-align:left">Note : {{ $sale->notes }}</p>
    @endif
    @if($footer)
        <p class="muted" style="margin-top:16px">{{ $footer }}</p>
    @endif
    @if($autoPrint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
    @endif
</body>
</html>
