@props(['name' => 'password', 'label' => 'Password', 'autocomplete' => 'current-password'])

@php
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($name);
@endphp

<div x-data="{ show: false }">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>

    <div class="flex items-stretch gap-2">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            x-bind:type="show ? 'text' : 'password'"
            autocomplete="{{ $autocomplete }}"
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('id')->merge(['class' => 'block w-full min-w-0 rounded-xl border bg-white px-4 py-3 text-[15px] text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-[#2F5BFF] focus:outline-none focus:ring-4 focus:ring-[#2F5BFF]/20 '.($invalid ? 'border-red-500' : 'border-gray-300')]) }}
        >

        <button type="button"
                x-on:click="show = !show"
                x-bind:aria-pressed="show.toString()"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                aria-label="Show password"
                aria-controls="{{ $id }}"
                class="flex h-[50px] w-[50px] shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus-visible:border-[#2F5BFF] focus-visible:ring-4 focus-visible:ring-[#2F5BFF]/20">
            {{-- Heroicons: eye --}}
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            {{-- Heroicons: eye-slash --}}
            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror
</div>
