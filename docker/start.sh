#!/bin/sh
set -eu

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage/app/public storage/framework bootstrap/cache

php artisan migrate --force
php artisan db:seed --force

exec gosu www-data php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
