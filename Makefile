.PHONY: up down install migrate start all cs cbf

CONTAINER_NAME_BOT=bot

up:
	docker compose up --build -d

down:
	docker compose down

install:
	docker compose exec $(CONTAINER_NAME_BOT) sh -c "composer install"

migrate:
	docker compose exec $(CONTAINER_NAME_BOT) sh -c "vendor/bin/phinx migrate"

start:
	docker compose exec $(CONTAINER_NAME_BOT) sh -c "php bot.php"

all:
	make up
	make install
	make migrate
	make start

cs:
	docker compose exec $(CONTAINER_NAME_BOT) composer cs-check

cbf:
	docker compose exec $(CONTAINER_NAME_BOT) composer cs-fix
