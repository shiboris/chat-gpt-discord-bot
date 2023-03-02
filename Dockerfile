FROM php:8.1-fpm-buster

WORKDIR /usr/src/myapp

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . /usr/src/myapp

CMD [ "php", "./bot.php" ]
