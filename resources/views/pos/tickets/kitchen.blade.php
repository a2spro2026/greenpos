<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Cuisine {{ $sale->number }}</title>
<style>body{font-family:ui-monospace,monospace;padding:16px} h1{font-size:18px;margin:0 0 8px} .meta{font-size:12px;color:#444;margin-bottom:12px} li{margin:6px 0;font-size:14px}</style>
</head><body onload="window.print()">
<h1>TICKET CUISINE</h1>
<div class="meta">{{ $sale->number }} · {{ $sale->service_mode }} · {{ $sale->ticket_name }} · {{ now()->format('d/m H:i') }}</div>
@if($sale->notes)<p><strong>Notes:</strong> {{ $sale->notes }}</p>@endif
<ul>@foreach($sale->lines as $line)<li>{{ $line->quantity }} × {{ $line->product_name ?? $line->description }}</li>@endforeach</ul>
</body></html>
