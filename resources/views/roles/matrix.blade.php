@extends('layouts.app')

@section('title', 'Matrice des permissions')
@section('breadcrumb', 'Administration / Rôles / Matrice')
@section('heading', 'Matrice RBAC')
@section('subtitle', 'Vue consolidée des permissions par rôle et par module.')

@section('actions')
    @can('roles.create')
        <a href="{{ route('roles.create') }}" class="gp-btn-primary">Nouveau rôle</a>
    @endcan
@endsection

@section('content')
    @include('roles._nav')

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($roles as $role)
            <a href="{{ route('roles.show', $role) }}" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $role->colorClass() }} hover:opacity-80">{{ $role->name }}</a>
        @endforeach
    </div>

    <section class="gp-card overflow-x-auto p-0">
        <table class="min-w-full text-xs">
            <thead class="border-b border-gp-border bg-slate-50 dark:border-white/10 dark:bg-white/5">
                <tr>
                    <th class="sticky left-0 z-10 bg-slate-50 px-3 py-3 text-left dark:bg-[#121a18]">Module / Action</th>
                    @foreach($roles as $role)
                        <th class="min-w-[72px] px-2 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 py-0.5 font-semibold {{ $role->colorClass() }}">{{ \Illuminate\Support\Str::limit($role->name, 10, '') }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($modules as $module => $moduleActions)
                    <tr class="bg-slate-50/80 dark:bg-white/5">
                        <td colspan="{{ 1 + $roles->count() }}" class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-gp-muted">
                            {{ \App\Support\PermissionCatalog::MODULES[$module] ?? $module }}
                        </td>
                    </tr>
                    @foreach($moduleActions as $action)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-white/[0.03]">
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium dark:bg-[#0f1614]">{{ $actions[$action] ?? $action }}</td>
                            @foreach($roles as $role)
                                @php $key = $module.'.'.$action; $ok = $role->is_super || $role->hasPermission($key); @endphp
                                <td class="px-2 py-2 text-center">
                                    @if($ok)
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700">✓</span>
                                    @else
                                        <span class="text-slate-300">·</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
