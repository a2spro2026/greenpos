@include('reports._period')
<form method="get" class="gp-card mb-4 grid gap-3 sm:grid-cols-5">
    <label class="block text-xs"><span class="gp-label">Du</span><input type="date" name="from" value="{{ $filters['from']->toDateString() }}" class="gp-input"></label>
    <label class="block text-xs"><span class="gp-label">Au</span><input type="date" name="to" value="{{ $filters['to']->toDateString() }}" class="gp-input"></label>
    <label class="block text-xs"><span class="gp-label">Boutique</span>
        <select name="store_id" class="gp-select w-full">
            @if(\App\Support\Workspace::canAccessAllStores())
                <option value="all" @selected($filters['store_id'] === null)>Toutes (consolidé)</option>
            @endif
            @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected($filters['store_id'] == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </label>
    @isset($withUser)
        <label class="block text-xs"><span class="gp-label">Collaborateur</span>
            <select name="user_id" class="gp-select w-full">
                <option value="">Tous</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected($filters['user_id'] == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </label>
    @else
        <div></div>
    @endisset
    <div class="flex items-end"><button class="gp-btn-primary w-full">Filtrer</button></div>
</form>
