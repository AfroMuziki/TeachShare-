<?php

namespace App\Services;

use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Three independent rate-limit layers plus an escalating account lockout.
 *
 *   email+IP  5 / 60s     one person hammering one account
 *   email    10 / 15min   one account attacked from rotating IPs
 *   IP       20 / 1h      one IP spraying many accounts
 *
 * Everything is keyed on the *submitted* email string, never on whether a user
 * exists, so no response or lockout state can be used to enumerate accounts.
 */
class LoginThrottleService
{
    public const IP_EMAIL_MAX = 5;
    public const IP_EMAIL_DECAY = 60;
    public const EMAIL_MAX = 10;
    public const EMAIL_DECAY = 900;
    public const IP_MAX = 20;
    public const IP_DECAY = 3600;

    public const CAPTCHA_AFTER = 3;
    public const FAILURE_TTL = 86400; // failure streak resets after 24h without a failure

    public function __construct(private TurnstileService $turnstile, private BrevoService $brevo)
    {
    }

    /** @return array{ip_email: string, email: string, ip: string} */
    public function keys(string $email, string $ip): array
    {
        $email = $this->normalize($email);

        return [
            'ip_email' => 'login:ip-email:'.sha1($email.'|'.$ip),
            'email' => 'login:email:'.sha1($email),
            'ip' => 'login:ip:'.sha1($ip),
        ];
    }

    /** Call on every FAILED attempt: increments all three counters. */
    public function hit(string $email, string $ip): void
    {
        $k = $this->keys($email, $ip);

        RateLimiter::hit($k['ip_email'], self::IP_EMAIL_DECAY);
        RateLimiter::hit($k['email'], self::EMAIL_DECAY);
        RateLimiter::hit($k['ip'], self::IP_DECAY);
    }

    /** On success: forgive the user's own counters, but NOT the IP counter. */
    public function clear(string $email, string $ip): void
    {
        $k = $this->keys($email, $ip);

        RateLimiter::clear($k['ip_email']);
        RateLimiter::clear($k['email']);
    }

    public function isBlocked(string $email, string $ip): bool
    {
        $k = $this->keys($email, $ip);

        return RateLimiter::tooManyAttempts($k['ip_email'], self::IP_EMAIL_MAX)
            || RateLimiter::tooManyAttempts($k['email'], self::EMAIL_MAX)
            || RateLimiter::tooManyAttempts($k['ip'], self::IP_MAX);
    }

    public function availableIn(string $email, string $ip): int
    {
        $k = $this->keys($email, $ip);
        $waits = [];

        foreach ([
            [$k['ip_email'], self::IP_EMAIL_MAX],
            [$k['email'], self::EMAIL_MAX],
            [$k['ip'], self::IP_MAX],
        ] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $waits[] = RateLimiter::availableIn($key);
            }
        }

        return $waits ? max($waits) : 0;
    }

    /**
     * Persist a failed attempt, extend the failure streak and escalate the lockout.
     * Must run AFTER hit() so the IP counter is current for the admin alert.
     *
     * Lockout applies at every 5th consecutive failure:
     *   5 -> 15 min, 10 -> 1 hour, 15 -> 1 hour, 20+ -> 24 hours
     */
    public function recordFailure(string $email, string $ip): void
    {
        $email = $this->normalize($email);

        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            'successful' => false,
        ]);

        $failures = ((int) Cache::get($this->failuresKey($email), 0)) + 1;
        Cache::put($this->failuresKey($email), $failures, self::FAILURE_TTL);

        if ($failures % 5 === 0) {
            $seconds = match (true) {
                $failures >= 20 => 86400,
                $failures >= 10 => 3600,
                default => 900,
            };

            Cache::put($this->lockoutKey($email), now()->addSeconds($seconds)->getTimestamp(), $seconds);
            Log::warning('Account locked after repeated failures', [
                'email' => $email,
                'ip' => $ip,
                'failures' => $failures,
                'locked_for_seconds' => $seconds,
            ]);
        }

        $this->alertIfSpraying($email, $ip);
    }

    public function recordSuccess(string $email, string $ip): void
    {
        LoginAttempt::create([
            'email' => $this->normalize($email),
            'ip_address' => $ip,
            'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            'successful' => true,
        ]);
    }

    public function isLockedOut(string $email): bool
    {
        return Cache::has($this->lockoutKey($email));
    }

    public function lockoutRemaining(string $email): int
    {
        $until = Cache::get($this->lockoutKey($email));

        return $until ? max(0, (int) $until - now()->getTimestamp()) : 0;
    }

    public function clearFailures(string $email): void
    {
        Cache::forget($this->failuresKey($email));
        Cache::forget($this->lockoutKey($email));
    }

    public function failureCount(string $email): int
    {
        return (int) Cache::get($this->failuresKey($email), 0);
    }

    /** True only when Turnstile is configured AND this email has 3+ recent failures. */
    public function requiresCaptcha(?string $email, string $ip): bool
    {
        if (blank($email) || ! $this->turnstile->enabled()) {
            return false;
        }

        return $this->failureCount($email) >= self::CAPTCHA_AFTER;
    }

    private function alertIfSpraying(string $email, string $ip): void
    {
        $attempts = RateLimiter::attempts($this->keys($email, $ip)['ip']);

        // Cache::add is atomic: at most one alert per IP per hour.
        if ($attempts >= self::IP_MAX && Cache::add('login:alert:'.sha1($ip), 1, self::IP_DECAY)) {
            $this->brevo->sendSuspiciousLoginAlert($ip, $email, $attempts);
        }
    }

    private function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function failuresKey(string $email): string
    {
        return 'login:failures:'.sha1($this->normalize($email));
    }

    private function lockoutKey(string $email): string
    {
        return 'login:lockout:'.sha1($this->normalize($email));
    }
}
