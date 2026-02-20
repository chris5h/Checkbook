#!/bin/sh
set -e

# Start php-fpm in the background
php-fpm -D

# Start nginx in the foreground (keeps the container alive)
exec nginx -g 'daemon off;'
