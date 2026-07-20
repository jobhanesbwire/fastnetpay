#!/bin/sh
set -e

APP_DIR=/var/www/html

if [ "${SESSION_HANDLER:-files}" = "redis" ]; then
    if php -m | grep -qi '^redis$'; then
        redis_host="${SESSION_REDIS_HOST:-fastnetpay_redis}"
        redis_port="${SESSION_REDIS_PORT:-6379}"
        redis_db="${SESSION_REDIS_DATABASE:-0}"
        redis_prefix="${SESSION_REDIS_PREFIX:-fnp_sess:}"
        {
            echo "session.save_handler = redis"
            echo "session.save_path = \"tcp://${redis_host}:${redis_port}?database=${redis_db}&prefix=${redis_prefix}\""
        } > /usr/local/etc/php/conf.d/fastnetpay-session.ini
    else
        echo "SESSION_HANDLER=redis requested but the PHP redis extension is not available." >&2
        exit 1
    fi
fi

if [ ! -f "$APP_DIR/config.php" ] && [ -f "$APP_DIR/config.sample.php" ]; then
    cp "$APP_DIR/config.sample.php" "$APP_DIR/config.php"
    chown www-data:www-data "$APP_DIR/config.php" 2>/dev/null || true
    chmod 640 "$APP_DIR/config.php" 2>/dev/null || true
fi

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
