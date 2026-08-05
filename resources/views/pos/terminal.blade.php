<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal POS — GreenPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('gp-theme');
                if (t !== 'dark' && t !== 'light') t = 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .pos-grid { display: grid; grid-template-columns: 1fr; min-height: 100dvh; }
        @media (min-width: 1024px) { .pos-grid { grid-template-columns: minmax(0, 1.35fr) minmax(340px, 0.85fr); } }
        .pos-product { transition: transform .12s ease, box-shadow .12s ease; }
        .pos-product:active { transform: scale(.97); }
        .pos-toast { animation: pos-in .2s ease; }
        @keyframes pos-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .pos-modal-backdrop { animation: pos-fade .15s ease; }
        @keyframes pos-fade { from { opacity: 0; } to { opacity: 1; } }
        .pos-shell {
            background: #f8fafc;
            color: #0f172a;
            transition: background-color .25s ease, color .25s ease;
        }
        html.dark .pos-shell { background: #0f172a; color: #fff; }
        .pos-chrome {
            background: #fff;
            border-color: #e2e8f0;
            transition: background-color .25s ease, border-color .25s ease;
        }
        html.dark .pos-chrome { background: #111827; border-color: rgba(255,255,255,.1); }
    </style>
</head>
<body class="pos-shell antialiased" style="font-family:'Plus Jakarta Sans',system-ui,sans-serif">
@php
    $canHold = auth()->user()?->can('pos.hold');
@endphp

<div id="pos-app" class="pos-grid"
     data-catalog-url="{{ route('pos.catalog') }}"
     data-checkout-url="{{ route('pos.checkout') }}"
     data-hold-url="{{ route('pos.hold') }}"
     data-resume-base="{{ url('/pos/held') }}"
     data-csrf="{{ csrf_token() }}"
     data-session-open="{{ $session ? '1' : '0' }}"
     data-can-hold="{{ $canHold ? '1' : '0' }}">

    {{-- LEFT: catalog --}}
    <section class="flex min-h-0 flex-col border-b border-slate-200 dark:border-white/10 lg:border-b-0 lg:border-r">
        <header class="pos-chrome flex flex-wrap items-center gap-3 border-b px-4 py-3">
            <a href="{{ route('pos.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-100 px-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200 dark:bg-white/5 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10">
                ← POS
            </a>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold">Terminal caisse</p>
                <p class="truncate text-xs text-slate-400">
                    @if($session)
                        {{ $session->number }} · ouverte
                    @else
                        Aucune caisse ouverte
                    @endif
                </p>
            </div>
            @if(!$session)
                <a href="{{ route('pos.sessions.create') }}" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-400">Ouvrir caisse</a>
            @else
                <a href="{{ route('pos.sessions.close.form', $session) }}" class="rounded-xl bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:hover:bg-white/10">Clôturer</a>
            @endif
            <button type="button" id="gp-theme-toggle" data-theme-toggle class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200 dark:bg-white/5 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-white/10" aria-label="Changer le thème" title="Thème clair / sombre">
                <svg data-theme-icon="light" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg data-theme-icon="dark" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
            <kbd class="hidden rounded-lg bg-slate-100 px-2 py-1 text-[10px] text-slate-500 ring-1 ring-slate-200 sm:inline dark:bg-white/5 dark:text-slate-400 dark:ring-white/10">F2 recherche · F4 payer · Esc vider</kbd>
        </header>

        <div class="space-y-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-[#0b1220]">
            <div class="relative">
                <input id="pos-search" type="search" autocomplete="off" autofocus
                       placeholder="Recherche produit, SKU ou scan code-barres…"
                       class="h-14 w-full rounded-2xl border-0 bg-white pl-12 pr-4 text-base font-medium text-slate-900 outline-none ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-500/60 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-slate-500">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
            </div>
            <div class="flex gap-2 overflow-x-auto pb-1" id="pos-categories">
                <button type="button" data-cat="" class="pos-cat shrink-0 rounded-full bg-emerald-500 px-4 py-2 text-sm font-bold text-slate-950">Tous</button>
                @foreach($categories as $cat)
                    <button type="button" data-cat="{{ $cat->id }}" class="pos-cat shrink-0 rounded-full bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 ring-1 ring-white/10 hover:bg-white/10">{{ $cat->name }}</button>
                @endforeach
            </div>
            @if($favorites->isNotEmpty())
                <div>
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Favoris / récents</p>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        @foreach($favorites as $fav)
                            <button type="button"
                                    class="pos-fav shrink-0 rounded-xl bg-emerald-500/15 px-3 py-2 text-left text-sm font-semibold text-emerald-200 ring-1 ring-emerald-500/30 hover:bg-emerald-500/25"
                                    data-id="{{ $fav->id }}"
                                    data-name="{{ $fav->name }}"
                                    data-price="{{ $fav->sale_price }}"
                                    data-tax="{{ $fav->tax_rate }}"
                                    data-sku="{{ $fav->sku }}">
                                {{ \Illuminate\Support\Str::limit($fav->name, 22) }}
                                <span class="mt-0.5 block text-xs font-medium text-emerald-300/80">{{ number_format($fav->sale_price, 2, ',', ' ') }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div id="pos-products" class="flex-1 overflow-y-auto p-4">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5" id="pos-product-grid"></div>
            <p id="pos-empty" class="hidden py-16 text-center text-sm text-slate-500">Aucun produit trouvé.</p>
        </div>
    </section>

    {{-- RIGHT: cart --}}
    <aside class="flex min-h-[50dvh] flex-col bg-[#111827] lg:min-h-0">
        <div class="border-b border-white/10 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold">Panier</h2>
                <select id="pos-customer" class="max-w-[55%] rounded-xl border-0 bg-white/5 py-2 pl-3 pr-8 text-xs font-semibold text-slate-200 ring-1 ring-white/10 focus:ring-emerald-500/50">
                    <option value="">Client passage</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}{{ $c->company_name ? ' · '.$c->company_name : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="pos-cart-lines" class="flex-1 space-y-2 overflow-y-auto p-3">
            <p id="pos-cart-empty" class="py-10 text-center text-sm text-slate-500">Scannez ou touchez un produit.</p>
        </div>

        <div class="space-y-2 border-t border-white/10 px-4 py-3 text-sm">
            <label class="block">
                <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Notes</span>
                <input id="pos-notes" type="text" maxlength="500" placeholder="Note ticket…" class="h-10 w-full rounded-xl bg-white/5 px-3 text-sm ring-1 ring-white/10 placeholder:text-slate-500 focus:outline-none focus:ring-emerald-500/50">
            </label>
            <div class="flex justify-between text-slate-400"><span>Sous-total HT</span><span id="pos-subtotal">0,00</span></div>
            <div class="flex justify-between text-slate-400"><span>Remises</span><span id="pos-discount">0,00</span></div>
            <div class="flex justify-between text-slate-400"><span>TVA</span><span id="pos-tax">0,00</span></div>
            <div class="flex items-end justify-between pt-1">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total TTC</span>
                <span id="pos-total" class="text-3xl font-extrabold text-emerald-400">0,00</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 border-t border-white/10 p-3 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
            <button type="button" id="pos-clear" class="rounded-xl bg-white/5 py-3 text-sm font-bold ring-1 ring-white/10 hover:bg-rose-500/20">Vider</button>
            <button type="button" id="pos-hold" class="rounded-xl bg-amber-500/20 py-3 text-sm font-bold text-amber-200 ring-1 ring-amber-500/30 hover:bg-amber-500/30 {{ $canHold ? '' : 'opacity-40' }}" {{ $canHold ? '' : 'disabled' }}>Suspendre</button>
            <button type="button" id="pos-resume-btn" class="rounded-xl bg-sky-500/20 py-3 text-sm font-bold text-sky-200 ring-1 ring-sky-500/30 hover:bg-sky-500/30">Reprendre</button>
            <button type="button" id="pos-pay" class="rounded-xl bg-emerald-500 py-3 text-sm font-extrabold text-slate-950 hover:bg-emerald-400 sm:col-span-1 lg:col-span-2 xl:col-span-1">Payer (F4)</button>
        </div>
    </aside>
</div>

{{-- Held sales modal --}}
<div id="pos-held-modal" class="pos-modal-backdrop fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-md rounded-3xl bg-[#111827] p-5 shadow-2xl ring-1 ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold">Ventes suspendues</h3>
            <button type="button" data-close-held class="rounded-lg p-2 hover:bg-white/10">✕</button>
        </div>
        <ul class="max-h-80 space-y-2 overflow-y-auto">
            @forelse($held as $h)
                <li>
                    <button type="button" class="pos-resume-item flex w-full items-center justify-between rounded-2xl bg-white/5 px-4 py-3 text-left ring-1 ring-white/10 hover:bg-white/10" data-id="{{ $h->id }}">
                        <span>
                            <span class="block font-bold">{{ $h->number }}</span>
                            <span class="text-xs text-slate-400">{{ optional($h->held_at)->format('d/m H:i') }}</span>
                        </span>
                        <span class="font-bold text-emerald-400">{{ number_format($h->total_ttc, 2, ',', ' ') }}</span>
                    </button>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">Aucune vente suspendue.</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Payment modal --}}
<div id="pos-pay-modal" class="pos-modal-backdrop fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-lg rounded-3xl bg-[#111827] p-5 shadow-2xl ring-1 ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold">Paiement</h3>
            <button type="button" data-close-pay class="rounded-lg p-2 hover:bg-white/10">✕</button>
        </div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Total à encaisser</p>
        <p id="pay-total" class="mb-5 text-4xl font-extrabold text-emerald-400">0,00</p>

        <div class="mb-4 grid grid-cols-3 gap-2">
            <button type="button" data-method="cash" class="pay-method rounded-2xl bg-emerald-500 py-3 text-sm font-bold text-slate-950">Espèces</button>
            <button type="button" data-method="card" class="pay-method rounded-2xl bg-white/5 py-3 text-sm font-bold ring-1 ring-white/10">Carte</button>
            <button type="button" data-method="mobile" class="pay-method rounded-2xl bg-white/5 py-3 text-sm font-bold ring-1 ring-white/10">Mobile</button>
        </div>

        <div id="pay-cash-block" class="mb-4 space-y-3">
            <label class="block">
                <span class="mb-1 block text-xs font-bold text-slate-400">Montant reçu</span>
                <input id="pay-tendered" type="number" min="0" step="0.01" class="h-14 w-full rounded-2xl bg-white/5 px-4 text-2xl font-bold ring-1 ring-white/10 focus:outline-none focus:ring-emerald-500/50">
            </label>
            <div class="flex flex-wrap gap-2">
                @foreach([50, 100, 200, 500] as $q)
                    <button type="button" data-quick="{{ $q }}" class="rounded-xl bg-white/5 px-3 py-2 text-sm font-bold ring-1 ring-white/10 hover:bg-white/10">{{ $q }}</button>
                @endforeach
                <button type="button" data-quick="exact" class="rounded-xl bg-emerald-500/20 px-3 py-2 text-sm font-bold text-emerald-300 ring-1 ring-emerald-500/30">Exact</button>
            </div>
            <div class="flex justify-between rounded-2xl bg-white/5 px-4 py-3 text-sm">
                <span class="text-slate-400">Monnaie à rendre</span>
                <span id="pay-change" class="text-xl font-extrabold text-amber-300">0,00</span>
            </div>
        </div>

        <div id="pay-mixed-block" class="mb-4 hidden space-y-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Paiement mixte</p>
            <div class="grid grid-cols-3 gap-2">
                <label class="block"><span class="text-[11px] text-slate-400">Espèces</span><input id="mix-cash" type="number" min="0" step="0.01" value="0" class="mt-1 h-11 w-full rounded-xl bg-white/5 px-3 text-sm font-bold ring-1 ring-white/10"></label>
                <label class="block"><span class="text-[11px] text-slate-400">Carte</span><input id="mix-card" type="number" min="0" step="0.01" value="0" class="mt-1 h-11 w-full rounded-xl bg-white/5 px-3 text-sm font-bold ring-1 ring-white/10"></label>
                <label class="block"><span class="text-[11px] text-slate-400">Mobile</span><input id="mix-mobile" type="number" min="0" step="0.01" value="0" class="mt-1 h-11 w-full rounded-xl bg-white/5 px-3 text-sm font-bold ring-1 ring-white/10"></label>
            </div>
            <button type="button" id="pay-enable-mixed" class="text-xs font-semibold text-sky-300 hover:underline">Activer le mixte</button>
        </div>
        <button type="button" id="pay-toggle-mixed" class="mb-4 text-xs font-semibold text-sky-300 hover:underline">Paiement mixte…</button>

        <p id="pay-error" class="mb-3 hidden text-sm font-semibold text-rose-400"></p>
        <div class="flex gap-2">
            <button type="button" data-close-pay class="flex-1 rounded-2xl bg-white/5 py-4 text-sm font-bold ring-1 ring-white/10">Annuler</button>
            <button type="button" id="pay-confirm" class="flex-[1.4] rounded-2xl bg-emerald-500 py-4 text-sm font-extrabold text-slate-950 hover:bg-emerald-400">Valider la vente</button>
        </div>
    </div>
</div>

<div id="pos-toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[60] hidden -translate-x-1/2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-bold text-slate-950 shadow-xl pos-toast"></div>

<script>
(() => {
    const root = document.getElementById('pos-app');
    const cfg = {
        catalogUrl: root.dataset.catalogUrl,
        checkoutUrl: root.dataset.checkoutUrl,
        holdUrl: root.dataset.holdUrl,
        resumeBase: root.dataset.resumeBase,
        csrf: root.dataset.csrf,
        sessionOpen: root.dataset.sessionOpen === '1',
        canHold: root.dataset.canHold === '1',
    };

    const initialCatalog = @json($catalog);
    let products = initialCatalog.slice();
    let categoryId = '';
    let cart = [];
    let searchTimer = null;
    let payMethod = 'cash';
    let mixedMode = false;
    let heldSaleId = null;
    let barcodeBuffer = '';
    let barcodeTimer = null;

    const el = {
        search: document.getElementById('pos-search'),
        grid: document.getElementById('pos-product-grid'),
        empty: document.getElementById('pos-empty'),
        lines: document.getElementById('pos-cart-lines'),
        cartEmpty: document.getElementById('pos-cart-empty'),
        subtotal: document.getElementById('pos-subtotal'),
        discount: document.getElementById('pos-discount'),
        tax: document.getElementById('pos-tax'),
        total: document.getElementById('pos-total'),
        customer: document.getElementById('pos-customer'),
        notes: document.getElementById('pos-notes'),
        payModal: document.getElementById('pos-pay-modal'),
        heldModal: document.getElementById('pos-held-modal'),
        payTotal: document.getElementById('pay-total'),
        tendered: document.getElementById('pay-tendered'),
        change: document.getElementById('pay-change'),
        payError: document.getElementById('pay-error'),
        cashBlock: document.getElementById('pay-cash-block'),
        toast: document.getElementById('pos-toast'),
    };

    const money = (n) => Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(msg, ok = true) {
        el.toast.textContent = msg;
        el.toast.classList.toggle('bg-emerald-500', ok);
        el.toast.classList.toggle('bg-rose-500', !ok);
        el.toast.classList.toggle('text-white', !ok);
        el.toast.classList.toggle('text-slate-950', ok);
        el.toast.classList.remove('hidden');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => el.toast.classList.add('hidden'), 2200);
    }

    function totals() {
        let sub = 0, disc = 0, tax = 0;
        cart.forEach((l) => {
            const gross = l.qty * l.price;
            const d = gross * ((l.discount || 0) / 100);
            const net = gross - d;
            const t = net * ((l.tax || 0) / 100);
            sub += net; disc += d; tax += t;
        });
        return { subtotal: sub, discount: disc, tax, total: sub + tax };
    }

    function renderCart() {
        const t = totals();
        el.subtotal.textContent = money(t.subtotal);
        el.discount.textContent = money(t.discount);
        el.tax.textContent = money(t.tax);
        el.total.textContent = money(t.total);

        if (!cart.length) {
            el.lines.innerHTML = '<p id="pos-cart-empty" class="py-10 text-center text-sm text-slate-500">Scannez ou touchez un produit.</p>';
            return;
        }

        el.lines.innerHTML = cart.map((l, i) => `
            <div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">${escapeHtml(l.name)}</p>
                        <p class="text-xs text-slate-400">${money(l.price)} · TVA ${l.tax}%</p>
                    </div>
                    <button type="button" data-remove="${i}" class="rounded-lg px-2 py-1 text-rose-300 hover:bg-rose-500/20">✕</button>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <button type="button" data-dec="${i}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-lg font-bold">−</button>
                    <input data-qty="${i}" type="number" min="0.001" step="1" value="${l.qty}" class="h-10 w-16 rounded-xl bg-[#0b1220] text-center text-sm font-bold ring-1 ring-white/10">
                    <button type="button" data-inc="${i}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-lg font-bold">+</button>
                    <input data-disc="${i}" type="number" min="0" max="100" step="1" value="${l.discount || 0}" title="Remise %" class="ml-auto h-10 w-16 rounded-xl bg-[#0b1220] text-center text-xs font-bold ring-1 ring-white/10" placeholder="%">
                    <span class="min-w-[4.5rem] text-right text-sm font-extrabold text-emerald-300">${money(lineTotal(l))}</span>
                </div>
            </div>
        `).join('');
    }

    function lineTotal(l) {
        const gross = l.qty * l.price;
        const d = gross * ((l.discount || 0) / 100);
        const net = gross - d;
        return net + net * ((l.tax || 0) / 100);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function addProduct(p, qty = 1) {
        if (!cfg.sessionOpen) {
            toast('Ouvrez une caisse avant de vendre', false);
            return;
        }
        const existing = cart.find((l) => l.product_id === p.id);
        if (existing) {
            existing.qty += qty;
        } else {
            cart.push({
                product_id: p.id,
                name: p.name,
                sku: p.sku,
                price: Number(p.sale_price),
                tax: Number(p.tax_rate ?? 20),
                qty,
                discount: 0,
            });
        }
        renderCart();
        toast(p.name + ' ajouté');
    }

    function renderProducts(list) {
        if (!list.length) {
            el.grid.innerHTML = '';
            el.empty.classList.remove('hidden');
            return;
        }
        el.empty.classList.add('hidden');
        el.grid.innerHTML = list.map((p) => {
            const stock = p.track_stock
                ? (p.stock_qty === null ? '' : `<span class="text-[10px] ${p.stock_qty <= 0 ? 'text-rose-400' : 'text-slate-400'}">${p.stock_qty} en stock</span>`)
                : '<span class="text-[10px] text-slate-500">Service</span>';
            return `
                <button type="button" class="pos-product flex min-h-[110px] flex-col justify-between rounded-2xl bg-white/5 p-3 text-left ring-1 ring-white/10 hover:bg-emerald-500/15 hover:ring-emerald-500/40"
                        data-add='${JSON.stringify({ id: p.id, name: p.name, sale_price: p.sale_price, tax_rate: p.tax_rate, sku: p.sku }).replace(/'/g, '&#39;')}'>
                    <span class="line-clamp-2 text-sm font-bold leading-snug">${escapeHtml(p.name)}</span>
                    <span class="mt-2 flex items-end justify-between gap-1">
                        <span class="text-base font-extrabold text-emerald-400">${money(p.sale_price)}</span>
                        ${stock}
                    </span>
                </button>`;
        }).join('');
    }

    async function fetchCatalog(q = '') {
        const url = new URL(cfg.catalogUrl, window.location.origin);
        if (q) url.searchParams.set('q', q);
        if (categoryId) url.searchParams.set('category_id', categoryId);
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        products = data.products || [];
        renderProducts(products);
    }

    function payloadItems() {
        return cart.map((l) => ({
            product_id: l.product_id,
            quantity: l.qty,
            unit_price: l.price,
            discount_percent: l.discount || 0,
        }));
    }

    function openPay() {
        if (!cart.length) return toast('Panier vide', false);
        if (!cfg.sessionOpen) return toast('Ouvrez une caisse', false);
        const t = totals();
        el.payTotal.textContent = money(t.total);
        el.tendered.value = t.total.toFixed(2);
        el.change.textContent = '0,00';
        el.payError.classList.add('hidden');
        mixedMode = false;
        document.getElementById('pay-mixed-block').classList.add('hidden');
        el.cashBlock.classList.remove('hidden');
        setMethod('cash');
        el.payModal.classList.remove('hidden');
        el.payModal.classList.add('flex');
        el.tendered.focus();
        el.tendered.select();
    }

    function setMethod(m) {
        payMethod = m;
        document.querySelectorAll('.pay-method').forEach((btn) => {
            const on = btn.dataset.method === m;
            btn.classList.toggle('bg-emerald-500', on);
            btn.classList.toggle('text-slate-950', on);
            btn.classList.toggle('bg-white/5', !on);
            btn.classList.toggle('ring-1', !on);
            btn.classList.toggle('ring-white/10', !on);
        });
        el.cashBlock.classList.toggle('hidden', m !== 'cash' || mixedMode);
        if (m !== 'cash') {
            el.tendered.value = totals().total.toFixed(2);
            updateChange();
        }
    }

    function updateChange() {
        const total = totals().total;
        const tendered = Number(el.tendered.value || 0);
        el.change.textContent = money(Math.max(0, tendered - total));
    }

    async function confirmPay() {
        const total = totals().total;
        let payments = [];
        if (mixedMode) {
            const cash = Number(document.getElementById('mix-cash').value || 0);
            const card = Number(document.getElementById('mix-card').value || 0);
            const mobile = Number(document.getElementById('mix-mobile').value || 0);
            if (cash > 0) payments.push({ method: 'cash', amount: cash, tendered: cash });
            if (card > 0) payments.push({ method: 'card', amount: card });
            if (mobile > 0) payments.push({ method: 'mobile', amount: mobile });
        } else if (payMethod === 'cash') {
            const tendered = Number(el.tendered.value || 0);
            if (tendered + 0.001 < total) {
                el.payError.textContent = 'Montant reçu insuffisant.';
                el.payError.classList.remove('hidden');
                return;
            }
            payments = [{ method: 'cash', amount: total, tendered }];
        } else {
            payments = [{ method: payMethod, amount: total }];
        }

        const paid = payments.reduce((s, p) => s + Number(p.amount), 0);
        if (Math.round(paid * 100) < Math.round(total * 100)) {
            el.payError.textContent = 'Paiement insuffisant.';
            el.payError.classList.remove('hidden');
            return;
        }

        const body = {
            items: payloadItems(),
            payments,
            customer_id: el.customer.value || null,
            notes: el.notes.value || null,
            held_sale_id: heldSaleId,
        };

        document.getElementById('pay-confirm').disabled = true;
        try {
            const res = await fetch(cfg.checkoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Erreur paiement');
            }
            cart = [];
            renderCart();
            el.notes.value = '';
            heldSaleId = null;
            closePay();
            toast('Ticket ' + data.number + ' validé');
            if (data.receipt_url) {
                setTimeout(() => window.open(data.receipt_url, '_blank'), 300);
            }
            fetchCatalog(el.search.value.trim());
        } catch (e) {
            el.payError.textContent = e.message;
            el.payError.classList.remove('hidden');
        } finally {
            document.getElementById('pay-confirm').disabled = false;
        }
    }

    async function holdSale() {
        if (!cfg.canHold || !cart.length) return;
        try {
            const res = await fetch(cfg.holdUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    items: payloadItems(),
                    customer_id: el.customer.value || null,
                    notes: el.notes.value || null,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Impossible de suspendre');
            cart = [];
            renderCart();
            toast('Suspendue : ' + data.number);
            setTimeout(() => location.reload(), 600);
        } catch (e) {
            toast(e.message, false);
        }
    }

    async function resumeSale(id) {
        const res = await fetch(cfg.resumeBase + '/' + id, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) return toast('Reprise impossible', false);
        const items = (data.payload && data.payload.items) || [];
        cart = [];
        for (const it of items) {
            const p = products.find((x) => x.id === it.product_id) || {
                id: it.product_id,
                name: 'Produit #' + it.product_id,
                sale_price: it.unit_price || 0,
                tax_rate: 20,
            };
            cart.push({
                product_id: it.product_id,
                name: p.name,
                price: Number(it.unit_price ?? p.sale_price),
                tax: Number(p.tax_rate ?? 20),
                qty: Number(it.quantity),
                discount: Number(it.discount_percent || 0),
            });
        }
        if (data.payload?.customer_id) el.customer.value = String(data.payload.customer_id);
        if (data.payload?.notes) el.notes.value = data.payload.notes;
        heldSaleId = data.sale_id || id;
        renderCart();
        closeHeld();
        toast('Reprise ' + data.number);
    }

    function closePay() {
        el.payModal.classList.add('hidden');
        el.payModal.classList.remove('flex');
    }
    function closeHeld() {
        el.heldModal.classList.add('hidden');
        el.heldModal.classList.remove('flex');
    }

    // Events
    el.search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchCatalog(el.search.value.trim()), 120);
    });
    el.search.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = el.search.value.trim();
            if (!q) return;
            const exact = products.find((p) => p.barcode === q || p.sku === q);
            if (exact) {
                addProduct(exact);
                el.search.value = '';
                fetchCatalog('');
            } else {
                fetchCatalog(q).then(() => {
                    if (products.length === 1) {
                        addProduct(products[0]);
                        el.search.value = '';
                        fetchCatalog('');
                    }
                });
            }
        }
    });

    document.getElementById('pos-categories').addEventListener('click', (e) => {
        const btn = e.target.closest('.pos-cat');
        if (!btn) return;
        categoryId = btn.dataset.cat || '';
        document.querySelectorAll('.pos-cat').forEach((b) => {
            const on = b === btn;
            b.classList.toggle('bg-emerald-500', on);
            b.classList.toggle('text-slate-950', on);
            b.classList.toggle('bg-white/5', !on);
            b.classList.toggle('text-slate-200', !on);
        });
        fetchCatalog(el.search.value.trim());
    });

    el.grid.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-add]');
        if (!btn) return;
        addProduct(JSON.parse(btn.getAttribute('data-add')));
    });

    document.querySelectorAll('.pos-fav').forEach((btn) => {
        btn.addEventListener('click', () => addProduct({
            id: Number(btn.dataset.id),
            name: btn.dataset.name,
            sale_price: Number(btn.dataset.price),
            tax_rate: Number(btn.dataset.tax || 20),
            sku: btn.dataset.sku,
        }));
    });

    el.lines.addEventListener('click', (e) => {
        const rem = e.target.closest('[data-remove]');
        const inc = e.target.closest('[data-inc]');
        const dec = e.target.closest('[data-dec]');
        if (rem) { cart.splice(Number(rem.dataset.remove), 1); renderCart(); }
        if (inc) { cart[Number(inc.dataset.inc)].qty += 1; renderCart(); }
        if (dec) {
            const i = Number(dec.dataset.dec);
            cart[i].qty = Math.max(0.001, cart[i].qty - 1);
            if (cart[i].qty < 0.002) cart.splice(i, 1);
            renderCart();
        }
    });
    el.lines.addEventListener('change', (e) => {
        const q = e.target.closest('[data-qty]');
        const d = e.target.closest('[data-disc]');
        if (q) {
            const i = Number(q.dataset.qty);
            cart[i].qty = Math.max(0.001, Number(q.value) || 1);
            renderCart();
        }
        if (d) {
            const i = Number(d.dataset.disc);
            cart[i].discount = Math.min(100, Math.max(0, Number(d.value) || 0));
            renderCart();
        }
    });

    document.getElementById('pos-clear').addEventListener('click', () => {
        if (!cart.length || confirm('Vider le panier ?')) { cart = []; renderCart(); }
    });
    document.getElementById('pos-pay').addEventListener('click', openPay);
    document.getElementById('pos-hold').addEventListener('click', holdSale);
    document.getElementById('pos-resume-btn').addEventListener('click', () => {
        el.heldModal.classList.remove('hidden');
        el.heldModal.classList.add('flex');
    });
    document.querySelectorAll('[data-close-pay]').forEach((b) => b.addEventListener('click', closePay));
    document.querySelectorAll('[data-close-held]').forEach((b) => b.addEventListener('click', closeHeld));
    document.querySelectorAll('.pos-resume-item').forEach((b) => b.addEventListener('click', () => resumeSale(b.dataset.id)));
    document.querySelectorAll('.pay-method').forEach((b) => b.addEventListener('click', () => setMethod(b.dataset.method)));
    el.tendered.addEventListener('input', updateChange);
    document.querySelectorAll('[data-quick]').forEach((b) => b.addEventListener('click', () => {
        const v = b.dataset.quick;
        if (v === 'exact') el.tendered.value = totals().total.toFixed(2);
        else el.tendered.value = (Number(el.tendered.value || 0) + Number(v)).toFixed(2);
        updateChange();
    }));
    document.getElementById('pay-toggle-mixed').addEventListener('click', () => {
        mixedMode = !mixedMode;
        document.getElementById('pay-mixed-block').classList.toggle('hidden', !mixedMode);
        el.cashBlock.classList.toggle('hidden', mixedMode || payMethod !== 'cash');
        if (mixedMode) {
            const t = totals().total;
            document.getElementById('mix-cash').value = t.toFixed(2);
            document.getElementById('mix-card').value = '0';
            document.getElementById('mix-mobile').value = '0';
        }
    });
    document.getElementById('pay-confirm').addEventListener('click', confirmPay);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') { e.preventDefault(); el.search.focus(); el.search.select(); }
        if (e.key === 'F4') { e.preventDefault(); openPay(); }
        if (e.key === 'Escape') {
            if (!el.payModal.classList.contains('hidden')) closePay();
            else if (!el.heldModal.classList.contains('hidden')) closeHeld();
            else if (cart.length && confirm('Vider le panier ?')) { cart = []; renderCart(); }
        }
        if (e.key === 'Enter' && !el.payModal.classList.contains('hidden') && document.activeElement === el.tendered) {
            e.preventDefault();
            confirmPay();
        }
    });

    // Wedge barcode scanner: rapid keystrokes ending with Enter while not focused on search
    document.addEventListener('keypress', (e) => {
        if (document.activeElement === el.search || document.activeElement?.tagName === 'INPUT') return;
        if (e.key === 'Enter') {
            if (barcodeBuffer.length >= 3) {
                const code = barcodeBuffer;
                barcodeBuffer = '';
                fetchCatalog(code).then(() => {
                    const exact = products.find((p) => p.barcode === code || p.sku === code) || (products.length === 1 ? products[0] : null);
                    if (exact) addProduct(exact);
                    else toast('Code inconnu: ' + code, false);
                });
            }
            return;
        }
        if (e.key.length === 1) {
            barcodeBuffer += e.key;
            clearTimeout(barcodeTimer);
            barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 80);
        }
    });

    renderProducts(products);
    renderCart();
})();
</script>
</body>
</html>
