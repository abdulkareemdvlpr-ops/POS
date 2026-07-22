FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql zip bcmath gd opcache

# Enable Apache Mod Rewrite
RUN a2enmod rewrite

# Install NodeJS & NPM
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Set Working Directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . .

# Install dependencies and build assets
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm install && npm run build

# Setup permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Run migrations and start apache dynamically (bypassing CRLF/entrypoint issues)
CMD ["sh", "-c", "if [ -n \"$PORT\" ]; then sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf; fi && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && apache2-foreground"]
