.PHONY: up install migration start all

CONTAINER_NAME_BOT=bot

up:
	docker compose up --build -d

install:
	docker-compose exec $(CONTAINER_NAME_BOT) sh -c "composer install"

migration:
	docker-compose exec $(CONTAINER_NAME_BOT) sh -c "vendor/bin/phinx migrate"

start:
	docker-compose exec $(CONTAINER_NAME_BOT) sh -c "php bot.php"

all:
	make up
	make install
	make migration
	make start

cs:
	docker compose exec $(CONTAINER_NAME_BOT) composer cs-check

cbf:
	docker compose exec $(CONTAINER_NAME_BOT) composer cs-fix
