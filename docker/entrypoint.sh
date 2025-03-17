#!/usr/bin/env bash

# Start Nginx service
service nginx start

# Generate application key if not already set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Setup SQLlite DB
touch /var/www/database/database.sqlite
chmod 777 /var/www/database/database.sqlite
chown -R www-data:www-data /var/www/database

# Run Laravel migrations
# php artisan migrate --force
# For now we do this every deploy
php artisan migrate:refresh --seed --force

# Create symbolic link for storage
php artisan storage:link

# Clear and optimize the application cache
php artisan optimize:clear
php artisan optimize

# Start PHP-FPM
php-fpm