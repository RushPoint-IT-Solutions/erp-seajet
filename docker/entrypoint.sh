#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
    else
        cp .env.example .env
    fi
fi

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
        sleep 2
    done
    echo "Database is up."
fi

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan migrate --force

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
