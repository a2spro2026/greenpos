<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasAuditEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $events = SaasAuditEvent::query()
            ->with(['tenant', 'user'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest('occurred_at')
            ->paginate(40)
            ->withQueryString();

        return view('admin.journal.index', [
            'events' => $events,
            'filters' => $request->only(['category']),
        ]);
    }
}
