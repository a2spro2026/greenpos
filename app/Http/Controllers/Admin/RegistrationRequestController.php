<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyRegistrationRequest;
use App\Models\PlatformNotification;
use App\Services\CompanyRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationRequestController extends Controller
{
    public function __construct(private CompanyRegistrationService $registrations)
    {
    }

    public function index(Request $request): View
    {
        $items = CompanyRegistrationRequest::query()
            ->with(['plan', 'company'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('company_name', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhere('owner_email', 'like', $term)
                        ->orWhere('activity', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.registrations.index', [
            'items' => $items,
            'filters' => $request->only(['q', 'status']),
            'counts' => $this->registrations->statusCounts(),
        ]);
    }

    public function show(CompanyRegistrationRequest $registration): View
    {
        $registration->load(['plan', 'company', 'reviewer']);

        if (Schema::hasTable('platform_notifications')) {
            PlatformNotification::query()
                ->whereNull('read_at')
                ->where('type', 'registration')
                ->where(function ($q) use ($registration) {
                    $q->where('data->request_id', $registration->id)
                        ->orWhere('action_url', route('admin.registrations.show', $registration));
                })
                ->update(['read_at' => now()]);
        }

        return view('admin.registrations.show', [
            'item' => $registration,
        ]);
    }

    public function approve(Request $request, CompanyRegistrationRequest $registration): RedirectResponse
    {
        try {
            $this->registrations->approve($registration, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.registrations.show', $registration)
            ->with('success', 'Demande approuvée. Entreprise, boutique, administrateur et abonnement créés.');
    }

    public function reject(Request $request, CompanyRegistrationRequest $registration): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $this->registrations->reject($registration, $request->user(), $data['rejection_reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.registrations.show', $registration)
            ->with('success', 'Demande refusée. Un e-mail a été envoyé au client.');
    }

    public function suspend(Request $request, CompanyRegistrationRequest $registration): RedirectResponse
    {
        $data = $request->validate([
            'suspend_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->registrations->suspend($registration, $request->user(), $data['suspend_reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.registrations.show', $registration)
            ->with('success', 'Statut suspendu.');
    }
}
