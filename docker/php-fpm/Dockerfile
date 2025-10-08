FROM php:8.1-fpm

# System deps
RUN apt-get update && apt-get install -y git unzip zip && rm -rf /var/lib/apt/lists/*

# PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions mongodb-1.20.0

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working dir
WORKDIR /app

# Copy application code
COPY ../ ./

COPY ../branding ./web/public/branding

# Copy PHP config
COPY docker/php-fpm/mclogs.ini /usr/local/etc/php/conf.d/mclogs.ini
COPY docker/php-fpm/security.ini /usr/local/etc/php/conf.d/security.ini

#RUN composer require vlucas/phpdotenv --no-interaction --no-scripts

# Default command is to run Composer serve script
CMD ["composer", "run-script", "serve"]
