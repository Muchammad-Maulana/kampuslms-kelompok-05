#!/bin/sh
set -eu

mkdir -p \
    /var/www/html/storage/app/private \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache

if [ "$#" -gt 0 ] && [ "$1" != "apache2-foreground" ]; then
    exec gosu www-data "$@"
fi

port="${PORT:-8080}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must contain only digits." >&2
        exit 1
        ;;
esac

sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
