FROM php:8.1-cli

WORKDIR /user/src/app

RUN apt-get update && \
    apt-get install -y zip && \
    apt-get install -y unzip && \
    apt-get install -y sqlite3 && \
    apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

COPY . /user/src/app
COPY --from=composer /usr/bin/composer /usr/bin/composer
