# TeachShare — Phase 1: Authentication

Laravel 11 · Breeze (Blade + Alpine) · Tailwind · Socialite · Brevo API · Supabase Postgres

Phase 1 covers auth only: sign in, register, forgot/reset password, email verification (Brevo),
Google OAuth, protected dashboard, logout, "remember me", and multi-layer brute-force protection.
Upload / download / browsing are Phase 2+.

## Setup

This package is an **overlay**: it contains only the files that differ from a stock Laravel 11 + Breeze install.

```bash
# 1. Fresh Laravel 11 + Breeze + packages
composer create-project laravel/laravel:^11.0 teachshare
cd teachshare
composer require laravel/socialite cloudinary/cloudinary_php
composer require laravel/breeze --dev
php artisan breeze:install blade --no-interaction

# 2. Lay this overlay on top (say yes to overwrite), then clean up Breeze leftovers
unzip -o ../teachshare-phase1.zip -d .
bash phase1-setup.sh

# 3. Configure and run
cp .env.example .env         # fill in credentials
php artisan key:generate
php artisan migrate
php artisan db:seed          # optional admin user
npm install && npm run dev   # keep running
php artisan serve
```

Run the tests with `php artisan test`. They use in-memory SQLite (Laravel 11's default `phpunit.xml`), so `pdo_sqlite` must be installed. They fake every outbound HTTP call (Brevo, Turnstile) and never touch Supabase.

## Credentials

| Service | Where | Notes |
|---|---|---|
| Supabase | Project → Connect → **Session pooler** | Use port **5432**. The transaction pooler (6543) breaks Laravel's prepared statements. `DB_SSLMODE=require` is already in `.env.example`. |
| Brevo | SMTP & API → API keys | Verify your sender/domain first or Brevo rejects sends. `BREVO_SENDER_EMAIL` must be that verified sender. |
| Google | Cloud Console → Credentials → OAuth client (Web) | Add `${APP_URL}/auth/google/callback` as an authorised redirect URI (one per environment). |
| Turnstile | Cloudflare → Turnstile | Optional. Leave both keys blank to disable the CAPTCHA entirely. |
| Cloudinary | Dashboard | Configured but unused until Phase 2. |

With `BREVO_API_KEY` blank in `local`, emails are not sent; the rendered email (including the verify/reset link) is written to `storage/logs/laravel.log` so you can develop without Brevo.

## Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`
- `php artisan migrate --force` on deploy
- Behind Render/Vercel the app trusts proxy headers (`bootstrap/app.php`) so the real client IP feeds the throttles. If you ever expose the app without a proxy, restrict `trustProxies(at: ...)`.
- Schedule `php artisan model:prune` daily to delete `login_attempts` rows older than 90 days.

## How the pieces fit

- **Routes** — `routes/auth.php`. The sign-in route is `/sign-in` but named `login`, because Laravel's `auth` middleware redirects to `route('login')`.
- **Email** — `BrevoService` renders each `Mailable` to HTML and POSTs it to Brevo's `/v3/smtp/email`. `User` overrides `sendEmailVerificationNotification()` / `sendPasswordResetNotification()`, so Breeze's flows use Brevo without further changes. Failures are logged, never thrown.
- **Welcome email** — sent by `SendWelcomeEmail` when an address is verified, and directly when a brand-new Google user is created.
- **Brute force** — `LoginThrottleService` (see class docblock). Lockout applies at every 5th consecutive failure: 5 → 15 min, 10 and 15 → 1 h, 20+ → 24 h. The streak resets after 24 h without a failure or on a successful login.
- **Google linking** — matched by `google_id`, then by verified email. Linking to a local account whose email was never verified clears that account's password, so a pre-registered squatter can't keep access.

## Known trade-offs

- Account lockout is keyed on the email string (so it can't reveal which accounts exist). The flip side is that anyone can lock a known email for 15 minutes by failing five sign-ins. That is inherent to the spec's design.
- Google-only accounts have no password until they use "Forgot password", which doubles as "set a password".

## Deploying on Render

1. Create a **Web Service** from your full Laravel repo (after applying this overlay).
2. **Build Command**:
   ```
   composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
3. **Start Command**:
   ```
   php artisan migrate --force && php artisan serve --host 0.0.0.0 --port $PORT
   ```
   (Or use a proper process manager / `php-fpm` + nginx Docker image for production.)
4. Set environment variables from `.env.example` (especially `APP_KEY`, DB_*, Google, Brevo).  
   Generate `APP_KEY` with `php artisan key:generate --show` and paste the value.
5. `APP_URL` must be your Render public URL (e.g. `https://teachshare.onrender.com`).  
   Add the matching Google OAuth redirect URI.
6. `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`, `APP_DEBUG=false`.
7. Optional: add a **Cron Job** service that runs `php artisan model:prune` daily.

The overlay already enables `trustProxies(at: '*')` so rate-limiting and logging see the real client IP behind Render's proxy.
