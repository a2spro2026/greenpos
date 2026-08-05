@php
    $selected = $selected ?? [];
    $modules = $modules ?? [];
    $actions = $actions ?? [];
    $moduleLabels = $moduleLabels ?? [];
    $scopePermissions = $scopePermissions ?? collect();
    $extraByModule = $extraByModule ?? collect();
@endphp

<section class="gp-card mb-6 overflow-x-auto p-0">
    <div class="border-b border-gp-border px-5 py-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Matrice de permissions</h2>
        <p class="mt-1 text-xs text-gp-muted">Cochez les droits accordés à ce rôle pour chaque module.</p>
    </div>
    <table class="min-w-full text-sm">
        <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
            <tr>
                <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left dark:bg-[#121a18]">Module</th>
                @foreach($actions as $actionKey => $actionLabel)
                    <th class="px-2 py-3 text-center">{{ $actionLabel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gp-border dark:divide-white/10">
            @foreach($modules as $module => $moduleActions)
                <tr class="hover:bg-slate-50/60 dark:hover:bg-white/5">
                    <td class="sticky left-0 z-10 bg-white px-4 py-3 font-semibold dark:bg-[#0f1614]">{{ $moduleLabels[$module] ?? $module }}</td>
                    @foreach($actions as $actionKey => $actionLabel)
                        <td class="px-2 py-3 text-center">
                            @if(in_array($actionKey, $moduleActions, true))
                                @php $key = $module.'.'.$actionKey; @endphp
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                       class="h-4 w-4 rounded border-gp-border text-gp-primary focus:ring-gp-primary/30"
                                       {{ in_array($key, $selected, true) ? 'checked' : '' }}>
                            @else
                                <span class="text-gp-muted/40">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</section>

@if($extraByModule->isNotEmpty())
<section class="gp-card mb-6">
    <h2 class="mb-4 text-sm font-bold">Permissions avancées</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($extraByModule as $module => $perms)
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gp-muted">{{ $moduleLabels[$module] ?? $module }}</p>
                <div class="space-y-2">
                    @foreach($perms as $perm)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->key }}"
                                   class="h-4 w-4 rounded border-gp-border text-gp-primary"
                                   {{ in_array($perm->key, $selected, true) ? 'checked' : '' }}>
                            {{ $perm->label }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($scopePermissions->isNotEmpty())
<section class="gp-card mb-6">
    <h2 class="mb-4 text-sm font-bold">Permissions spéciales (périmètre)</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($scopePermissions as $perm)
            <label class="flex items-start gap-3 rounded-xl border border-gp-border p-3 text-sm dark:border-white/10">
                <input type="checkbox" name="permissions[]" value="{{ $perm->key }}"
                       class="mt-0.5 h-4 w-4 rounded border-gp-border text-gp-primary"
                       {{ in_array($perm->key, $selected, true) ? 'checked' : '' }}>
                <span>
                    <span class="font-semibold">{{ $perm->label }}</span>
                    @if($perm->description)
                        <span class="mt-0.5 block text-xs text-gp-muted">{{ $perm->description }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</section>
@endif

<div class="mb-4 flex flex-wrap gap-2">
    <button type="button" id="rbac-check-all" class="gp-btn-secondary text-xs">Tout cocher</button>
    <button type="button" id="rbac-uncheck-all" class="gp-btn-secondary text-xs">Tout décocher</button>
</div>
<script>
    document.getElementById('rbac-check-all')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(el => el.checked = true);
    });
    document.getElementById('rbac-uncheck-all')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(el => el.checked = false);
    });
</script>
