<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'email']]);

        // 5 requests per 15 minutes per email + IP.
        $key = 'password-reset:'.sha1($request->input('email').'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            throw ValidationException::withMessages([
                'email' => "Too many reset requests. Try again in {$minutes} minute(s).",
            ]);
        }

        RateLimiter::hit($key, 900);

        // Result deliberately ignored: the response is identical whether or not
        // the account exists, so this form cannot be used to enumerate emails.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists for that email, we have sent a password reset link.');
    }
}
