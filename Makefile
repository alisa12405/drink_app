.PHONY: up deploy down build restart logs logs-cloudflare ps sh test

# Production deployment: build immutable images and remove obsolete dev containers.
up: deploy

deploy:
	docker compose up -d --build --remove-orphans

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart app queue webserver site cloudflared

logs:
	docker compose logs -f

logs-cloudflare:
	docker compose logs -f site cloudflared

ps:
	docker compose ps

sh:
	docker compose exec app bash

test:
	docker compose exec app php artisan config:clear
	docker compose exec app ./vendor/bin/phpunit
	docker compose exec app php artisan config:cache
