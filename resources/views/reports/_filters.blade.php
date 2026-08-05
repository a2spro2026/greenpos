<form method="GET" action="{{ $action ?? request()->url() }}" class="mb-6 gp-card">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
        <div>
            <label class="gp-label text-xs">Du</label>
            <input type="date" name="from" value="{{ ($filters['from'] ?? now()->startOfMonth())->format('Y-m-d') }}" class="gp-input w-full">
        </div>
        <div>
            <label class="gp-label text-xs">Au</label>
            <input type="date" name="to" value="{{ ($filters['to'] ?? now())->format('Y-m-d') }}" class="gp-input w-full">
        </div>
        <div>
            <label class="gp-label text-xs">Boutique</label>
            <select name="store_id" class="gp-select w-full">
                <option value="">Toutes</option>
                @foreach($options['stores'] as $st)
                    <option value="{{ $st->id }}" {{ ($filters['store_id'] ?? '') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label text-xs">Utilisateur</label>
            <select name="user_id" class="gp-select w-full">
                <option value="">Tous</option>
                @foreach($options['users'] as $u)
                    <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label text-xs">Catégorie</label>
            <select name="category_id" class="gp-select w-full">
                <option value="">Toutes</option>
                @foreach($options['categories'] as $c)
                    <option value="{{ $c->id }}" {{ ($filters['category_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label text-xs">Produit</label>
            <select name="product_id" class="gp-select w-full">
                <option value="">Tous</option>
                @foreach($options['products'] as $p)
                    <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label text-xs">Client</label>
            <select name="customer_id" class="gp-select w-full">
                <option value="">Tous</option>
                @foreach($options['customers'] as $c)
                    <option value="{{ $c->id }}" {{ ($filters['customer_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2">
            <button class="gp-btn-primary">Appliquer</button>
            <a href="{{ $action ?? request()->url() }}" class="gp-btn-secondary">Réinitialiser</a>
        </div>
    </div>
</form>
