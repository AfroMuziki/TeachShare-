<?php

namespace App\Http\Requests\Auth;

use App\Services\LoginThrottleService;
use App\Services\TurnstileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate, enforcing lockout and every throttle layer.
     * The throttle service is resolved from the container (not the constructor)
     * because FormRequests are built by the framework from the current request.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $throttle = app(LoginThrottleService::class);
        $email = (string) $this->input('email');
        $ip = (string) $this->ip();

        if ($throttle->isLockedOut($email)) {
            throw ValidationException::withMessages([
                'email' => 'Account temporarily locked. Please try again later.',
            ]);
        }

        if ($throttle->isBlocked($email, $ip)) {
            $seconds = $throttle->availableIn($email, $ip);

            throw ValidationException::withMessages([
                'email' => 'Too many sign-in attempts. Try again in '.max(1, (int) ceil($seconds / 60)).' minute(s).',
            ]);
        }

        if ($throttle->requiresCaptcha($email, $ip)
            && ! app(TurnstileService::class)->verify($this->input('cf-turnstile-response'), $ip)) {
            throw ValidationException::withMessages([
                'captcha' => 'Please complete the security check.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => (string) $this->input('password')], $this->boolean('remember'))) {
            $throttle->hit($email, $ip);
            $throttle->recordFailure($email, $ip);

            Log::warning('Failed login', [
                'email' => $email,
                'ip' => $ip,
                'user_agent' => $this->userAgent(),
            ]);

            if (! app()->runningUnitTests()) {
                usleep(250_000); // slow down bots
            }

            // Same message whether the email exists or not.
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $throttle->clear($email, $ip);
        $throttle->clearFailures($email);
        $throttle->recordSuccess($email, $ip);

        Log::info('Successful login', ['user_id' => Auth::id(), 'ip' => $ip]);
    }
}
