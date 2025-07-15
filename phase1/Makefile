# Makefile para Benchmark Científico PivotPHP Core - Fase 1

.PHONY: help build up down benchmark test validate clean install logs

# Configurações
COMPOSE_FILE = docker/docker-compose.yml
SERVICE_NAME = pivotphp-core
CONTAINER_NAME = phase1-pivotphp-core

# Help
help: ## Mostra ajuda
	@echo "Benchmark Científico PivotPHP Core - Fase 1"
	@echo "========================================="
	@echo ""
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Instalação
install: ## Instala dependências do projeto
	@echo "📦 Instalando dependências..."
	composer install --optimize-autoloader

# Docker
build: ## Constrói container Docker
	@echo "🔨 Construindo container..."
	docker-compose -f $(COMPOSE_FILE) build

up: ## Inicia ambiente Docker
	@echo "🚀 Iniciando ambiente..."
	docker-compose -f $(COMPOSE_FILE) up -d
	@echo "⏳ Aguardando serviços..."
	sleep 5
	@echo "✅ Ambiente disponível em http://localhost:8080"

down: ## Para ambiente Docker
	@echo "🛑 Parando ambiente..."
	docker-compose -f $(COMPOSE_FILE) down

restart: down up ## Reinicia ambiente

# Validação
validate: ## Valida configuração e reprodutibilidade
	@echo "🔍 Validando configuração..."
	docker-compose -f $(COMPOSE_FILE) config
	@echo "🔍 Testando reprodutibilidade..."
	php benchmarks/validate.php

# Testes
test-health: ## Testa health check
	@echo "🏥 Testando health check..."
	curl -s http://localhost:8080/health | jq '.'

test-meta: ## Testa endpoint de metadata
	@echo "📋 Testando metadata..."
	curl -s http://localhost:8080/meta | jq '.'

test-json: ## Testa endpoints JSON
	@echo "📊 Testando JSON pequeno..."
	curl -s http://localhost:8080/json/small | jq '.metadata.size_bytes'
	@echo "📊 Testando JSON médio..."
	curl -s http://localhost:8080/json/medium | jq '.metadata.size_bytes'

test-stress: ## Testa stress endpoints
	@echo "💪 Testando stress básico..."
	curl -s "http://localhost:8080/stress?iterations=1000" | jq '.result'

test-all: test-health test-meta test-json test-stress ## Executa todos os testes básicos

# Benchmark
benchmark: ## Executa benchmark completo
	@echo "🚀 Executando benchmark científico completo..."
	php benchmarks/run.php

benchmark-quick: ## Executa benchmark rápido (menos iterações)
	@echo "⚡ Executando benchmark rápido..."
	BENCHMARK_QUICK=1 php benchmarks/run.php

benchmark-validate: ## Executa apenas validação de reprodutibilidade
	@echo "🔍 Validando reprodutibilidade..."
	php -r "require 'vendor/autoload.php'; use PivotPHP\Benchmark\Phase1\DataGenerator; \$$config = json_decode(file_get_contents('config/benchmark.json'), true)['phase1_config']; \$$seeds = json_decode(file_get_contents('config/seeds.json'), true)['deterministic_seeds']; \$$gen = new DataGenerator(\$$config, \$$seeds); foreach (['small', 'medium', 'large'] as \$$scenario) { echo \$$scenario . ': ' . (\$$gen->validateReproducibility(\$$scenario) ? 'OK' : 'FAIL') . \"\n\"; }"

# Logs e debugging
logs: ## Mostra logs do container
	docker-compose -f $(COMPOSE_FILE) logs -f $(SERVICE_NAME)

logs-monitor: ## Mostra logs do monitor
	docker-compose -f $(COMPOSE_FILE) logs -f monitor

shell: ## Acessa shell do container
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) /bin/bash

# Análise
analyze: ## Analisa último resultado
	@echo "📈 Analisando últimos resultados..."
	@find results -name "*.json" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2- | xargs cat | jq '.summary'

report: ## Gera relatório dos resultados
	@echo "📋 Últimos relatórios:"
	@ls -la results/*.md | tail -5

# Limpeza
clean: ## Remove containers e resultados
	@echo "🧹 Limpando ambiente..."
	docker-compose -f $(COMPOSE_FILE) down -v
	docker-compose -f $(COMPOSE_FILE) rm -f
	
clean-results: ## Remove apenas resultados
	@echo "🧹 Limpando resultados..."
	rm -f results/*

clean-all: clean clean-results ## Limpeza completa

# Métricas rápidas
quick-metrics: ## Métricas rápidas de performance
	@echo "⚡ Métricas rápidas:"
	@echo -n "Health (10 requests): "
	@time (for i in {1..10}; do curl -s http://localhost:8080/health > /dev/null; done)
	@echo -n "JSON small (10 requests): "
	@time (for i in {1..10}; do curl -s http://localhost:8080/json/small > /dev/null; done)

# Status
status: ## Mostra status do ambiente
	@echo "📊 Status do ambiente:"
	@docker-compose -f $(COMPOSE_FILE) ps
	@echo ""
	@echo "🌐 Endpoints disponíveis:"
	@echo "  Health:     http://localhost:8080/health"
	@echo "  Metadata:   http://localhost:8080/meta"
	@echo "  JSON Small: http://localhost:8080/json/small"
	@echo "  JSON Med:   http://localhost:8080/json/medium"
	@echo "  Stress:     http://localhost:8080/stress"

# Desenvolvimento
dev-setup: install build up test-all ## Setup completo para desenvolvimento
	@echo "✅ Ambiente de desenvolvimento pronto!"

# Produção
prod-benchmark: build up benchmark down ## Executa benchmark completo em produção
	@echo "✅ Benchmark de produção concluído!"

# Default target
.DEFAULT_GOAL := help