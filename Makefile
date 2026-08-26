.PHONY: up down build restart logs sh sh-frontend ps

# Build image va khoi dong toan bo stack. Lan dau se TU DONG cai Laravel 11
# (backend/) va Vue 3 + Vite (frontend/) - xem docker/php/entrypoint.sh va
# docker/node/entrypoint.sh.
up:
	docker compose up -d --build

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

logs:
	docker compose logs -f

ps:
	docker compose ps

# Vao shell container PHP (backend) de chay composer/artisan
sh:
	docker compose exec app bash

# Vao shell container Node (frontend) de chay npm
sh-frontend:
	docker compose exec frontend sh
