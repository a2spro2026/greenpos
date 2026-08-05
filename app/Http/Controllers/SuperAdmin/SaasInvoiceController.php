<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Models\SaasSubscription;
use App\Services\SaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaasInvoiceController extends Controller
{
    public function __construct(private SaasBillingService $billing)
    {
    }

    public function index(Request $request): View
    {
        $invoices = SaasInvoice::query()
            ->with(['tenant', 'subscription.plan', 'payment'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.invoices.index', [
            'invoices' => $invoices,
            'statuses' => SaasInvoice::STATUSES,
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function show(SaasInvoice $invoice): View
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        return view('superadmin.invoices.show', compact('invoice'));
    }

    public function create(Request $request): View
    {
        return view('superadmin.invoices.create', [
            'subscriptions' => SaasSubscription::query()
                ->with(['tenant', 'plan'])
                ->whereIn('status', ['active', 'trialing', 'past_due'])
                ->latest()
                ->get(),
            'selected' => $request->integer('subscription_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saas_subscription_id' => ['required', 'exists:saas_subscriptions,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,issued'],
        ]);

        $sub = SaasSubscription::query()->findOrFail($data['saas_subscription_id']);
        $invoice = $this->billing->issueInvoice($sub, isset($data['amount']) ? (float) $data['amount'] : null, $data['status']);

        return redirect()->route('superadmin.invoices.show', $invoice)->with('success', 'Facture SaaS émise.');
    }

    public function print(SaasInvoice $invoice): View
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        return view('superadmin.invoices.print', compact('invoice'));
    }

    public function pdf(SaasInvoice $invoice): View
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        return view('superadmin.invoices.pdf', compact('invoice'));
    }

    public function download(SaasInvoice $invoice): StreamedResponse
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);
        $html = view('superadmin.invoices.pdf', compact('invoice'))->render();
        $filename = $invoice->number.'.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function pay(Request $request, SaasInvoice $invoice): RedirectResponse
    {
        $provider = $request->string('provider')->toString() ?: null;
        $this->billing->markInvoicePaid($invoice, $provider ?: null);

        return back()->with('success', 'Facture marquée payée.');
    }

    public function void(SaasInvoice $invoice): RedirectResponse
    {
        $this->billing->voidInvoice($invoice);

        return back()->with('success', 'Facture annulée.');
    }
}
