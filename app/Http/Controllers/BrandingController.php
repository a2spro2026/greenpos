<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function __construct(private BrandingService $branding)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('settings.view');
        $company = Workspace::company();
        abort_unless($company, 403);

        $branding = $this->branding->forCompany($company);
        $tab = $request->query('tab', 'identity');

        return view('branding.index', [
            'company' => $company,
            'branding' => $branding,
            'tab' => $tab,
            'cssVars' => $this->branding->cssVariables($branding),
            'urls' => $this->assetUrls($branding),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');
        $company = Workspace::company();
        abort_unless($company, 403);

        $data = $request->validate([
            'trade_name' => ['nullable', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'button_color' => ['nullable', 'string', 'max:20'],
            'link_color' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', 'in:light,dark,auto'],
            'density' => ['nullable', 'in:compact,standard,comfortable'],
            'login_welcome' => ['nullable', 'string', 'max:255'],
            'login_footer' => ['nullable', 'string', 'max:255'],
            'invoice_primary_color' => ['nullable', 'string', 'max:20'],
            'invoice_header' => ['nullable', 'string', 'max:500'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'invoice_legal' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'in:fr,ar,en'],
            'currency' => ['nullable', 'string', 'size:3'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'number_format' => ['nullable', 'in:fr,en'],
            'emails' => ['nullable', 'array'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_compact' => ['nullable', 'image', 'max:1024'],
            'favicon' => ['nullable', 'image', 'max:512'],
            'login_background' => ['nullable', 'image', 'max:4096'],
            'login_logo' => ['nullable', 'image', 'max:2048'],
            'invoice_logo' => ['nullable', 'image', 'max:2048'],
            'invoice_signature' => ['nullable', 'image', 'max:1024'],
            'invoice_stamp' => ['nullable', 'image', 'max:1024'],
        ]);

        $files = [];
        $map = [
            'logo' => 'logo_path',
            'logo_compact' => 'logo_compact_path',
            'favicon' => 'favicon_path',
            'login_background' => 'login_background_path',
            'login_logo' => 'login_logo_path',
            'invoice_logo' => 'invoice_logo_path',
            'invoice_signature' => 'invoice_signature_path',
            'invoice_stamp' => 'invoice_stamp_path',
        ];
        foreach ($map as $input => $key) {
            if ($request->hasFile($input)) {
                $files[$key] = $request->file($input);
            }
            if ($request->boolean('remove_'.$input)) {
                $data['remove_'.$key] = true;
            }
        }

        $this->branding->save($data, $files, $company);

        return redirect()
            ->route('branding.index', ['tab' => $request->input('tab', 'identity')])
            ->with('success', 'Personnalisation enregistrée pour '.$company->name.'.');
    }

    protected function assetUrls(array $branding): array
    {
        $urls = [];
        foreach (BrandingService::FILE_KEYS as $key) {
            $urls[$key] = $this->branding->assetUrl($branding[$key] ?? null);
        }

        return $urls;
    }
}
