#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force
fi

attempt=0
until php artisan migrate --force; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Database did not become ready in time." >&2
        exit 1
    fi
    echo "Waiting for database (attempt ${attempt})..."
    sleep 3
done

php artisan config:cache

exec php artisan serve --host=0.0.0.0 --port=8000
