<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Admin alert: one IP reached the hourly failed-login ceiling. */
class SuspiciousLoginMail extends Mailable
{
    public function __construct(public string $ip, public string $email, public int $attempts)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Suspicious sign-in activity on TeachShare');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.suspicious-login');
    }
}
