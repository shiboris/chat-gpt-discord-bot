FROM php:8.1-fpm-buster

RUN apt-get update && \
    apt-get install -y git && \
    apt-get install -y sqlite3 && \
    apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . ./

RUN composer install

CMD [ "php", "./bot.php" ]
