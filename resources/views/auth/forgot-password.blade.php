@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <h1 class="mb-3 text-center text-2xl font-light text-gray-800 sm:text-[28px]">Forgot your password?</h1>
    <p class="mb-8 text-center text-sm text-gray-500">Enter your email and we'll send you a link to reset it.</p>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <x-auth.input name="email" type="email" label="Email" :value="old('email')" autocomplete="email" required autofocus />

        <x-auth.primary-button>Send reset link</x-auth.primary-button>
    </form>

    <p class="mt-7 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}"
           class="rounded font-medium text-[#0E9AA7] hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0E9AA7]/25">Back to sign in</a>
    </p>
@endsection
