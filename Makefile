.PHONY: setup up down restart shell logs migrate fresh build node-build help

# First-time setup on a new machine
setup:
	@echo "🚀 Setting up project..."
	@if [ ! -f .env ]; then cp .env.example .env && echo "✓ Created .env from .env.example"; else echo "✓ .env already exists"; fi
	docker compose up -d --build
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose run --rm node sh -c "npm install && npm run build"
	@echo "✅ Setup complete! Open http://localhost:8081"

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

shell:
	docker compose exec app bash

logs:
	docker compose logs -f

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

build:
	docker compose build

node-build:
	docker compose run --rm node sh -c "npm install && npm run build"

help:
	@echo "Available commands:"
	@echo "  make setup       - First-time setup (run once)"
	@echo "  make up          - Start all containers"
	@echo "  make down        - Stop all containers"
	@echo "  make shell       - Bash into app container"
	@echo "  make logs        - Follow all logs"
	@echo "  make migrate     - Run migrations"
	@echo "  make fresh       - Drop tables, re-migrate, seed"
	@echo "  make node-build  - Compile frontend assets"