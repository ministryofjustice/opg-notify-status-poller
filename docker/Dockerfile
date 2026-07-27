FROM composer@sha256:5946476338742b200bb9ff88f8be56275ddae4b3949c72305cb0dbf10cfcb760 AS composer-prod

COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts --no-dev

RUN composer dumpautoload -o

FROM composer@sha256:5946476338742b200bb9ff88f8be56275ddae4b3949c72305cb0dbf10cfcb760 AS composer-test

COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts

RUN composer dumpautoload -o

FROM php:8.5.7-cli-alpine@sha256:bc49fd4f6ea0f46fd45756349747400f668586ee1c4a69c63a436a6b51761350 AS build
RUN apk --no-cache add \
  # needed by intl
  icu-dev

# Alpine Image Vulnerabilities
RUN apk upgrade --no-cache busybox c-ares curl libcurl musl-utils nghttp2-libs libcrypto3 libssl3 libsodium libxml2 sqlite-libs xz xz-libs zlib

RUN docker-php-ext-install pcntl
RUN docker-php-ext-install calendar
RUN docker-php-ext-install intl

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/
COPY --chown=www-data:www-data src src
COPY --chown=www-data:www-data public public

FROM build AS test
COPY --from=composer-test --chown=www-data:www-data /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
COPY --chown=www-data:www-data tests tests
RUN mkdir -p /var/www/test-results/functional
RUN chown -R www-data:www-data /var/www/test-results/functional

USER "www-data"
CMD ["php", "-f", "/var/www/public/job_runner.php"]

FROM build AS production
COPY --from=composer-prod --chown=www-data:www-data /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
USER "www-data"
CMD ["php", "-f", "/var/www/public/job_runner.php"]

