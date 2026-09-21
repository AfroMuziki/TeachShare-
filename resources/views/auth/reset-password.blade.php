@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <h1 class="mb-8 text-center text-2xl font-light text-gray-800 sm:text-[28px]">Choose a new password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth.input name="email" type="email" label="Email" :value="old('email', $request->email)" autocomplete="username" required readonly />
        <x-auth.password-input name="password" label="New password" autocomplete="new-password" required minlength="8" autofocus />
        <x-auth.password-input name="password_confirmation" label="Confirm new password" autocomplete="new-password" required minlength="8" />

        <x-auth.primary-button>Reset password</x-auth.primary-button>
    </form>
@endsection
