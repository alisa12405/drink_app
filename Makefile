.PHONY: up down build restart logs sh sh-frontend ps deploy-cloudflare logs-cloudflare

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

# Build production frontend and run it behind a remotely managed Cloudflare Tunnel.
deploy-cloudflare:
	docker compose --profile cloudflare up -d --build site cloudflared

logs-cloudflare:
	docker compose logs -f site cloudflared

# Vao shell container PHP (backend) de chay composer/artisan
sh:
	docker compose exec app bash

# Vao shell container Node (frontend) de chay npm
sh-frontend:
	docker compose exec frontend sh
