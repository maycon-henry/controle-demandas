#!/bin/sh
# Prepara a aplicação a cada deploy: chave, cache de configuração, migrações e primeiro gestor.
set -e

# A Render gera APP_KEY como base64 puro; o Laravel espera o prefixo "base64:".
case "$APP_KEY" in
    base64:*) ;;
    "") echo "APP_KEY não definida" >&2; exit 1 ;;
    *) export APP_KEY="base64:$APP_KEY" ;;
esac

# Apache escuta na porta definida pela Render.
sed -i "s/^Listen .*/Listen ${PORT:-10000}/" /etc/apache2/ports.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan usuarios:gestor-inicial

exec "$@"
