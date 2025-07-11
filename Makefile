.PHONY: help build up down benchmark-all benchmark-pivotphp benchmark-comparative report clean shell-% logs-%

# Default target
help:
	@echo "PivotPHP Benchmarks - Available Commands:"
	@echo ""
	@echo "  make build                - Build all Docker containers"
	@echo "  make up                   - Start all services"
	@echo "  make down                 - Stop all services"
	@echo "  make benchmark-all        - Run all benchmarks"
	@echo "  make benchmark-pivotphp   - Run PivotPHP benchmarks (core + orm + reactphp)"
	@echo "  make benchmark-core       - Run only PivotPHP Core benchmarks"
	@echo "  make benchmark-orm        - Run only PivotPHP ORM benchmarks"
	@echo "  make benchmark-reactphp   - Run only PivotPHP ReactPHP benchmarks"
	@echo "  make benchmark-comparative - Run comparative framework benchmarks"
	@echo "  make benchmark-matrix     - Run benchmarks across all PHP versions"
	@echo "  make benchmark-concurrency - Run in-process concurrency benchmarks"
	@echo "  make benchmark-http-concurrency - Run HTTP concurrency benchmarks"
	@echo ""
	@echo "  Docker Benchmarks:"
	@echo "  make docker-benchmark    - Run Docker-based benchmarks"
	@echo "  make docker-benchmark-pivotphp - Run PivotPHP Docker benchmarks only"
	@echo "  make docker-benchmark-comparative - Run comparative frameworks in Docker"
	@echo "  make docker-benchmark-all - Run all Docker benchmarks (high iterations)"
	@echo ""
	@echo "  Advanced Analysis (Parallel + Sequential):"
	@echo "  make parallel-sequential-analysis - Complete parallel→sequential analysis"
	@echo "  make parallel-sequential-pivotphp - PivotPHP parallel→sequential"
	@echo "  make parallel-sequential-comparative - Comparative frameworks analysis"
	@echo "  make parallel-sequential-all - All frameworks with CPU warmup strategy"
	@echo ""
	@echo "  make report              - Generate benchmark reports"
	@echo "  make clean               - Clean all results and containers"
	@echo ""
	@echo "  Container Access:"
	@echo "  make shell-pivotphp-core - Open shell in PivotPHP Core container"
	@echo "  make shell-pivotphp-orm      - Open shell in PivotPHP ORM container"
	@echo "  make shell-pivotphp-reactphp - Open shell in PivotPHP ReactPHP container"
	@echo "  make shell-slim4             - Open shell in Slim 4 container"
	@echo "  make shell-lumen         - Open shell in Lumen container"
	@echo "  make shell-flight        - Open shell in Flight container"
	@echo "  make shell-symfony       - Open shell in Symfony container"
	@echo "  make db-shell            - Access MySQL shell"
	@echo "  make redis-cli           - Access Redis CLI"
	@echo ""
	@echo "  Logs:"
	@echo "  make logs-pivotphp-core     - View PivotPHP Core logs"
	@echo "  make logs-pivotphp-orm      - View PivotPHP ORM logs"
	@echo "  make logs-pivotphp-reactphp - View PivotPHP ReactPHP logs"
	@echo "  make logs-all               - View all container logs"

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

# Run PivotPHP benchmarks (core, orm, and reactphp)
benchmark-pivotphp: up
	@echo "Running PivotPHP benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --frameworks=pivotphp-core,pivotphp-orm,pivotphp-reactphp

# Run only PivotPHP Core benchmarks
benchmark-core: up
	@echo "Running PivotPHP Core benchmarks..."
	docker-compose exec pivotphp-core php benchmarks/run.php

# Run only PivotPHP ORM benchmarks
benchmark-orm: up
	@echo "Running PivotPHP ORM benchmarks..."
	docker-compose exec pivotphp-orm php benchmarks/run.php

# Run only PivotPHP ReactPHP benchmarks
benchmark-reactphp: up
	@echo "Running PivotPHP ReactPHP benchmarks..."
	docker-compose exec pivotphp-reactphp php benchmarks/run.php

# Run comparative framework benchmarks
benchmark-comparative: up
	@echo "Running comparative framework benchmarks..."
	docker-compose exec orchestrator php /scripts/run-benchmarks.php --frameworks=slim4,lumen,flight,symfony

# Run full comparison report (PivotPHP vs market frameworks)
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
	@for framework in pivotphp-core pivotphp-orm pivotphp-reactphp slim4 lumen flight symfony; do \
		echo "Installing dependencies for $$framework..."; \
		cd frameworks/$$framework && composer install --no-dev --optimize-autoloader; \
		cd ../..; \
	done

# Validate setup
validate:
	@echo "Validating benchmark setup..."
	@docker-compose config
	@echo "Setup is valid!"

# Docker-based benchmark commands
docker-benchmark:
	@echo "Running Docker-based benchmarks..."
	php scripts/docker-benchmark-runner.php --frameworks=pivotphp-core,pivotphp-reactphp,slim4,lumen,flight,symfony

docker-benchmark-pivotphp:
	@echo "Running PivotPHP Docker benchmarks..."
	php scripts/docker-benchmark-runner.php --frameworks=pivotphp-core,pivotphp-reactphp --benchmarks=http-performance,rest-api,async-performance

docker-benchmark-comparative:
	@echo "Running comparative framework benchmarks in Docker..."
	php scripts/docker-benchmark-runner.php --frameworks=slim4,lumen,flight,symfony --benchmarks=http-performance,memory-test

docker-benchmark-all:
	@echo "Running all Docker benchmarks with high iterations..."
	php scripts/docker-benchmark-runner.php --frameworks=pivotphp-core,pivotphp-reactphp,slim4,lumen,flight,symfony --iterations=10000 --warmup=1000

# Parallel + Sequential Analysis (CPU warmup strategy)
parallel-sequential-analysis:
	@echo "Running complete parallel + sequential analysis..."
	@echo "Strategy: Parallel warmup → Sequential precision"
	php scripts/parallel-benchmark-runner.php

parallel-sequential-pivotphp:
	@echo "Running PivotPHP parallel + sequential analysis..."
	php scripts/parallel-benchmark-runner.php --frameworks=pivotphp-core,pivotphp-reactphp,pivotphp-orm

parallel-sequential-comparative:
	@echo "Running comparative frameworks parallel + sequential analysis..."
	php scripts/parallel-benchmark-runner.php --frameworks=slim4,lumen,flight,symfony

parallel-sequential-all:
	@echo "Running complete analysis on all frameworks..."
	php scripts/parallel-benchmark-runner.php --frameworks=pivotphp-core,pivotphp-reactphp,pivotphp-orm,slim4,lumen,flight,symfony --benchmarks=basic-test,memory-test,performance-test,cpu-intensive
