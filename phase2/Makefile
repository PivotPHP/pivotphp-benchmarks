# Makefile para Benchmark Científico PivotPHP Extensions - Fase 2

.PHONY: help build up down benchmark test validate clean install logs

# Configurações
COMPOSE_FILE = docker/docker-compose.yml
ORM_SERVICE = pivotphp-orm
REACTPHP_SERVICE = pivotphp-reactphp

# Help
help: ## Mostra ajuda
	@echo "Benchmark Científico PivotPHP Extensions - Fase 2"
	@echo "================================================"
	@echo ""
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\\033[36m%-20s\\033[0m %s\\n", $$1, $$2}'

# Instalação
install: ## Instala dependências do projeto
	@echo "📦 Instalando dependências..."
	composer install --optimize-autoloader

# Docker
build: ## Constrói containers Docker
	@echo "🔨 Construindo containers..."
	docker-compose -f $(COMPOSE_FILE) build

up: ## Inicia ambiente Docker completo
	@echo "🚀 Iniciando ambiente completo..."
	docker-compose -f $(COMPOSE_FILE) up -d
	@echo "⏳ Aguardando serviços..."
	sleep 30
	@echo "✅ Ambiente disponível:"
	@echo "  ORM:     http://localhost:8081"
	@echo "  ReactPHP: http://localhost:8082"

down: ## Para ambiente Docker
	@echo "🛑 Parando ambiente..."
	docker-compose -f $(COMPOSE_FILE) down

restart: down up ## Reinicia ambiente

# Serviços individuais
up-orm: ## Inicia apenas serviço ORM
	@echo "🔨 Iniciando apenas ORM..."
	docker-compose -f $(COMPOSE_FILE) up -d mysql redis $(ORM_SERVICE)

up-reactphp: ## Inicia apenas serviço ReactPHP
	@echo "⚡ Iniciando apenas ReactPHP..."
	docker-compose -f $(COMPOSE_FILE) up -d mysql redis $(REACTPHP_SERVICE)

# Validação
validate: ## Valida configuração
	@echo "🔍 Validando configuração..."
	docker-compose -f $(COMPOSE_FILE) config

# Testes
test-health: ## Testa health checks
	@echo "🏥 Testando health checks..."
	@echo "ORM Health:"
	@curl -s http://localhost:8081/health | jq '.status' || echo "❌ ORM não disponível"
	@echo "ReactPHP Health:"
	@curl -s http://localhost:8082/health | jq '.status' || echo "❌ ReactPHP não disponível"

test-orm: ## Testa endpoints ORM
	@echo "📊 Testando endpoints ORM..."
	@curl -s http://localhost:8081/orm/users?limit=5 | jq '.users | length' || echo "❌ ORM users endpoint falhou"
	@curl -s http://localhost:8081/orm/stats | jq '.stats' || echo "❌ ORM stats endpoint falhou"

test-reactphp: ## Testa endpoints ReactPHP
	@echo "⚡ Testando endpoints ReactPHP..."
	@curl -s http://localhost:8082/async/timer | jq '.test' || echo "❌ ReactPHP async endpoint falhou"
	@curl -s http://localhost:8082/event-loop/timers | jq '.test' || echo "❌ ReactPHP event-loop endpoint falhou"

test-all: test-health test-orm test-reactphp ## Executa todos os testes básicos

# Benchmark
benchmark: ## Executa benchmark completo da Fase 2
	@echo "🚀 Executando benchmark científico da Fase 2..."
	php benchmarks/run-phase2.php

benchmark-orm: ## Executa apenas benchmarks ORM
	@echo "📊 Executando benchmarks ORM..."
	@echo "⚠️ Implementar script específico ORM"

benchmark-reactphp: ## Executa apenas benchmarks ReactPHP
	@echo "⚡ Executando benchmarks ReactPHP..."
	@echo "⚠️ Implementar script específico ReactPHP"

benchmark-quick: ## Executa benchmark rápido
	@echo "⚡ Executando benchmark rápido..."
	BENCHMARK_QUICK=1 php benchmarks/run-phase2.php

# Logs e debugging
logs: ## Mostra logs de todos os containers
	docker-compose -f $(COMPOSE_FILE) logs -f

logs-orm: ## Mostra logs do container ORM
	docker-compose -f $(COMPOSE_FILE) logs -f $(ORM_SERVICE)

logs-reactphp: ## Mostra logs do container ReactPHP
	docker-compose -f $(COMPOSE_FILE) logs -f $(REACTPHP_SERVICE)

logs-mysql: ## Mostra logs do MySQL
	docker-compose -f $(COMPOSE_FILE) logs -f mysql

logs-redis: ## Mostra logs do Redis
	docker-compose -f $(COMPOSE_FILE) logs -f redis

shell-orm: ## Acessa shell do container ORM
	docker-compose -f $(COMPOSE_FILE) exec $(ORM_SERVICE) /bin/bash

shell-reactphp: ## Acessa shell do container ReactPHP
	docker-compose -f $(COMPOSE_FILE) exec $(REACTPHP_SERVICE) /bin/bash

# Database
db-shell: ## Acessa shell do MySQL
	docker-compose -f $(COMPOSE_FILE) exec mysql mysql -u benchmark -pbenchmark benchmark_phase2

redis-cli: ## Acessa CLI do Redis
	docker-compose -f $(COMPOSE_FILE) exec redis redis-cli

setup-db: ## Configura banco de dados inicial
	@echo "🗄️ Configurando banco de dados..."
	@echo "⚠️ Implementar script de setup"

# Análise
analyze: ## Analisa último resultado
	@echo "📈 Analisando últimos resultados..."
	@find results -name "*.json" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2- | xargs cat | jq '.summary'

report: ## Mostra últimos relatórios
	@echo "📋 Últimos relatórios:"
	@ls -la results/*.md | tail -5

compare-baseline: ## Compara com baseline da Fase 1
	@echo "📊 Comparando com baseline da Fase 1..."
	@echo "⚠️ Implementar comparação automática"

# Limpeza
clean: ## Remove containers e resultados
	@echo "🧹 Limpando ambiente..."
	docker-compose -f $(COMPOSE_FILE) down -v
	docker-compose -f $(COMPOSE_FILE) rm -f

clean-results: ## Remove apenas resultados
	@echo "🧹 Limpando resultados..."
	rm -f results/*

clean-all: clean clean-results ## Limpeza completa

# Status
status: ## Mostra status do ambiente
	@echo "📊 Status do ambiente:"
	@docker-compose -f $(COMPOSE_FILE) ps
	@echo ""
	@echo "🌐 Endpoints disponíveis:"
	@echo "  ORM Health:      http://localhost:8081/health"
	@echo "  ORM Users:       http://localhost:8081/orm/users"
	@echo "  ORM Complex:     http://localhost:8081/orm/complex-join"
	@echo "  ReactPHP Health: http://localhost:8082/health"
	@echo "  ReactPHP Async:  http://localhost:8082/async/timer"
	@echo "  ReactPHP Events: http://localhost:8082/event-loop/timers"
	@echo ""
	@echo "🗄️ Database:"
	@echo "  MySQL:  localhost:3307"
	@echo "  Redis:  localhost:6380"

# Desenvolvimento
dev-setup: install build up test-all ## Setup completo para desenvolvimento
	@echo "✅ Ambiente de desenvolvimento da Fase 2 pronto!"

# Produção
prod-benchmark: build up benchmark down ## Executa benchmark completo em produção
	@echo "✅ Benchmark de produção da Fase 2 concluído!"

# Comparação
baseline-comparison: ## Executa comparação com baseline da Fase 1
	@echo "📊 Executando comparação com baseline..."
	@if [ -f "../phase1/results/phase1-simple-benchmark-2025-07-11-18-35-33.json" ]; then \
		echo "✅ Baseline encontrado"; \
		php benchmarks/run-phase2.php; \
	else \
		echo "❌ Execute primeiro a Fase 1 para gerar baseline"; \
	fi

# Default target
.DEFAULT_GOAL := help