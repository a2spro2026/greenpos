<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\OnboardingService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private OnboardingService $onboarding)
    {
    }

    public function landing(): View
    {
        $plans = $this->onboarding->publicPlans();

        return view('onboarding.landing', compact('plans'));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($this->onboarding->needsPlan($user)) {
                return redirect()->route('onboarding.plan');
            }
            if ($this->onboarding->needsWizard($user)) {
                return redirect()->route('onboarding.wizard');
            }

            return redirect()->route('home');
        }

        return view('onboarding.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Vous devez accepter les conditions d’utilisation.',
        ]);

        $user = $this->onboarding->registerAccount($data);
        $this->onboarding->loginAfterRegister($user, $request);

        return redirect()->route('onboarding.plan');
    }

    public function showPlan(): View|RedirectResponse
    {
        $user = auth()->user();
        $row = $this->onboarding->currentFor($user);

        if (! $row || $row->status !== 'registered') {
            if ($row && $row->needsWizard()) {
                return redirect()->route('onboarding.wizard');
            }

            return redirect()->route('home');
        }

        $plans = $this->onboarding->publicPlans();
        $draft = $row->draft ?? [];

        return view('onboarding.plan', compact('plans', 'draft', 'row'));
    }

    public function selectPlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_mode' => ['required', 'in:trial,subscribe'],
        ]);

        $plan = SaasPlan::query()->findOrFail($data['saas_plan_id']);
        $this->onboarding->provision(auth()->user(), $plan, $data['billing_mode']);

        return redirect()->route('onboarding.wizard');
    }

    public function wizard(): View|RedirectResponse
    {
        $row = $this->onboarding->currentFor(auth()->user());
        if (! $row || ! $row->needsWizard()) {
            return redirect()->route('home');
        }

        $company = $row->company ?? Workspace::company();

        return view('onboarding.wizard', [
            'onboarding' => $row,
            'company' => $company,
            'store' => $company?->stores()->orderByDesc('is_default')->first(),
            'plan' => $row->plan,
        ]);
    }

    public function saveWizard(Request $request): RedirectResponse
    {
        $row = $this->onboarding->currentFor(auth()->user());
        if (! $row || ! $row->needsWizard()) {
            return redirect()->route('home');
        }

        $data = $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'category_name' => ['required', 'string', 'max:120'],
            'product_name' => ['nullable', 'string', 'max:160'],
            'product_price' => ['nullable', 'numeric', 'min:0'],
            'register_name' => ['required', 'string', 'max:120'],
            'employee_name' => ['nullable', 'string', 'max:120'],
            'employee_email' => ['nullable', 'email', 'max:160'],
            'employee_role' => ['nullable', 'in:cashier,sales,manager,employee'],
        ]);

        $this->onboarding->saveWizard($row, $data, $request->file('logo'));
        $this->onboarding->complete($row->fresh());

        return redirect()->route('home')->with('welcome_onboarding', true);
    }

    public function skipWizard(): RedirectResponse
    {
        $row = $this->onboarding->currentFor(auth()->user());
        if ($row && $row->needsWizard()) {
            $this->onboarding->complete($row);
        }

        return redirect()->route('home')->with('welcome_onboarding', true);
    }

    public function dismissWelcome(): RedirectResponse
    {
        $row = $this->onboarding->currentFor(auth()->user());
        if ($row) {
            $this->onboarding->markWelcomeShown($row);
        }

        return back();
    }
}
