<button type="button" data-cat="" class="pos-cat shrink-0 rounded-full bg-emerald-500 px-4 py-2 text-left text-sm font-bold text-slate-950">Tous</button>
@foreach($categories as $cat)
    <button type="button" data-cat="{{ $cat->id }}" class="pos-cat shrink-0 rounded-full bg-white/5 px-4 py-2 text-left text-sm font-semibold text-slate-200 ring-1 ring-white/10 hover:bg-white/10">{{ $cat->name }}</button>
@endforeach
