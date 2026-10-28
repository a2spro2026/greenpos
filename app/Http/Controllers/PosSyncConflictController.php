<?php

namespace App\Http\Controllers;

use App\Models\PosSyncConflict;
use App\Services\PosOfflineSyncService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosSyncConflictController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('pos.sell');
        $status = $request->string('status')->toString() ?: 'open';
        $conflicts = PosSyncConflict::query()->where('company_id', Workspace::company()->id)
            ->with(['user', 'store', 'sale'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()->paginate(30)->withQueryString();
        $openCount = PosSyncConflict::query()->where('company_id', Workspace::company()->id)->where('status', 'open')->count();

        return view('pos.sync-conflicts.index', compact('conflicts', 'status', 'openCount'));
    }

    public function retry(PosSyncConflict $conflict, PosOfflineSyncService $sync): RedirectResponse
    {
        $this->authorize('pos.sell');
        abort_unless($conflict->company_id === Workspace::company()->id, 404);
        $result = $sync->syncOne($conflict->payload);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok']
            ? 'Ticket synchronisé : '.$result['number']
            : 'Toujours refusé : '.($result['message'] ?? ''));
    }

    public function resolve(Request $request, PosSyncConflict $conflict): RedirectResponse
    {
        $this->authorize('pos.sell');
        abort_unless($conflict->company_id === Workspace::company()->id, 404);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $conflict->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'message' => trim($conflict->message.(! empty($data['note']) ? "\nClassé : ".$data['note'] : '')),
        ]);

        return back()->with('success', 'Conflit classé (le ticket d\'origine reste archivé).');
    }
}
