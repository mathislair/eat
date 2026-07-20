# eat — dev Makefile
# Run `make` or `make help` to list targets.

.DEFAULT_GOAL := help
.PHONY: help install dev serve vite build test migrate fresh seed tinker \
        fmt route-list clear optimize-clear fresh-assets

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

install: ## First-time setup: deps, .env, key, migrate, build assets
	composer run setup

dev: ## Run everything (server + queue + logs + vite) in one command
	composer run dev

serve: ## Run only the PHP dev server
	php artisan serve

vite: ## Run only the Vite dev server (hot reload)
	npm run dev

build: ## Build production front-end assets
	npm run build

test: ## Run the test suite
	php artisan test

migrate: ## Run pending migrations
	php artisan migrate

fresh: ## Drop all tables and re-migrate (DESTROYS DEV DATA)
	php artisan migrate:fresh

seed: ## Run database seeders
	php artisan db:seed

fresh-assets: ## Reinstall node deps and rebuild assets
	npm install && npm run build

tinker: ## Open an interactive REPL
	php artisan tinker

route-list: ## List all registered routes
	php artisan route:list

clear: ## Clear all Laravel caches (config, route, view, app)
	php artisan optimize:clear
