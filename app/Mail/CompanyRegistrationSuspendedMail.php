<?php

namespace App\Mail;

use App\Models\CompanyRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyRegistrationSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyRegistrationRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre demande GreenPOS est suspendue',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:system-ui,sans-serif;line-height:1.6;color:#18181b">'
                .'<h1 style="font-size:20px">Demande suspendue</h1>'
                .'<p>Votre demande est suspendue.</p>'
                .'<p>Veuillez contacter GreenPOS.</p>'
                .'<p>Entreprise : <strong>'.e($this->request->company_name).'</strong></p>'
                .($this->request->suspend_reason
                    ? '<p><strong>Commentaire :</strong><br>'.nl2br(e($this->request->suspend_reason)).'</p>'
                    : '')
                .'<p style="color:#71717a;font-size:13px">Référence : '.e($this->request->reference).'</p>'
                .'<p><a href="'.e(url('/suivi-demande/'.$this->request->reference)).'">Suivre ma demande</a></p>'
                .'</div>',
        );
    }
}
