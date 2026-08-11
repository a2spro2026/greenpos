<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaasLicense;
use App\Models\SaasOnboarding;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Support\SessionManager;
use App\Support\SettingsDefaults;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    public function __construct(private SaasService $saas)
    {
    }

    public function publicPlans()
    {
        $this->saas->ensurePlans();

        return SaasPlan::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_public', true)->orWhereNull('is_public');
            })
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();
    }

    public function registerAccount(array $data): User
    {
        if (User::query()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Un compte existe déjà avec cet e-mail. Connectez-vous.',
            ]);
        }

        $name = trim($data['full_name']);
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        $user = User::query()->create([
            'name' => $name,
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        SaasOnboarding::query()->create([
            'user_id' => $user->id,
            'status' => 'registered',
            'draft' => [
                'company_name' => $data['company_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'terms_accepted' => true,
            ],
            'checklist' => $this->defaultChecklist(),
        ]);

        return $user;
    }

    public function currentFor(?User $user = null): ?SaasOnboarding
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        return SaasOnboarding::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
    }

    public function needsPlan(User $user): bool
    {
        $row = $this->currentFor($user);

        return $row && $row->status === 'registered' && ! $row->company_id;
    }

    public function needsWizard(User $user): bool
    {
        $row = $this->currentFor($user);

        return $row && $row->needsWizard();
    }

    /**
     * Create company, store, settings, SaaS tenant + trial subscription.
     */
    public function provision(User $user, SaasPlan $plan, string $billingMode = 'trial'): SaasOnboarding
    {
        $onboarding = $this->currentFor($user);
        if (! $onboarding || $onboarding->status !== 'registered') {
            throw ValidationException::withMessages(['plan' => 'Inscription introuvable ou déjà provisionnée.']);
        }

        $draft = $onboarding->draft ?? [];
        $companyName = $draft['company_name'] ?? ($user->displayName().' — Entreprise');

        return DB::transaction(function () use ($user, $plan, $billingMode, $onboarding, $draft, $companyName) {
            $trialDays = (int) ($plan->trial_days ?: 14);
            $isTrial = $billingMode === 'trial';

            $company = Company::query()->create([
                'name' => $companyName,
                'legal_name' => $companyName,
                'email' => $draft['email'] ?? $user->email,
                'phone' => $draft['phone'] ?? $user->phone,
                'country' => 'Maroc',
                'currency' => 'MAD',
                'timezone' => 'Africa/Casablanca',
                'locale' => 'fr',
                'status' => 'active',
            ]);

            $user->companies()->attach($company->id, [
                'role' => 'owner',
                'status' => 'active',
                'is_primary' => true,
            ]);

            $store = Store::query()->create([
                'company_id' => $company->id,
                'name' => 'Boutique principale',
                'code' => 'MAIN',
                'city' => null,
                'country' => 'Maroc',
                'is_active' => true,
                'is_default' => true,
            ]);

            $user->stores()->syncWithoutDetaching([$store->id]);

            foreach (['tax', 'currencies', 'languages', 'numbering', 'pos'] as $group) {
                CompanySetting::query()->updateOrCreate(
                    ['company_id' => $company->id, 'group' => $group],
                    ['payload' => SettingsDefaults::for($group)]
                );
            }

            $tenant = SaasTenant::query()->create([
                'company_id' => $company->id,
                'name' => $companyName,
                'legal_name' => $companyName,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => 'MA',
                'status' => $isTrial ? 'trial' : 'active',
                'trial_ends_at' => now()->addDays($trialDays),
                'owner_user_id' => $user->id,
            ]);

            $cycle = 'monthly';
            $amount = $isTrial ? 0 : $plan->price_monthly;
            $ends = $isTrial ? now()->addDays($trialDays) : now()->addMonth();

            $sub = SaasSubscription::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_plan_id' => $plan->id,
                'status' => $isTrial ? 'trialing' : 'active',
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'currency' => $plan->currency ?? 'MAD',
                'starts_at' => now(),
                'ends_at' => $ends,
                'renews_at' => $ends,
                'trial_ends_at' => $isTrial ? $ends : null,
                'provider' => 'onboarding',
                'auto_renew' => true,
            ]);

            SaasLicense::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_subscription_id' => $sub->id,
                'status' => 'active',
                'expires_at' => $ends,
            ]);

            Workspace::set($company, $store);

            app(\App\Services\ModuleManagerService::class)->syncCompanyFromPlan($company, $plan, false);

            $onboarding->update([
                'company_id' => $company->id,
                'saas_tenant_id' => $tenant->id,
                'saas_plan_id' => $plan->id,
                'status' => 'wizard',
                'wizard_step' => 1,
                'provisioned_at' => now(),
                'checklist' => $this->defaultChecklist(),
            ]);

            return $onboarding->fresh(['company', 'plan', 'tenant']);
        });
    }

    public function saveWizard(SaasOnboarding $onboarding, array $data, ?UploadedFile $logo = null): SaasOnboarding
    {
        $company = $onboarding->company;
        if (! $company) {
            throw ValidationException::withMessages(['wizard' => 'Entreprise introuvable.']);
        }

        return DB::transaction(function () use ($onboarding, $company, $data, $logo) {
            Workspace::set($company, $company->stores()->where('is_default', true)->first() ?? $company->stores()->first());

            $payload = [
                'address' => $data['address'] ?? $company->address,
                'city' => $data['city'] ?? $company->city,
                'country' => $data['country'] ?? $company->country ?? 'Maroc',
                'currency' => strtoupper($data['currency'] ?? $company->currency ?? 'MAD'),
                'locale' => $data['locale'] ?? $company->locale ?? 'fr',
            ];

            if ($logo) {
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $payload['logo_path'] = $logo->store('companies/'.$company->id, 'public');
            }

            $company->update($payload);

            $store = $company->stores()->orderByDesc('is_default')->first();
            if ($store) {
                $store->update([
                    'name' => $data['register_name'] ?? $store->name,
                    'city' => $data['city'] ?? $store->city,
                    'country' => $data['country'] ?? $store->country,
                    'address' => $data['address'] ?? ($store->address ?? null),
                ]);
            }

            $taxRate = (float) ($data['tax_rate'] ?? 20);
            CompanySetting::query()->updateOrCreate(
                ['company_id' => $company->id, 'group' => 'tax'],
                ['payload' => array_replace_recursive(SettingsDefaults::for('tax'), [
                    'default_tax_rate' => $taxRate,
                ])]
            );
            CompanySetting::query()->updateOrCreate(
                ['company_id' => $company->id, 'group' => 'currencies'],
                ['payload' => array_replace_recursive(SettingsDefaults::for('currencies'), [
                    'default_currency' => $payload['currency'],
                ])]
            );
            if (array_key_exists('pos', CompanySetting::GROUPS)) {
                CompanySetting::query()->updateOrCreate(
                    ['company_id' => $company->id, 'group' => 'pos'],
                    ['payload' => array_replace_recursive(SettingsDefaults::for('pos'), [
                        'default_cash_drawer' => $data['register_name'] ?? 'Caisse 1',
                    ])]
                );
            }

            $categoryName = trim((string) ($data['category_name'] ?? 'Général'));
            if ($categoryName !== '') {
                $slug = Str::slug($categoryName) ?: 'general';
                $category = Category::query()->firstOrCreate(
                    ['company_id' => $company->id, 'slug' => $slug],
                    ['name' => $categoryName, 'sort_order' => 1]
                );

                $productName = trim((string) ($data['product_name'] ?? ''));
                if ($productName !== '') {
                    $exists = Product::query()
                        ->where('company_id', $company->id)
                        ->where('name', $productName)
                        ->exists();
                    if (! $exists) {
                        $pSlug = Str::slug($productName).'-'.Str::lower(Str::random(3));
                        $product = Product::query()->create([
                            'company_id' => $company->id,
                            'category_id' => $category->id,
                            'type' => 'physical',
                            'name' => $productName,
                            'slug' => $pSlug,
                            'sku' => 'SKU-'.strtoupper(Str::random(6)),
                            'unit' => 'pce',
                            'sale_price' => (float) ($data['product_price'] ?? 0),
                            'purchase_price' => 0,
                            'tax_rate' => $taxRate,
                            'status' => 'active',
                            'track_stock' => true,
                            'created_by' => $onboarding->user_id,
                        ]);
                        if ($store && method_exists($product, 'stores')) {
                            $product->stores()->syncWithoutDetaching([$store->id]);
                        }
                    }
                }
            }

            $employeeName = trim((string) ($data['employee_name'] ?? ''));
            $employeeEmail = trim((string) ($data['employee_email'] ?? ''));
            if ($employeeName !== '' && $employeeEmail !== '' && ! User::query()->where('email', $employeeEmail)->exists()) {
                $parts = preg_split('/\s+/', $employeeName, 2) ?: [$employeeName];
                $employee = User::query()->create([
                    'name' => $employeeName,
                    'first_name' => $parts[0] ?? $employeeName,
                    'last_name' => $parts[1] ?? null,
                    'email' => $employeeEmail,
                    'password' => Hash::make(Str::password(12)),
                    'status' => 'invited',
                    'invited_at' => now(),
                ]);
                $employee->companies()->attach($company->id, [
                    'role' => $data['employee_role'] ?? 'cashier',
                    'status' => 'invited',
                    'is_primary' => false,
                ]);
                if ($store) {
                    $employee->stores()->syncWithoutDetaching([$store->id]);
                }
            }

            $onboarding->update([
                'wizard_step' => 2,
                'draft' => array_merge($onboarding->draft ?? [], [
                    'wizard' => collect($data)->except(['logo'])->all(),
                ]),
            ]);

            return $onboarding->fresh();
        });
    }

    public function complete(SaasOnboarding $onboarding): SaasOnboarding
    {
        $onboarding->update([
            'status' => 'completed',
            'completed_at' => now(),
            'welcome_shown' => false,
        ]);

        return $onboarding->fresh();
    }

    public function markWelcomeShown(SaasOnboarding $onboarding): void
    {
        if (! $onboarding->welcome_shown) {
            $onboarding->update(['welcome_shown' => true]);
        }
    }

    public function defaultChecklist(): array
    {
        return [
            'add_product' => false,
            'add_customer' => false,
            'make_sale' => false,
            'configure_pos' => false,
            'invite_employee' => false,
        ];
    }

    public function dashboardChecklist(?Company $company = null): ?array
    {
        $company = $company ?? Workspace::company();
        $user = auth()->user();
        if (! $company || ! $user) {
            return null;
        }

        $onboarding = SaasOnboarding::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $onboarding || ! in_array($onboarding->status, ['completed', 'wizard'], true)) {
            return null;
        }

        // Don't show checklist until wizard finished (or skipped via complete)
        if ($onboarding->status === 'wizard') {
            return null;
        }

        $items = [
            'add_product' => [
                'label' => 'Ajouter un produit',
                'done' => Product::query()->where('company_id', $company->id)->exists(),
                'href' => route('products.create'),
            ],
            'add_customer' => [
                'label' => 'Ajouter un client',
                'done' => Schema::hasTable('customers') && Customer::query()->where('company_id', $company->id)->exists(),
                'href' => route('customers.create'),
            ],
            'make_sale' => [
                'label' => 'Effectuer une vente',
                'done' => Schema::hasTable('sales') && Sale::query()->where('company_id', $company->id)->exists(),
                'href' => route('sales.create'),
            ],
            'configure_pos' => [
                'label' => 'Configurer la caisse',
                'done' => (bool) (CompanySetting::query()
                    ->where('company_id', $company->id)
                    ->where('group', 'pos')
                    ->value('payload')['default_cash_drawer'] ?? false),
                'href' => route('pos.dashboard'),
            ],
            'invite_employee' => [
                'label' => 'Inviter un employé',
                'done' => $company->users()->where('users.id', '!=', $user->id)->exists(),
                'href' => route('users.index'),
            ],
        ];

        $done = collect($items)->where('done', true)->count();
        $total = count($items);
        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        // Hide when fully done
        if ($progress >= 100) {
            return null;
        }

        return [
            'items' => $items,
            'done' => $done,
            'total' => $total,
            'progress' => $progress,
            'onboarding' => $onboarding,
            'show_welcome' => $onboarding && $onboarding->isComplete() && ! $onboarding->welcome_shown,
        ];
    }

    public function loginAfterRegister(User $user, Request $request): void
    {
        SessionManager::loginUser($user, $request, false);
    }
}
