<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Isolated Brevo SMTP deliverability probe — not used by OTP or product flows.
 */
class BrevoDeliveryTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        $fromAddress = (string) (config('mail.mailers.brevo.from.address') ?: 'info@funnity.com');
        $fromName = (string) (config('mail.mailers.brevo.from.name') ?: 'Funnity');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Funnity — Brevo Email Delivery Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.brevo-delivery-test',
            text: 'emails.brevo-delivery-test-text',
        );
    }
}
