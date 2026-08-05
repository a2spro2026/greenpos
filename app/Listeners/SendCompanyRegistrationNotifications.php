<?php

namespace App\Listeners;

use App\Events\CompanyRegistrationApproved;
use App\Events\CompanyRegistrationRejected;
use App\Events\CompanyRegistrationSubmitted;
use App\Events\CompanyRegistrationSuspended;
use App\Mail\CompanyRegistrationApprovedMail;
use App\Mail\CompanyRegistrationRejectedMail;
use App\Mail\CompanyRegistrationSuspendedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Simulates SaaS lifecycle emails for company registration.
 * Messages are logged (and sent via configured mailer, default: log).
 */
class SendCompanyRegistrationNotifications
{
    public function handleSubmitted(CompanyRegistrationSubmitted $event): void
    {
        $r = $event->request;
        Log::info('registration.email.simulated', [
            'event' => 'submitted',
            'reference' => $r->reference,
            'to' => $r->owner_email,
            'message' => 'Votre demande a été envoyée. Elle sera étudiée par notre équipe.',
        ]);
    }

    public function handleApproved(CompanyRegistrationApproved $event): void
    {
        $r = $event->request->fresh(['plan']) ?? $event->request;

        Log::info('registration.email.simulated', [
            'event' => 'approved',
            'reference' => $r->reference,
            'to' => $r->owner_email,
            'message' => 'Votre entreprise a été activée. Vous pouvez maintenant vous connecter.',
        ]);

        $this->safeSend($r->owner_email, new CompanyRegistrationApprovedMail($r));
    }

    public function handleRejected(CompanyRegistrationRejected $event): void
    {
        $r = $event->request->fresh() ?? $event->request;

        Log::info('registration.email.simulated', [
            'event' => 'rejected',
            'reference' => $r->reference,
            'to' => $r->owner_email,
            'message' => 'Votre demande n’a pas été acceptée. Vous pouvez contacter notre équipe.',
            'reason' => $r->rejection_reason,
        ]);

        $this->safeSend($r->owner_email, new CompanyRegistrationRejectedMail($r));
    }

    public function handleSuspended(CompanyRegistrationSuspended $event): void
    {
        $r = $event->request->fresh() ?? $event->request;

        Log::info('registration.email.simulated', [
            'event' => 'suspended',
            'reference' => $r->reference,
            'to' => $r->owner_email,
            'message' => 'Votre demande est suspendue. Veuillez contacter GreenPOS.',
            'reason' => $r->suspend_reason,
        ]);

        $this->safeSend($r->owner_email, new CompanyRegistrationSuspendedMail($r));
    }

    private function safeSend(string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
