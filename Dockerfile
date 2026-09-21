FROM php:8.3-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libpq-dev zip unzip nodejs npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy everything
COPY . .

# Install PHP packages
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts || true

# Install frontend
RUN npm ci && npm run build || true

EXPOSE 10000

# Start command
CMD php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=10000
