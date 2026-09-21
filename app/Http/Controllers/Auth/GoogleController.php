<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, BrevoService $brevo): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

            return $this->fail('Google sign-in failed. Please try again.');
        }

        $email = Str::lower((string) $google->getEmail());
        $emailVerified = filter_var($google->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || ! $emailVerified) {
            return $this->fail('Your Google account does not have a verified email address.');
        }

        $isNew = false;
        $user = User::where('google_id', $google->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Link Google to the existing account.
                if (! $user->hasVerifiedEmail()) {
                    // Pre-hijack protection: someone may have registered this address
                    // (unverified) before its real owner. Drop that password and any
                    // remember-me cookies so only the Google owner keeps access.
                    $user->forceFill(['password' => null, 'remember_token' => Str::random(60)]);
                }

                $user->forceFill([
                    'google_id' => $google->getId(),
                    'avatar_url' => $user->avatar_url ?: $google->getAvatar(),
                ]);
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                $user->save();
            } else {
                $isNew = true;
                $user = new User(['name' => $google->getName() ?: Str::before($email, '@'), 'email' => $email]);
                $user->forceFill([
                    'google_id' => $google->getId(),
                    'avatar_url' => $google->getAvatar(),
                    'password' => null,
                    'email_verified_at' => now(),
                ])->save();
            }
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        Log::info('Successful Google login', ['user_id' => $user->getKey(), 'ip' => $request->ip(), 'new_user' => $isNew]);

        if ($isNew) {
            $brevo->sendWelcomeEmail($user);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
