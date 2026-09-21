{{-- Renders only when requiresCaptcha() is true AND Turnstile keys are configured. --}}
@props(['show' => false])

@if ($show && filled(config('services.turnstile.site_key')))
    <div>
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light"></div>

        @error('captcha')
            <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
