# PivotPHP Ecosystem Validation Makefile
# Phase 3 - Comprehensive Core + ReactPHP + Cycle ORM Testing
# Production-ready performance validation

.PHONY: help build up down clean validate benchmark stress-test report logs health setup

# Default target
help:
	@echo "🚀 PivotPHP Ecosystem Validation - Phase 3"
	@echo "=========================================="
	@echo ""
	@echo "Available commands:"
	@echo "  make setup          - Initial setup and environment validation"
	@echo "  make build          - Build all Docker containers"
	@echo "  make up             - Start the ecosystem services"
	@echo "  make down           - Stop and remove containers"
	@echo "  make clean          - Clean up everything (containers, volumes, networks)"
	@echo "  make validate       - Run complete ecosystem validation"
	@echo "  make benchmark      - Run comprehensive benchmarks only"
	@echo "  make stress-test    - Run K6 stress testing only"
	@echo "  make health         - Check ecosystem health status"
	@echo "  make logs           - View logs from all services"
	@echo "  make report         - Generate performance report"
	@echo "  make monitor        - Start monitoring (Grafana/Prometheus)"
	@echo "  make test-quick     - Quick validation test"
	@echo "  make test-full      - Full validation with all components"
	@echo ""
	@echo "🎯 Quick Start:"
	@echo "  make setup && make validate"
	@echo ""

# Setup and environment validation
setup:
	@echo "🔧 Setting up PivotPHP Ecosystem Validation..."
	@echo "📋 Validating environment requirements..."
	@php -r "if(version_compare(PHP_VERSION, '8.1', '<')) { echo 'PHP 8.1+ required. Current: ' . PHP_VERSION . \"\n\"; exit(1); }"
	@docker --version > /dev/null 2>&1 || (echo "❌ Docker not found. Please install Docker." && exit 1)
	@docker-compose --version > /dev/null 2>&1 || (echo "❌ Docker Compose not found. Please install Docker Compose." && exit 1)
	@echo "✅ Environment validation passed!"
	@echo "📁 Creating required directories..."
	@mkdir -p results logs monitoring/grafana monitoring/prometheus
	@echo "✅ Setup completed successfully!"

# Build all Docker containers
build:
	@echo "🐳 Building PivotPHP ecosystem containers..."
	@docker-compose -f docker-compose-ecosystem.yml build --no-cache
	@echo "✅ All containers built successfully!"

# Start the ecosystem services
up:
	@echo "🚀 Starting PivotPHP ecosystem services..."
	@docker-compose -f docker-compose-ecosystem.yml up -d
	@echo "⏳ Waiting for services to be ready..."
	@sleep 30
	@echo "✅ Ecosystem services started!"
	@echo "📋 Service status:"
	@docker-compose -f docker-compose-ecosystem.yml ps

# Stop and remove containers
down:
	@echo "🛑 Stopping PivotPHP ecosystem services..."
	@docker-compose -f docker-compose-ecosystem.yml down
	@echo "✅ Services stopped!"

# Clean up everything
clean:
	@echo "🧹 Cleaning up PivotPHP ecosystem..."
	@docker-compose -f docker-compose-ecosystem.yml down -v --remove-orphans
	@docker system prune -f --volumes
	@echo "✅ Cleanup completed!"

# Run complete ecosystem validation
validate:
	@echo "🔬 Running complete ecosystem validation..."
	@php run-ecosystem-validation.php
	@echo "✅ Ecosystem validation completed!"

# Run comprehensive benchmarks
benchmark:
	@echo "📊 Running comprehensive benchmarks..."
	@php ecosystem-comprehensive-benchmark.php
	@echo "✅ Benchmarks completed!"

# Run K6 stress testing
stress-test:
	@echo "🔥 Running K6 stress testing..."
	@docker run --rm -i --network ecosystem-validation_ecosystem-network \
		-v $(PWD)/k6-scripts:/scripts \
		-v $(PWD)/results:/results \
		grafana/k6:latest run /scripts/ecosystem-stress-test.js
	@echo "✅ Stress testing completed!"

# Check ecosystem health
health:
	@echo "🏥 Checking ecosystem health..."
	@echo "📊 Core Framework:"
	@curl -s http://localhost:8080/health | jq '.' || echo "❌ Core framework not responding"
	@echo "⚡ ReactPHP Extension:"
	@curl -s http://localhost:8081/health | jq '.' || echo "❌ ReactPHP not responding"
	@echo "💾 Database:"
	@docker-compose -f docker-compose-ecosystem.yml exec -T mysql-ecosystem mysqladmin ping -h localhost || echo "❌ Database not responding"
	@echo "🗄️ Redis:"
	@docker-compose -f docker-compose-ecosystem.yml exec -T redis-ecosystem redis-cli ping || echo "❌ Redis not responding"

# View logs from all services
logs:
	@echo "📋 Viewing ecosystem logs..."
	@docker-compose -f docker-compose-ecosystem.yml logs --tail=50 -f

# Generate performance report
report:
	@echo "📄 Generating performance report..."
	@php -r "echo 'Performance Report Generated: ' . date('Y-m-d H:i:s') . \"\n\";"
	@ls -la results/
	@echo "✅ Report generation completed!"

# Start monitoring services
monitor:
	@echo "📊 Starting monitoring services..."
	@docker-compose -f docker-compose-ecosystem.yml up -d prometheus grafana
	@echo "✅ Monitoring started!"
	@echo "📊 Grafana: http://localhost:3000 (admin/benchmark)"
	@echo "📈 Prometheus: http://localhost:9090"

# Quick validation test
test-quick:
	@echo "⚡ Running quick validation test..."
	@make setup
	@make build
	@make up
	@sleep 10
	@make health
	@make benchmark
	@make down
	@echo "✅ Quick test completed!"

# Full validation with all components
test-full:
	@echo "🎯 Running full ecosystem validation..."
	@make setup
	@make build
	@make up
	@make validate
	@make stress-test
	@make report
	@echo "✅ Full validation completed!"

# Development helpers
dev-setup:
	@echo "🔧 Setting up development environment..."
	@make setup
	@make build
	@make up
	@make monitor
	@echo "✅ Development environment ready!"

# Performance targets check
check-targets:
	@echo "🎯 Checking performance targets..."
	@echo "📊 Expected Performance Targets:"
	@echo "  • Core Only: 6,000 req/sec"
	@echo "  • Core + ReactPHP: 15,000 req/sec"
	@echo "  • Core + ORM: 4,500 req/sec"
	@echo "  • Full Ecosystem: 12,000 req/sec"
	@echo ""
	@echo "🚀 ReactPHP Target: 19,707 req/sec (Market Leader)"
	@echo "💾 Cycle ORM Target: 457,870 ops/sec"
	@echo ""

# Debugging helpers
debug-core:
	@echo "🔍 Debugging core framework..."
	@docker-compose -f docker-compose-ecosystem.yml logs pivotphp-core-only

debug-reactphp:
	@echo "🔍 Debugging ReactPHP extension..."
	@docker-compose -f docker-compose-ecosystem.yml logs pivotphp-core-reactphp

debug-orm:
	@echo "🔍 Debugging ORM extension..."
	@docker-compose -f docker-compose-ecosystem.yml logs pivotphp-core-orm

debug-full:
	@echo "🔍 Debugging full ecosystem..."
	@docker-compose -f docker-compose-ecosystem.yml logs pivotphp-full-ecosystem

# Database operations
db-init:
	@echo "💾 Initializing database..."
	@docker-compose -f docker-compose-ecosystem.yml exec mysql-ecosystem mysql -u root -pbenchmark_password ecosystem_test < sql/init.sql
	@echo "✅ Database initialized!"

db-reset:
	@echo "🔄 Resetting database..."
	@docker-compose -f docker-compose-ecosystem.yml exec mysql-ecosystem mysql -u root -pbenchmark_password -e "DROP DATABASE IF EXISTS ecosystem_test; CREATE DATABASE ecosystem_test;"
	@make db-init
	@echo "✅ Database reset completed!"

# Resource monitoring
monitor-resources:
	@echo "📊 Monitoring resource usage..."
	@docker stats --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"

# Network diagnostics
network-test:
	@echo "🌐 Testing network connectivity..."
	@docker-compose -f docker-compose-ecosystem.yml exec pivotphp-full-ecosystem ping -c 3 mysql-ecosystem
	@docker-compose -f docker-compose-ecosystem.yml exec pivotphp-full-ecosystem ping -c 3 redis-ecosystem

# Backup results
backup-results:
	@echo "💾 Backing up results..."
	@tar -czf results-backup-$(shell date +%Y%m%d-%H%M%S).tar.gz results/
	@echo "✅ Results backed up!"

# Clean results only
clean-results:
	@echo "🧹 Cleaning results directory..."
	@rm -rf results/*
	@echo "✅ Results cleaned!"

# Show current status
status:
	@echo "📊 PivotPHP Ecosystem Status"
	@echo "============================"
	@echo "🐳 Docker Containers:"
	@docker-compose -f docker-compose-ecosystem.yml ps
	@echo ""
	@echo "📁 Results Directory:"
	@ls -la results/ 2>/dev/null || echo "No results yet"
	@echo ""
	@echo "💾 Disk Usage:"
	@df -h .
	@echo ""
	@echo "🧠 Memory Usage:"
	@free -h

# Version info
version:
	@echo "📋 PivotPHP Ecosystem Validation"
	@echo "================================"
	@echo "Version: Phase 3 - Comprehensive Integration"
	@echo "Framework: PivotPHP v1.1.4"
	@echo "Components: Core + ReactPHP + Cycle ORM"
	@echo "PHP Version: $(shell php -r 'echo PHP_VERSION;')"
	@echo "Docker Version: $(shell docker --version)"
	@echo "Docker Compose Version: $(shell docker-compose --version)"
	@echo "Date: $(shell date)"
	@echo ""
	@echo "🎯 Performance Targets:"
	@echo "  • ReactPHP: 19,707 req/sec (Market Leader)"
	@echo "  • Core Framework: 6,227 req/sec"
	@echo "  • Cycle ORM: 457,870 ops/sec"
	@echo "  • Full Ecosystem: 12,000 req/sec"

# Install dependencies
install-deps:
	@echo "📦 Installing dependencies..."
	@composer install --optimize-autoloader
	@echo "✅ Dependencies installed!"