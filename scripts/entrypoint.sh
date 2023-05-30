#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

env=${CONTAINER_ENV:-production}

cd /srv/app

if [[ -n ${APP_DO_INIT-} || ! -d vendor ]]; then
    echo "Running composer install..."
    composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev --no-suggest
    composer dump-autoload
fi

echo "Running migrations..."
php artisan migrate --force --no-interaction

# make a new APP_KEY if the one in .env is not properly formatted
grep APP_KEY=base64 .env >/dev/null || php artisan key:generate -q

if [[ $env != "local" ]]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan clear-compiled
    php artisan notify:deployed
fi

# FIXME: should be using php-fpm behind nginx at some point
#php artisan serve -vvv --host 0.0.0.0 --port "${APP_PORT:-8013}"

php-fpm${PHP_VERSION} -F -O

