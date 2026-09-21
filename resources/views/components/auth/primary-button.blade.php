{{-- Must sit inside an Alpine scope that defines `loading` (every auth form does). --}}
<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'flex w-full items-center justify-center gap-2 rounded-full bg-[#2F5BFF] px-6 py-3.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-[#2449D9] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#2F5BFF]/30 disabled:cursor-not-allowed disabled:opacity-70']) }}
    x-bind:disabled="loading"
    x-bind:aria-busy="loading.toString()"
>
    <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
    </svg>
    <span>{{ $slot }}</span>
</button>
