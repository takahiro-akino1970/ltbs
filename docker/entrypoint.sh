#!/bin/sh
set -eu

if [ "$1" = "php-fpm" ]; then
    case "${APP_PORTAL:-}" in
        order|admin)
            ;;
        *)
            echo "APP_PORTAL must be set to 'order' or 'admin'." >&2
            exit 1
            ;;
    esac

    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY must be set in the app environment file." >&2
        exit 1
    fi

    composer install --prefer-dist --no-interaction

    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
        touch database/database.sqlite
    fi

    php artisan config:clear

    case "${RUN_MIGRATIONS:-false}" in
        true)
            php artisan migrate --force
            ;;
        false)
            ;;
        *)
            echo "RUN_MIGRATIONS must be 'true' or 'false'." >&2
            exit 1
            ;;
    esac
fi

exec "$@"
