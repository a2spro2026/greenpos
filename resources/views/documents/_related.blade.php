{{-- Panel documents liés — usage: @include('documents._related', ['type' => 'customer', 'id' => $customer->id]) --}}
@php
    $relatedDocs = collect();
    try {
        if (!empty($type) && !empty($id) && \Illuminate\Support\Facades\Schema::hasTable('documents')) {
            $relatedDocs = app(\App\Services\DocumentService::class)->relatedFor($type, (int) $id);
        }
    } catch (\Throwable) {
        $relatedDocs = collect();
    }
@endphp

<article class="gp-card overflow-hidden p-0">
    <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Documents liés</h2>
        @can('documents.create')
            <a href="{{ route('documents.upload', ['documentable_type' => $type, 'documentable_id' => $id]) }}" class="text-xs font-semibold text-gp-primary hover:underline">Ajouter</a>
        @endcan
    </div>
    @if($relatedDocs->isEmpty())
        <p class="px-5 py-8 text-center text-sm text-gp-muted">Aucun document associé.</p>
    @else
        <ul class="divide-y divide-gp-border dark:divide-white/10">
            @foreach($relatedDocs as $doc)
                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <a href="{{ route('documents.show', $doc) }}" class="flex min-w-0 items-center gap-2 font-semibold hover:text-gp-primary">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold uppercase {{ $doc->iconColor() }}">{{ $doc->extension }}</span>
                        <span class="truncate">{{ $doc->name }}</span>
                    </a>
                    <span class="shrink-0 text-xs text-gp-muted">{{ $doc->humanSize() }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</article>
