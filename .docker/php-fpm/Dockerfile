FROM php:8.4-fpm AS php_base

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd \
    opcache \
    pdo_mysql \
    zip \
    intl \
    redis \
    exif \
    @composer

COPY --link app.ini $PHP_INI_DIR/conf.d/

FROM php_base AS php_dev

ARG USER_ID=1000

RUN install-php-extensions xdebug

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --link app.dev.ini $PHP_INI_DIR/conf.d/

RUN usermod -u $USER_ID www-data

USER www-data
WORKDIR /app

FROM php_base AS php_prod

ARG USER_ID=1000

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link app.prod.ini $PHP_INI_DIR/conf.d/

RUN usermod -u $USER_ID www-data

USER www-data
WORKDIR /app
