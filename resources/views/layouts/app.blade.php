<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('app.name', 'TeachShare') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#F3F6FB] text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ route('dashboard') }}" class="text-base font-semibold text-[#2F5BFF] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#2F5BFF]/30 rounded">
                {{ config('app.name', 'TeachShare') }}
            </a>

            <form method="POST" action="{{ route('logout') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <button type="submit" :disabled="loading"
                        class="rounded-full border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-[#2F5BFF]/30 disabled:opacity-60">
                    Sign out
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
