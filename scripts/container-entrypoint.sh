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

rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
a2enmod mpm_prefork
apache_modules="$(apache2ctl -M 2>&1)" || {
    printf '%s\n' "$apache_modules" >&2
    exit 1
}
if ! printf '%s\n' "$apache_modules" | grep -q 'mpm_prefork_module' ||
    printf '%s\n' "$apache_modules" | grep -Eq 'mpm_(event|worker)_module'; then
    printf '%s\n' "$apache_modules" >&2
    echo "Apache must load only mpm_prefork." >&2
    exit 1
fi

exec apache2-foreground
