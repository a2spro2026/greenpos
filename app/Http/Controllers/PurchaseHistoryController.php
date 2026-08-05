<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderLog;
use App\Models\PurchaseReceipt;
use App\Models\Store;
use App\Models\Supplier;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();

        $logs = PurchaseOrderLog::query()
            ->forCompany($company->id)
            ->with(['order.supplier', 'receipt', 'user'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('message', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhereHas('order', fn ($o) => $o->where('number', 'like', $term));
                });
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $receipts = PurchaseReceipt::query()
            ->forCompany($company->id)
            ->with(['order.supplier', 'store'])
            ->latest()
            ->limit(10)
            ->get();

        return view('purchases.history', [
            'logs' => $logs,
            'receipts' => $receipts,
            'filters' => $request->only(['q', 'action', 'from', 'to']),
        ]);
    }
}
