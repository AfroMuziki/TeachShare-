#!/usr/bin/env bash
# Run from the Laravel project root AFTER unzipping this overlay on top of a
# fresh Laravel 11 + Breeze (blade) install. See README.md.
set -euo pipefail

[ -f artisan ] || { echo "Run this from your Laravel project root (artisan not found)."; exit 1; }

echo "-> Removing Breeze scaffolding that Phase 1 replaces"
rm -rf resources/views/profile
rm -f  app/Http/Controllers/ProfileController.php \
       app/Http/Requests/ProfileUpdateRequest.php \
       app/Http/Controllers/Auth/ConfirmablePasswordController.php \
       app/Http/Controllers/Auth/PasswordController.php \
       app/View/Components/AppLayout.php \
       app/View/Components/GuestLayout.php \
       resources/views/auth/login.blade.php \
       resources/views/auth/confirm-password.blade.php \
       resources/views/layouts/guest.blade.php \
       resources/views/layouts/navigation.blade.php \
       tests/Feature/ProfileTest.php \
       tests/Feature/ExampleTest.php \
       tests/Feature/Auth/PasswordConfirmationTest.php \
       tests/Feature/Auth/PasswordUpdateTest.php

echo "-> Making config/database.php read DB_SSLMODE (Supabase needs SSL)"
if grep -q "env('DB_SSLMODE'" config/database.php; then
  echo "   already configured"
elif grep -q "'sslmode' => 'prefer'" config/database.php; then
  sed -i "s/'sslmode' => 'prefer'/'sslmode' => env('DB_SSLMODE', 'prefer')/" config/database.php
  echo "   patched"
else
  echo "   WARNING: could not find the pgsql sslmode line; set 'sslmode' => env('DB_SSLMODE', 'prefer') by hand."
fi

echo "-> Checking Composer packages"
for pkg in laravel/breeze laravel/socialite cloudinary/cloudinary_php; do
  grep -q "\"$pkg\"" composer.json || echo "   MISSING: composer require $pkg"
done

cat <<'MSG'

Done. Next:
  cp .env.example .env      # then fill in credentials
  php artisan key:generate
  php artisan migrate
  php artisan db:seed       # optional: creates the admin user
  npm install && npm run dev
  php artisan serve
  php artisan test
MSG
