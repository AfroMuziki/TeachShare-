@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <h1 class="mb-3 text-center text-2xl font-light text-gray-800 sm:text-[28px]">Verify your email</h1>
    <p class="mb-6 text-center text-sm text-gray-500">
        We sent a verification link to <span class="font-medium text-gray-700">{{ auth()->user()->email }}</span>.
        Click it to activate your account.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div role="status" class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">
            A new verification link is on its way.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf
        <x-auth.primary-button>Resend verification email</x-auth.primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit"
                class="rounded text-sm font-medium text-[#0E9AA7] hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0E9AA7]/25">
            Sign out
        </button>
    </form>
@endsection
