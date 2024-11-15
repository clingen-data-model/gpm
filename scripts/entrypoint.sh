#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

env=${CONTAINER_ENV:-production}

cd /srv/app

if [[ -n ${APP_DO_INIT-} || ! -d vendor ]]; then
    echo "Running composer install..."
    composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev 
    composer dump-autoload
fi

/srv/app/scripts/awaitdb.bash || echo "Unable to connect to DB!"

echo "Running migrations..."
php artisan migrate --force --no-interaction

# make a new APP_KEY if not in environment and if the one in .env is not properly formatted
if [[ ${APP_KEY:-invalid} != base64* ]]; then
    touch .env # make sure we have an .env file soe key:generate doesn't complain
    grep APP_KEY=base64 .env >/dev/null || php artisan key:generate -q
fi

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

