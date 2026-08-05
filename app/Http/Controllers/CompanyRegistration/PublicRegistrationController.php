<?php

namespace App\Http\Controllers\CompanyRegistration;

use App\Http\Controllers\Controller;
use App\Services\CompanyRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PublicRegistrationController extends Controller
{
    public function __construct(private CompanyRegistrationService $registrations)
    {
    }

    public function create(): View
    {
        return view('company-registration.wizard', [
            'plans' => $this->registrations->publicPlans(),
            'currencies' => ['MAD' => 'MAD — Dirham marocain', 'EUR' => 'EUR — Euro', 'USD' => 'USD — Dollar', 'XOF' => 'XOF — Franc CFA'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', 'string', 'max:64'],
            'owner_email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:64', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'activity' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'currency' => ['required', 'string', 'max:8'],
            'store_name' => ['required', 'string', 'max:255'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        try {
            $registration = $this->registrations->submit($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'owner_email' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('register-company.success')
            ->with('registration_reference', $registration->reference);
    }

    public function success(): View
    {
        return view('company-registration.success', [
            'reference' => session('registration_reference'),
        ]);
    }
}
