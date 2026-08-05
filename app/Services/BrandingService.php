<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Support\SettingsDefaults;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public const FILE_KEYS = [
        'logo_path',
        'logo_compact_path',
        'favicon_path',
        'login_background_path',
        'login_logo_path',
        'invoice_logo_path',
        'invoice_signature_path',
        'invoice_stamp_path',
    ];

    public function forCompany(?Company $company = null): array
    {
        $company = $company ?? Workspace::company();
        if (! $company) {
            return SettingsDefaults::for('branding');
        }

        $defaults = SettingsDefaults::for('branding');
        $row = CompanySetting::query()
            ->where('company_id', $company->id)
            ->where('group', 'branding')
            ->first();

        $payload = $row
            ? array_replace_recursive($defaults, $row->payload ?? [])
            : $defaults;

        if (($payload['trade_name'] ?? '') === '') {
            $payload['trade_name'] = $company->name;
        }

        // Fallback logo from company identity
        if (empty($payload['logo_path']) && $company->logo_path) {
            $payload['logo_path'] = $company->logo_path;
        }

        return $payload;
    }

    public function save(array $data, array $files = [], ?Company $company = null): array
    {
        $company = $company ?? Workspace::company();
        abort_unless($company, 403);

        $current = $this->forCompany($company);
        $emails = $current['emails'] ?? SettingsDefaults::for('branding')['emails'];

        if (! empty($data['emails']) && is_array($data['emails'])) {
            foreach ($data['emails'] as $key => $tpl) {
                if (! isset($emails[$key])) {
                    continue;
                }
                if (isset($tpl['subject'])) {
                    $emails[$key]['subject'] = (string) $tpl['subject'];
                }
                if (isset($tpl['body'])) {
                    $emails[$key]['body'] = (string) $tpl['body'];
                }
            }
        }

        $payload = [
            'trade_name' => $data['trade_name'] ?? $current['trade_name'],
            'tagline' => $data['tagline'] ?? $current['tagline'],
            'primary_color' => $data['primary_color'] ?? $current['primary_color'],
            'secondary_color' => $data['secondary_color'] ?? $current['secondary_color'],
            'button_color' => $data['button_color'] ?? $current['button_color'],
            'link_color' => $data['link_color'] ?? $current['link_color'],
            'theme' => $data['theme'] ?? $current['theme'],
            'density' => $data['density'] ?? $current['density'],
            'login_welcome' => $data['login_welcome'] ?? $current['login_welcome'],
            'login_footer' => $data['login_footer'] ?? $current['login_footer'],
            'invoice_primary_color' => $data['invoice_primary_color'] ?? $current['invoice_primary_color'],
            'invoice_header' => $data['invoice_header'] ?? $current['invoice_header'],
            'invoice_footer' => $data['invoice_footer'] ?? $current['invoice_footer'],
            'invoice_legal' => $data['invoice_legal'] ?? $current['invoice_legal'],
            'timezone' => $data['timezone'] ?? $current['timezone'],
            'locale' => $data['locale'] ?? $current['locale'],
            'currency' => strtoupper($data['currency'] ?? $current['currency']),
            'date_format' => $data['date_format'] ?? $current['date_format'],
            'number_format' => $data['number_format'] ?? $current['number_format'],
            'emails' => $emails,
        ];

        foreach (self::FILE_KEYS as $key) {
            $payload[$key] = $current[$key] ?? null;
            if (! empty($files[$key]) && $files[$key] instanceof UploadedFile) {
                if (! empty($payload[$key])) {
                    Storage::disk('public')->delete($payload[$key]);
                }
                $payload[$key] = $files[$key]->store('branding/'.$company->id, 'public');
            }
            if (! empty($data['remove_'.$key])) {
                if (! empty($payload[$key])) {
                    Storage::disk('public')->delete($payload[$key]);
                }
                $payload[$key] = null;
            }
        }

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'group' => 'branding'],
            ['payload' => $payload]
        );

        // Mirror lightweight appearance + company fields (same company only)
        $appearance = app(SettingService::class)->getGroup('appearance', $company);
        app(SettingService::class)->saveGroup('appearance', array_merge($appearance, [
            'theme' => $payload['theme'] === 'auto' ? 'system' : $payload['theme'],
            'primary_color' => $payload['primary_color'],
            'date_format' => $payload['date_format'],
            'amount_format' => $payload['number_format'],
            'favicon_path' => $payload['favicon_path'],
            'compact_mode' => ($payload['density'] ?? '') === 'compact',
        ]), $company);

        $company->update([
            'timezone' => $payload['timezone'],
            'locale' => $payload['locale'],
            'currency' => $payload['currency'],
        ]);

        if (! empty($payload['logo_path']) && $payload['logo_path'] !== $company->logo_path) {
            $company->update(['logo_path' => $payload['logo_path']]);
        }

        return $this->forCompany($company->fresh());
    }

    public function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function cssVariables(array $branding): array
    {
        $primary = $branding['primary_color'] ?? '#0f766e';
        $secondary = $branding['secondary_color'] ?? '#134e4a';
        $button = $branding['button_color'] ?? $primary;
        $link = $branding['link_color'] ?? '#0d9488';

        return [
            '--color-gp-primary' => $primary,
            '--color-gp-primary-hover' => $link,
            '--color-gp-secondary' => $secondary,
            '--color-gp-accent' => $link,
            '--gp-brand-button' => $button,
            '--gp-brand-link' => $link,
        ];
    }

    public function renderEmail(string $type, array $vars, ?Company $company = null): array
    {
        $branding = $this->forCompany($company);
        $tpl = $branding['emails'][$type] ?? ['subject' => '', 'body' => ''];
        $replace = static function (string $text) use ($vars, $branding): string {
            $map = array_merge([
                'company' => $branding['trade_name'] ?? 'GreenPOS',
                'name' => 'Client',
                'number' => '',
                'amount' => '',
                'link' => '#',
                'message' => '',
            ], $vars);

            foreach ($map as $k => $v) {
                $text = str_replace('{{'.$k.'}}', (string) $v, $text);
            }

            return $text;
        };

        return [
            'subject' => $replace($tpl['subject'] ?? ''),
            'body' => $replace($tpl['body'] ?? ''),
        ];
    }
}
