<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Store;
use App\Services\SettingService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings)
    {
    }

    public function index(): View
    {
        $this->authorize('settings.view');
        $company = Workspace::company();
        $storesCount = Store::query()->where('company_id', $company->id)->count();
        $configured = CompanySetting::query()->where('company_id', $company->id)->count();

        $sections = $this->sections();

        return view('settings.index', compact('company', 'storesCount', 'configured', 'sections'));
    }

    public function company(): View
    {
        $this->authorize('settings.view');
        $company = Workspace::company();

        return view('settings.company', [
            'company' => $company,
            'section' => 'company',
            'title' => 'Informations de l\'entreprise',
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'ice' => ['nullable', 'string', 'max:50'],
            'if_number' => ['nullable', 'string', 'max:50'],
            'rc' => ['nullable', 'string', 'max:50'],
            'patente' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'cnss' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $this->settings->updateCompany($data, $request->file('logo'));

        return back()->with('success', 'Informations entreprise enregistrées.');
    }

    public function stores(): View
    {
        $this->authorize('settings.view');
        $company = Workspace::company();
        $stores = Store::query()->where('company_id', $company->id)->orderByDesc('is_default')->orderBy('name')->get();

        return view('settings.stores', [
            'stores' => $stores,
            'section' => 'stores',
            'title' => 'Boutiques',
        ]);
    }

    public function storeStore(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        $this->settings->createStore($data);

        return back()->with('success', 'Boutique créée.');
    }

    public function updateStore(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        $this->settings->updateStore($store, $data);

        return back()->with('success', 'Boutique mise à jour.');
    }

    public function destroyStore(Store $store): RedirectResponse
    {
        $this->authorize('settings.update');
        $this->settings->deleteStore($store);

        return back()->with('success', 'Boutique supprimée.');
    }

    public function section(string $section): View
    {
        $this->authorize('settings.view');

        if (! array_key_exists($section, CompanySetting::GROUPS)) {
            abort(404);
        }

        $settings = $this->settings->getGroup($section);

        return view('settings.section', [
            'settings' => $settings,
            'section' => $section,
            'title' => CompanySetting::GROUPS[$section],
            'group' => $section,
        ]);
    }

    public function updateSection(Request $request, string $section): RedirectResponse
    {
        $this->authorize('settings.update');

        if (! array_key_exists($section, CompanySetting::GROUPS)) {
            abort(404);
        }

        $payload = $this->validateSection($request, $section);
        $this->settings->saveGroup($section, $payload);

        return back()->with('success', CompanySetting::GROUPS[$section].' enregistré.');
    }

    protected function sections(): array
    {
        return [
            ['key' => 'company', 'label' => 'Informations entreprise', 'desc' => 'Identité, logo et coordonnées', 'route' => 'settings.company', 'icon' => 'building'],
            ['key' => 'stores', 'label' => 'Boutiques', 'desc' => 'Points de vente et magasins', 'route' => 'settings.stores', 'icon' => 'store'],
            ['key' => 'tax', 'label' => 'Fiscalité', 'desc' => 'TVA et taux', 'route' => 'settings.section', 'params' => ['section' => 'tax'], 'icon' => 'percent'],
            ['key' => 'currencies', 'label' => 'Devises', 'desc' => 'Devise et formats monétaires', 'route' => 'settings.section', 'params' => ['section' => 'currencies'], 'icon' => 'currency'],
            ['key' => 'languages', 'label' => 'Langues', 'desc' => 'Locales et langues', 'route' => 'settings.section', 'params' => ['section' => 'languages'], 'icon' => 'lang'],
            ['key' => 'numbering', 'label' => 'Numérotation', 'desc' => 'Préfixes et compteurs', 'route' => 'settings.section', 'params' => ['section' => 'numbering'], 'icon' => 'hash'],
            ['key' => 'pos', 'label' => 'POS & Caisse', 'desc' => 'Terminal et périphériques', 'route' => 'settings.section', 'params' => ['section' => 'pos'], 'icon' => 'pos'],
            ['key' => 'invoicing', 'label' => 'Facturation', 'desc' => 'PDF, délais, mentions', 'route' => 'settings.section', 'params' => ['section' => 'invoicing'], 'icon' => 'invoice'],
            ['key' => 'payments', 'label' => 'Paiements', 'desc' => 'Modes et arrondi', 'route' => 'settings.section', 'params' => ['section' => 'payments'], 'icon' => 'pay'],
            ['key' => 'notifications', 'label' => 'Notifications', 'desc' => 'Email, SMS, alertes', 'route' => 'settings.section', 'params' => ['section' => 'notifications'], 'icon' => 'bell'],
            ['key' => 'security', 'label' => 'Sécurité', 'desc' => 'Session et mots de passe', 'route' => 'settings.section', 'params' => ['section' => 'security'], 'icon' => 'shield'],
            ['key' => 'backup', 'label' => 'Sauvegarde', 'desc' => 'Backup et rétention', 'route' => 'settings.section', 'params' => ['section' => 'backup'], 'icon' => 'backup'],
            ['key' => 'appearance', 'label' => 'Apparence', 'desc' => 'Thème et formats', 'route' => 'settings.section', 'params' => ['section' => 'appearance'], 'icon' => 'palette'],
            ['key' => 'branding', 'label' => 'Branding', 'desc' => 'Logo, couleurs, login, factures, emails', 'route' => 'branding.index', 'icon' => 'brand'],
            ['key' => 'integrations', 'label' => 'Intégrations', 'desc' => 'Connecteurs externes', 'route' => 'settings.section', 'params' => ['section' => 'integrations'], 'icon' => 'plug'],
        ];
    }

    protected function validateSection(Request $request, string $section): array
    {
        return match ($section) {
            'tax' => [
                'default_tax_rate' => (float) $request->input('default_tax_rate', 20),
                'prices_include_tax' => $request->boolean('prices_include_tax'),
                'tax_label' => $request->string('tax_label')->toString() ?: 'TVA',
                'tax_number_label' => $request->string('tax_number_label')->toString() ?: 'ICE',
                'show_tax_on_tickets' => $request->boolean('show_tax_on_tickets'),
            ],
            'currencies' => [
                'default_currency' => strtoupper($request->string('default_currency')->toString() ?: 'MAD'),
                'available' => array_values(array_filter(array_map('trim', explode(',', $request->string('available')->toString())))),
                'decimal_places' => (int) $request->input('decimal_places', 2),
                'thousand_separator' => $request->string('thousand_separator')->toString(),
                'decimal_separator' => $request->string('decimal_separator')->toString() ?: ',',
                'symbol_position' => $request->string('symbol_position')->toString() ?: 'after',
                'rounding_mode' => $request->string('rounding_mode')->toString() ?: 'standard',
            ],
            'languages' => [
                'default_locale' => $request->string('default_locale')->toString() ?: 'fr',
                'available' => array_values(array_filter(array_map('trim', explode(',', $request->string('available')->toString())))),
                'fallback_locale' => $request->string('fallback_locale')->toString() ?: 'fr',
            ],
            'numbering' => [
                'invoice_prefix' => $request->string('invoice_prefix')->toString() ?: 'FAC',
                'invoice_padding' => (int) $request->input('invoice_padding', 4),
                'quote_prefix' => $request->string('quote_prefix')->toString() ?: 'DEV',
                'quote_padding' => (int) $request->input('quote_padding', 4),
                'sale_prefix' => $request->string('sale_prefix')->toString() ?: 'VTE',
                'sale_padding' => (int) $request->input('sale_padding', 4),
                'pos_prefix' => $request->string('pos_prefix')->toString() ?: 'TKT',
                'pos_padding' => (int) $request->input('pos_padding', 4),
                'credit_note_prefix' => $request->string('credit_note_prefix')->toString() ?: 'AVR',
                'purchase_prefix' => $request->string('purchase_prefix')->toString() ?: 'CMD',
                'reset_yearly' => $request->boolean('reset_yearly'),
                'include_year' => $request->boolean('include_year'),
            ],
            'pos' => [
                'auto_open_session' => $request->boolean('auto_open_session'),
                'auto_close_session' => $request->boolean('auto_close_session'),
                'print_ticket' => $request->boolean('print_ticket'),
                'print_copies' => (int) $request->input('print_copies', 1),
                'default_printer' => $request->string('default_printer')->toString(),
                'barcode_reader' => $request->boolean('barcode_reader'),
                'scanner_enabled' => $request->boolean('scanner_enabled'),
                'default_cash_drawer' => $request->string('default_cash_drawer')->toString(),
                'allow_held_tickets' => $request->boolean('allow_held_tickets'),
                'require_customer' => $request->boolean('require_customer'),
                'show_stock_warning' => $request->boolean('show_stock_warning'),
                'receipt_footer' => $request->string('receipt_footer')->toString(),
            ],
            'invoicing' => [
                'default_payment_terms' => $request->string('default_payment_terms')->toString(),
                'default_due_days' => (int) $request->input('default_due_days', 30),
                'pdf_template' => $request->string('pdf_template')->toString() ?: 'standard',
                'show_logo' => $request->boolean('show_logo'),
                'footer_notes' => $request->string('footer_notes')->toString(),
                'legal_mentions' => $request->string('legal_mentions')->toString(),
                'auto_send_email' => $request->boolean('auto_send_email'),
                'numbering_aligned' => $request->boolean('numbering_aligned'),
            ],
            'payments' => [
                'default_currency' => strtoupper($request->string('default_currency')->toString() ?: 'MAD'),
                'allow_partial' => $request->boolean('allow_partial'),
                'allow_refunds' => $request->boolean('allow_refunds'),
                'rounding_enabled' => $request->boolean('rounding_enabled'),
                'rounding_step' => (float) $request->input('rounding_step', 0.05),
                'methods' => [
                    'cash' => $request->boolean('methods.cash'),
                    'card' => $request->boolean('methods.card'),
                    'bank_transfer' => $request->boolean('methods.bank_transfer'),
                    'mobile' => $request->boolean('methods.mobile'),
                    'check' => $request->boolean('methods.check'),
                    'other' => $request->boolean('methods.other'),
                ],
            ],
            'notifications' => [
                'email_enabled' => $request->boolean('email_enabled'),
                'sms_enabled' => $request->boolean('sms_enabled'),
                'sms_prepared' => true,
                'internal_enabled' => $request->boolean('internal_enabled'),
                'stock_alerts' => $request->boolean('stock_alerts'),
                'stock_alert_threshold' => $request->boolean('stock_alert_threshold'),
                'cash_alerts' => $request->boolean('cash_alerts'),
                'invoice_overdue' => $request->boolean('invoice_overdue'),
                'low_stock_email' => $request->boolean('low_stock_email'),
                'daily_summary' => $request->boolean('daily_summary'),
            ],
            'security' => [
                'session_lifetime' => (int) $request->input('session_lifetime', 120),
                'password_min_length' => (int) $request->input('password_min_length', 8),
                'password_require_mixed' => $request->boolean('password_require_mixed'),
                'password_require_numbers' => $request->boolean('password_require_numbers'),
                'password_require_symbols' => $request->boolean('password_require_symbols'),
                'two_factor_prepared' => true,
                'two_factor_enabled' => $request->boolean('two_factor_enabled'),
                'audit_logging' => $request->boolean('audit_logging'),
                'allowed_devices_only' => $request->boolean('allowed_devices_only'),
                'max_login_attempts' => (int) $request->input('max_login_attempts', 5),
            ],
            'backup' => [
                'auto_backup' => $request->boolean('auto_backup'),
                'frequency' => $request->string('frequency')->toString() ?: 'daily',
                'retention_days' => (int) $request->input('retention_days', 30),
                'include_files' => $request->boolean('include_files'),
                'last_backup_at' => $request->input('last_backup_at'),
                'note' => $request->string('note')->toString(),
            ],
            'appearance' => [
                'theme' => $request->string('theme')->toString() ?: 'system',
                'primary_color' => $request->string('primary_color')->toString() ?: '#16a34a',
                'sidebar_style' => $request->string('sidebar_style')->toString() ?: 'dark',
                'date_format' => $request->string('date_format')->toString() ?: 'd/m/Y',
                'time_format' => $request->string('time_format')->toString() ?: 'H:i',
                'amount_format' => $request->string('amount_format')->toString() ?: 'fr',
                'compact_mode' => $request->boolean('compact_mode'),
            ],
            'integrations' => [
                'smtp_prepared' => true,
                'sms_gateway_prepared' => true,
                'payment_gateway_prepared' => true,
                'webhook_url' => $request->string('webhook_url')->toString(),
                'api_enabled' => $request->boolean('api_enabled'),
                'notes' => $request->string('notes')->toString(),
            ],
            default => [],
        };
    }
}
