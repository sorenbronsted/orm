FROM php:8-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions xdebug pdo_mysql mysqli @composer
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
VOLUME /app
WORKDIR /app
