FROM php:8.1-fpm-buster

RUN apt-get update && \
    apt-get install -y git && \
    apt-get install -y unzip && \
    apt-get install -y libicu-dev

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . ./

RUN composer install

CMD [ "php", "./bot.php" ]
