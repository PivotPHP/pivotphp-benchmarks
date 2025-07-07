.PHONY: help build up down benchmark-all benchmark-helix benchmark-comparative report clean shell-% logs-%

# Default target
help:
	@echo "HelixPHP Benchmarks - Available Commands:"
	@echo ""
	@echo "  make build                - Build all Docker containers"
	@echo "  make up                   - Start all services"
	@echo "  make down                 - Stop all services"
	@echo "  make benchmark-all        - Run all benchmarks"
	@echo "  make benchmark-helix      - Run HelixPHP benchmarks (core + orm)"
	@echo "  make benchmark-core       - Run only HelixPHP Core benchmarks"
	@echo "  make benchmark-orm        - Run only HelixPHP ORM benchmarks"
	@echo "  make benchmark-comparative - Run comparative framework benchmarks"
	@echo "  make benchmark-matrix     - Run benchmarks across all PHP versions"
	@echo "  make benchmark-concurrency - Run in-process concurrency benchmarks"
	@echo "  make benchmark-http-concurrency - Run HTTP concurrency benchmarks"
	@echo "  make report              - Generate benchmark reports"
	@echo "  make clean               - Clean all results and containers"
	@echo ""
	@echo "  Container Access:"
	@echo "  make shell-helix-core    - Open shell in HelixPHP Core container"
	@echo "  make shell-helix-orm     - Open shell in HelixPHP ORM container"
	@echo "  make shell-slim4         - Open shell in Slim 4 container"
	@echo "  make shell-lumen         - Open shell in Lumen container"
	@echo "  make shell-flight        - Open shell in Flight container"
	@echo "  make shell-symfony       - Open shell in Symfony container"
	@echo "  make db-shell            - Access MySQL shell"
	@echo "  make redis-cli           - Access Redis CLI"
	@echo ""
	@echo "  Logs:"
	@echo "  make logs-helix-core     - View HelixPHP Core logs"
	@echo "  make logs-helix-orm      - View HelixPHP ORM logs"
	@echo "  make logs-all            - View all container logs"

# Build all containers
build:
	@echo "Building all benchmark containers..."
	docker-compose build --parallel

# Start services
up:
	@echo "Starting benchmark environment..."
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 5
	@docker-compose ps

# Stop services
down:
	@echo "Stopping benchmark environment..."
	docker-compose down

# Run all benchmarks
benchmark-all: up
	@echo "Running all benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --all

# Run HelixPHP benchmarks (both core and orm)
benchmark-helix: up
	@echo "Running HelixPHP benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --frameworks=helix-core,helix-orm

# Run only HelixPHP Core benchmarks
benchmark-core: up
	@echo "Running HelixPHP Core benchmarks..."
	docker-compose exec helix-core php benchmarks/run.php

# Run only HelixPHP ORM benchmarks
benchmark-orm: up
	@echo "Running HelixPHP ORM benchmarks..."
	docker-compose exec helix-orm php benchmarks/run.php

# Run comparative framework benchmarks
benchmark-comparative: up
	@echo "Running comparative framework benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --frameworks=slim4,lumen,flight,symfony

# Run full comparison report (HelixPHP vs market frameworks)
benchmark-comparison: 
	@echo "Running full framework comparison..."
	php scripts/generate-comparative-report.php

# Run benchmarks across all PHP versions
benchmark-matrix: up
	@echo "Running benchmark matrix across PHP versions..."
	@for version in 81 82 83; do \
		echo "Running benchmarks on PHP 8.$$version..."; \
		PHP_VERSION=8.$$version docker-compose up -d --build; \
		docker-compose exec orchestrator php /scripts/run-benchmarks.php --all; \
	done

# Run concurrency benchmarks
benchmark-concurrency: up
	@echo "Running concurrency benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --benchmark=concurrency

# Run HTTP concurrency benchmarks (requires servers)
benchmark-http-concurrency:
	@echo "Starting web servers..."
	./scripts/start-servers.sh &
	@sleep 5
	@echo "Running HTTP concurrency benchmarks..."
	php scripts/run-concurrency-benchmarks.php
	@echo "Stopping servers..."
	@pkill -f "php -S"

# Generate reports
report:
	@echo "Generating benchmark reports..."
	docker-compose exec orchestrator php /scripts/generate-report.php

# Clean everything
clean:
	@echo "Cleaning benchmark environment..."
	docker-compose down -v
	rm -rf results/raw/*
	rm -rf results/processed/*
	rm -rf results/reports/*
	@echo "Clean complete."

# Shell access to containers
shell-%:
	docker-compose exec $* /bin/bash

# Database shell
db-shell:
	docker-compose exec mysql mysql -u benchmark -pbenchmark benchmark

# Redis CLI
redis-cli:
	docker-compose exec redis redis-cli

# View logs
logs-%:
	docker-compose logs -f $*

logs-all:
	docker-compose logs -f

# Install dependencies for all frameworks
install-deps:
	@echo "Installing dependencies for all frameworks..."
	@for framework in helix-core helix-orm slim4 lumen flight symfony; do \
		echo "Installing dependencies for $$framework..."; \
		cd frameworks/$$framework && composer install --no-dev --optimize-autoloader; \
		cd ../..; \
	done

# Validate setup
validate:
	@echo "Validating benchmark setup..."
	@docker-compose config
	@echo "Setup is valid!"