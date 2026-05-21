FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        gd \
        intl \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/fastnetpay.ini
COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/apache/servername.conf /etc/apache2/conf-available/fastnetpay-servername.conf
COPY .docker/app/entrypoint.sh /usr/local/bin/fastnetpay-entrypoint

WORKDIR /var/www/html
COPY . /var/www/html

RUN set -eux; \
    rm -f /usr/local/etc/php/conf.d/docker-php-ext-pdo.ini; \
    a2enconf fastnetpay-servername; \
    mkdir -p \
        qrcode/cache \
        system/cache \
        system/uploads \
        system/uploads/_sysfrm_tmp_ \
        system/uploads/sms \
        system/uploads/system \
        system/vendor/mpdf/mpdf/tmp \
        ui/cache; \
    chown -R www-data:www-data \
        qrcode/cache \
        system/cache \
        system/uploads \
        system/vendor/mpdf/mpdf/tmp \
        ui/cache; \
    chmod -R ug+rwX,o-rwx \
        qrcode/cache \
        system/cache \
        system/uploads \
        system/vendor/mpdf/mpdf/tmp \
        ui/cache; \
    chmod +x /usr/local/bin/fastnetpay-entrypoint

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/fastnetpay-entrypoint"]
CMD ["apache2-foreground"]
