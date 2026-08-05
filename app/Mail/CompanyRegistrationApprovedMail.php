<?php

namespace App\Mail;

use App\Models\CompanyRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyRegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyRegistrationRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre entreprise GreenPOS est maintenant activée',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:system-ui,sans-serif;line-height:1.6;color:#18181b">'
                .'<h1 style="font-size:20px">Entreprise activée</h1>'
                .'<p>Votre entreprise a été activée.</p>'
                .'<p>Vous pouvez maintenant vous connecter.</p>'
                .'<p>Entreprise : <strong>'.e($this->request->company_name).'</strong><br>'
                .'Email : <strong>'.e($this->request->owner_email).'</strong></p>'
                .'<p><a href="'.e(url('/login')).'" style="display:inline-block;padding:10px 16px;background:#16a34a;color:#fff;border-radius:8px;text-decoration:none">Se connecter à mon espace</a></p>'
                .'<p style="color:#71717a;font-size:13px">Référence : '.e($this->request->reference).'</p>'
                .'</div>',
        );
    }
}
