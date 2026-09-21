@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (request()->query('verified'))
        <div role="status" class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">
            Your email address is verified.
        </div>
    @endif

    <h1 class="text-2xl font-light text-gray-800 sm:text-3xl">Welcome, {{ Auth::user()->name }}</h1>
@endsection
