<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rendered to HTML and delivered by BrevoService (Brevo API), not sent over SMTP. */
class VerifyEmailMail extends Mailable
{
    public function __construct(public User $user, public string $verifyUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your TeachShare email');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
