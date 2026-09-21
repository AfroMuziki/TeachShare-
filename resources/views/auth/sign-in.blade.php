@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="mb-8 text-center text-2xl font-light text-gray-800 sm:text-[28px]">Sign in to your account</h1>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <x-auth.input name="email" type="email" label="Email" :value="old('email')"
                      autocomplete="username" required autofocus />

        <x-auth.password-input name="password" label="Password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-4">
            <label for="remember" class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))
                       class="h-4 w-4 rounded border-gray-300 text-[#2F5BFF] focus:ring-4 focus:ring-[#2F5BFF]/20">
                Remember me
            </label>

            <a href="{{ route('password.request') }}"
               class="rounded text-sm font-medium text-[#0E9AA7] hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0E9AA7]/25">
                Forgot Password?
            </a>
        </div>

        <x-auth.turnstile :show="$requiresCaptcha" />

        <x-auth.primary-button>Sign In</x-auth.primary-button>
    </form>

    <div class="my-6 flex items-center gap-4" role="separator">
        <span class="h-px flex-1 bg-gray-200"></span>
        <span class="text-sm text-gray-500">Or sign in with</span>
        <span class="h-px flex-1 bg-gray-200"></span>
    </div>

    <x-auth.google-button label="Google" />

    <p class="mt-7 text-center text-sm text-gray-600">
        New user?
        <a href="{{ route('register') }}"
           class="rounded font-medium text-[#0E9AA7] hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0E9AA7]/25">Register</a>
    </p>
@endsection
