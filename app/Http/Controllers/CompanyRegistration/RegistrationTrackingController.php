<?php

namespace App\Http\Controllers\CompanyRegistration;

use App\Http\Controllers\Controller;
use App\Services\CompanyRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationTrackingController extends Controller
{
    public function __construct(private CompanyRegistrationService $registrations)
    {
    }

    public function form(): View
    {
        return view('company-registration.track', [
            'item' => null,
            'message' => null,
            'reference' => old('reference', request('ref')),
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
        ], [
            'reference.required' => 'Saisissez votre numéro de demande (ex. REQ-20260805-XXXX).',
        ]);

        $ref = strtoupper(trim($data['reference']));

        return redirect()->route('register-company.track.show', ['reference' => $ref]);
    }

    public function show(string $reference): View|RedirectResponse
    {
        $item = $this->registrations->findByReference($reference);

        if (! $item) {
            return redirect()
                ->route('register-company.track')
                ->withInput(['reference' => $reference])
                ->withErrors(['reference' => 'Aucune demande trouvée pour cette référence.']);
        }

        return view('company-registration.track', [
            'item' => $item,
            'message' => $this->registrations->statusMessage($item),
            'reference' => $item->reference,
        ]);
    }
}
