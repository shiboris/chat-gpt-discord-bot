.PHONY: up install migration start all

CONTAINER_NAME_BOT=bot

up:
	docker compose up --build -d

install:
	docker-compose run $(CONTAINER_NAME_BOT) sh -c "composer install"

migration:
	docker-compose run $(CONTAINER_NAME_BOT) sh -c "vendor/bin/phinx migrate"

start:
	docker-compose run $(CONTAINER_NAME_BOT) sh -c "php bot.php"

all:
	make up
	make install
	make migration
	make start
