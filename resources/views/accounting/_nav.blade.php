@php
$items = [
  ['route'=>'accounting.expenses.index','label'=>'Charges','match'=>'accounting.expenses.*'],
  ['route'=>'accounting.cash.index','label'=>'Caisse','match'=>'accounting.cash.*'],
  ['route'=>'accounting.accounts.index','label'=>'Comptes','match'=>'accounting.accounts.*'],
  ['route'=>'accounting.ledger.index','label'=>'Écritures','match'=>'accounting.ledger.*'],
  ['route'=>'accounting.bilan','label'=>'Bilan','match'=>'accounting.bilan'],
  ['route'=>'accounting.vat','label'=>'TVA','match'=>'accounting.vat'],
  ['route'=>'accounting.collections.index','label'=>'Encaissements','match'=>'accounting.collections.*'],
];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
@foreach($items as $item)
<a href="{{ route($item['route']) }}" class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">{{ $item['label'] }}</a>
@endforeach
</nav>
