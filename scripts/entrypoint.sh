#!/usr/bin/env bash

set -euox pipefail
IFS=$'\n\t'

PRODUCTION_MODE=${PRODUCTION_MODE:-true}

cd /srv/app

DEV_OPTION=''

if [[ "$PRODUCTION_MODE" == "true" ]]; then
    echo "Running in production mode, will not install dev dependencies."
    DEV_OPTION='--no-dev'
fi

if [[ -n ${APP_DO_INIT-} || ! -d vendor ]]; then
    echo "Running composer install..."
    composer install --no-interaction --no-plugins --no-scripts --prefer-dist $DEV_OPTION
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

if [[ "$PRODUCTION_MODE" == "true" ]]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan clear-compiled
    php artisan notify:deployed
else
    echo "Clearing configuration cache..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear
    php artisan clear-compiled
fi

php-fpm${PHP_VERSION} -F -O
