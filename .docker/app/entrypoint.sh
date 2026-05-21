#!/bin/sh
set -e

APP_DIR=/var/www/html

if [ -f "$APP_DIR/system/composer.json" ] && [ ! -f "$APP_DIR/system/vendor/autoload.php" ]; then
    composer install --working-dir="$APP_DIR/system" --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

for dir in \
    qrcode/cache \
    system/cache \
    system/uploads \
    system/uploads/_sysfrm_tmp_ \
    system/uploads/sms \
    system/uploads/system \
    system/vendor/mpdf/mpdf/tmp \
    ui/cache
do
    mkdir -p "$APP_DIR/$dir"
    chown -R www-data:www-data "$APP_DIR/$dir" 2>/dev/null || true
    chmod -R ug+rwX,o-rwx "$APP_DIR/$dir" 2>/dev/null || true
done

exec "$@"
