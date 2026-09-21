<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Mail\SuspiciousLoginMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends transactional email through the Brevo REST API (v3), not SMTP.
 * Every public method swallows failures (and logs them) so an email outage
 * can never break sign-up or password reset.
 */
class BrevoService
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function sendVerificationEmail(User $user, string $verifyUrl): void
    {
        $this->deliver($user->email, $user->name, new VerifyEmailMail($user, $verifyUrl));
    }

    public function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        $this->deliver($user->email, $user->name, new ResetPasswordMail($user, $resetUrl));
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->deliver($user->email, $user->name, new WelcomeMail($user, route('dashboard')));
    }

    public function sendSuspiciousLoginAlert(string $ip, string $email, int $attempts): void
    {
        $admin = (string) config('services.teachshare.admin_email');
        if ($admin === '') {
            return;
        }

        $this->deliver($admin, 'TeachShare Admin', new SuspiciousLoginMail($ip, $email, $attempts));
    }

    private function deliver(string $toEmail, ?string $toName, Mailable $mail): void
    {
        try {
            $subject = $mail->envelope()->subject;
            $html = $mail->render();
        } catch (Throwable $e) {
            Log::error('Brevo: failed to render email', ['to' => $toEmail, 'error' => $e->getMessage()]);

            return;
        }

        $apiKey = config('services.brevo.api_key');

        if (blank($apiKey)) {
            Log::warning('Brevo: BREVO_API_KEY is not set; email not sent', ['to' => $toEmail, 'subject' => $subject]);
            if (app()->isLocal()) {
                // Dev convenience only: lets you click the link straight from storage/logs.
                Log::info('Brevo (local dev) email body', ['to' => $toEmail, 'html' => $html]);
            }

            return;
        }

        try {
            $response = Http::withHeaders(['api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->retry(2, 300, throw: false)
                ->post(self::ENDPOINT, [
                    'sender' => [
                        'email' => config('services.brevo.sender_email'),
                        'name' => config('services.brevo.sender_name'),
                    ],
                    'to' => [array_filter(['email' => $toEmail, 'name' => $toName])],
                    'subject' => $subject,
                    'htmlContent' => $html,
                ]);

            if ($response->failed()) {
                // Never log $html or the api key: reset/verify URLs carry secrets.
                Log::error('Brevo: API returned an error', [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Brevo: request failed', ['to' => $toEmail, 'subject' => $subject, 'error' => $e->getMessage()]);
        }
    }
}
