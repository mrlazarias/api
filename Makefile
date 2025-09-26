# Robust API Makefile
.PHONY: help build up down restart logs shell test install clean

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Environment setup
setup: ## Initial setup - copy env file and install dependencies
	@echo "Setting up the project..."
	cp env.example .env
	@echo "Please edit .env file with your configuration"

# Container management using Podman
build: ## Build the containers
	podman-compose build

build-simple: ## Build with simplified Containerfile (if main build fails)
	podman build -f Containerfile.simple -t robust-api:dev --target development .

build-no-cache: ## Build containers without cache
	podman-compose build --no-cache

up: ## Start the containers
	podman-compose up -d

down: ## Stop the containers
	podman-compose down

restart: ## Restart the containers
	podman-compose restart

logs: ## Show container logs
	podman-compose logs -f

logs-app: ## Show app container logs
	podman-compose logs -f app

logs-nginx: ## Show nginx container logs
	podman-compose logs -f nginx

# Development commands
shell: ## Access the app container shell
	podman-compose exec app sh

shell-root: ## Access the app container shell as root
	podman-compose exec -u root app sh

# Database commands
db-migrate: ## Run database migrations
	podman-compose exec app php console.php migrations:migrate

db-seed: ## Run database seeders
	podman-compose exec app php console.php db:seed

db-reset: ## Reset database (drop and recreate)
	podman-compose exec app php console.php db:reset

# Dependencies
install: ## Install PHP dependencies
	podman-compose exec app composer install

install-prod: ## Install production dependencies
	podman-compose exec app composer install --no-dev --optimize-autoloader

update: ## Update PHP dependencies
	podman-compose exec app composer update

# Code quality
test: ## Run tests
	podman-compose exec app ./vendor/bin/pest

test-coverage: ## Run tests with coverage
	podman-compose exec app ./vendor/bin/pest --coverage

phpstan: ## Run PHPStan analysis
	podman-compose exec app ./vendor/bin/phpstan analyse

cs-check: ## Check coding standards
	podman-compose exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix coding standards
	podman-compose exec app ./vendor/bin/php-cs-fixer fix

# Cache management
cache-clear: ## Clear application cache
	podman-compose exec app php console.php cache:clear

cache-warm: ## Warm up application cache
	podman-compose exec app php console.php cache:warm

# Production deployment
deploy: ## Deploy to production
	@echo "Building production image..."
	podman build -t robust-api:latest --target production .
	@echo "Production image built successfully!"

# Cleanup
clean: ## Clean up containers and volumes
	podman-compose down -v
	podman system prune -f

clean-all: ## Clean up everything including images
	podman-compose down -v --rmi all
	podman system prune -a -f

# Development utilities
generate-key: ## Generate a new JWT secret key
	@echo "JWT_SECRET=$$(openssl rand -hex 32)"

check-env: ## Check environment configuration
	@echo "Checking environment configuration..."
	@if [ ! -f .env ]; then echo "❌ .env file not found! Run 'make setup' first."; exit 1; fi
	@echo "✅ .env file exists"
	@echo "✅ Environment check passed"

# API utilities
api-docs: ## Generate API documentation
	podman-compose exec app php console.php api:docs

api-test: ## Test API endpoints
	@echo "Testing API health endpoint..."
	@curl -s http://localhost:8000/health | jq .

