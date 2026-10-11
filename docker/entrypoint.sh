#!/bin/sh
set -eu

if [ "$1" = "php-fpm" ]; then
    composer install --prefer-dist --no-interaction

    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    if ! grep -Eq '^APP_KEY=.+$' .env; then
        php artisan key:generate --force
    fi

    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
        touch database/database.sqlite
    fi

    php artisan config:clear
    php artisan migrate --force
fi

exec "$@"
