.PHONY: help build up down benchmark-all benchmark-helix benchmark-comparative report clean

# Default target
help:
	@echo "HelixPHP Benchmarks - Available Commands:"
	@echo "  make build              - Build all Docker containers"
	@echo "  make up                 - Start benchmark environment"
	@echo "  make down               - Stop benchmark environment"
	@echo "  make benchmark-all      - Run all benchmarks"
	@echo "  make benchmark-helix    - Run only HelixPHP benchmarks"
	@echo "  make benchmark-comparative - Run comparative framework benchmarks"
	@echo "  make report             - Generate benchmark reports"
	@echo "  make clean              - Clean all results"
	@echo "  make shell-php82        - Open shell in PHP 8.2 container"

# Docker commands
build:
	docker-compose build --no-cache

up:
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 5

down:
	docker-compose down

# Benchmark commands
benchmark-all: up
	@echo "Running all benchmarks..."
	docker-compose exec benchmark-php82 php scripts/run-benchmarks.php --all
	@make report

benchmark-helix: up
	@echo "Running HelixPHP benchmarks..."
	docker-compose exec benchmark-php82 php scripts/run-benchmarks.php --framework=helix
	@make report

benchmark-comparative: up
	@echo "Running comparative framework benchmarks..."
	docker-compose exec benchmark-php82 php scripts/run-benchmarks.php --comparative
	@make report

# Specific PHP version benchmarks
benchmark-php81: up
	@echo "Running benchmarks on PHP 8.1..."
	docker-compose exec benchmark-php81 php scripts/run-benchmarks.php --all

benchmark-php82: up
	@echo "Running benchmarks on PHP 8.2..."
	docker-compose exec benchmark-php82 php scripts/run-benchmarks.php --all

benchmark-php83: up
	@echo "Running benchmarks on PHP 8.3..."
	docker-compose exec benchmark-php83 php scripts/run-benchmarks.php --all

# Report generation
report:
	@echo "Generating benchmark reports..."
	docker-compose exec benchmark-php82 php scripts/generate-report.php
	@echo "Reports generated in results/reports/"

# Utility commands
clean:
	@echo "Cleaning benchmark results..."
	rm -rf results/raw/*
	rm -rf results/processed/*
	rm -rf results/reports/*
	@echo "Clean complete."

shell-php81:
	docker-compose exec benchmark-php81 /bin/bash

shell-php82:
	docker-compose exec benchmark-php82 /bin/bash

shell-php83:
	docker-compose exec benchmark-php83 /bin/bash

# Database commands
db-shell:
	docker-compose exec benchmark-db mysql -u benchmark -pbenchmark benchmark_db

redis-cli:
	docker-compose exec benchmark-redis redis-cli

# Full benchmark suite with all PHP versions
benchmark-matrix: up
	@echo "Running benchmark matrix across all PHP versions..."
	@make benchmark-php81
	@make benchmark-php82
	@make benchmark-php83
	docker-compose exec benchmark-php82 php scripts/generate-matrix-report.php

# Development helpers
install-deps:
	docker-compose exec benchmark-php82 composer install -d /benchmarks

test-setup:
	docker-compose exec benchmark-php82 php scripts/test-setup.php