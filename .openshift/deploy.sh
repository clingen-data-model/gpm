#!/bin/bash

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan clear-compiled
php artisan migrate --force --no-interaction
php artisan db:seed --class=NextActionAssigneesTableSeeder --force --no-interaction
php artisan db:seed --class=NextActionTypesTableSeeder --force --no-interaction
php artisan notify:deployed