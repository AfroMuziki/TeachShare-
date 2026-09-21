@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')
    <h1 class="mb-8 text-center text-2xl font-light text-gray-800 sm:text-[28px]">Create your account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <x-auth.input name="name" label="Full name" :value="old('name')" autocomplete="name" required autofocus />
        <x-auth.input name="email" type="email" label="Email" :value="old('email')" autocomplete="email" required />
        <x-auth.password-input name="password" label="Password" autocomplete="new-password" required minlength="8" />
        <x-auth.password-input name="password_confirmation" label="Confirm password" autocomplete="new-password" required minlength="8" />

        <p class="text-xs text-gray-500">Use at least 8 characters, with letters and numbers.</p>

        <x-auth.primary-button>Create account</x-auth.primary-button>
    </form>

    <div class="my-6 flex items-center gap-4" role="separator">
        <span class="h-px flex-1 bg-gray-200"></span>
        <span class="text-sm text-gray-500">Or sign up with</span>
        <span class="h-px flex-1 bg-gray-200"></span>
    </div>

    <x-auth.google-button label="Google" />

    <p class="mt-7 text-center text-sm text-gray-600">
        Already have an account?
        <a href="{{ route('login') }}"
           class="rounded font-medium text-[#0E9AA7] hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0E9AA7]/25">Sign in</a>
    </p>
@endsection
