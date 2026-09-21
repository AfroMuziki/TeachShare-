<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rendered to HTML and delivered by BrevoService (Brevo API), not sent over SMTP. */
class WelcomeMail extends Mailable
{
    public function __construct(public User $user, public string $dashboardUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to TeachShare');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome');
    }
}
