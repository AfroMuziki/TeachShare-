<?php

namespace App\Listeners;

use App\Services\BrevoService;
use Illuminate\Auth\Events\Verified;

/** Auto-discovered by Laravel 11: fires once a user confirms their email address. */
class SendWelcomeEmail
{
    public function __construct(private BrevoService $brevo)
    {
    }

    public function handle(Verified $event): void
    {
        $this->brevo->sendWelcomeEmail($event->user);
    }
}
