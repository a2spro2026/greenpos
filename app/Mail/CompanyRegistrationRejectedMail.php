<?php

namespace App\Mail;

use App\Models\CompanyRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyRegistrationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyRegistrationRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre demande GreenPOS a été refusée',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:system-ui,sans-serif;line-height:1.6;color:#18181b">'
                .'<h1 style="font-size:20px">Demande non acceptée</h1>'
                .'<p>Votre demande n’a pas été acceptée.</p>'
                .'<p>Vous pouvez contacter notre équipe.</p>'
                .($this->request->rejection_reason
                    ? '<p><strong>Commentaire :</strong><br>'.nl2br(e($this->request->rejection_reason)).'</p>'
                    : '')
                .'<p style="color:#71717a;font-size:13px">Référence : '.e($this->request->reference).'</p>'
                .'<p><a href="'.e(url('/contact')).'">Contacter GreenPOS</a> · '
                .'<a href="'.e(url('/suivi-demande/'.$this->request->reference)).'">Suivre ma demande</a></p>'
                .'</div>',
        );
    }
}
