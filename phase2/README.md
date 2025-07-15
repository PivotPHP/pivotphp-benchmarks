# FASE 2: Benchmark Científico PivotPHP com Extensões

**Objetivo**: Avaliar performance das extensões PivotPHP (Cycle ORM e ReactPHP) comparando com o baseline da Fase 1.

## 🎯 Estrutura da Fase 2

### Diretórios
```
phase2/
├── README.md                   # Este arquivo
├── docker/                    # Ambiente determinístico para extensões
│   ├── Dockerfile-orm         # Container PivotPHP + Cycle ORM
│   ├── Dockerfile-reactphp    # Container PivotPHP + ReactPHP
│   ├── docker-compose.yml     # Orquestração com banco de dados
│   └── php.ini               # Configuração padronizada
├── src/                       # Aplicações de teste
│   ├── orm/                  # Aplicação com Cycle ORM
│   │   ├── Application.php   # Bootstrap PivotPHP + ORM
│   │   ├── Models/           # Entidades de teste
│   │   └── Repositories/     # Repositórios de benchmark
│   ├── reactphp/             # Aplicação com ReactPHP
│   │   ├── Application.php   # Bootstrap PivotPHP + ReactPHP
│   │   ├── Server.php        # Servidor ReactPHP
│   │   └── Handlers/         # Handlers assíncronos
│   └── shared/               # Componentes compartilhados
│       ├── ExtensionBenchmark.php  # Framework de benchmark estendido
│       └── PerformanceComparator.php # Comparador de extensões
├── benchmarks/                # Scripts de benchmark
│   ├── orm-benchmarks.php    # Benchmarks específicos do ORM
│   ├── reactphp-benchmarks.php # Benchmarks específicos do ReactPHP
│   ├── comparative-runner.php   # Comparação entre extensões
│   └── run-phase2.php        # Script principal da Fase 2
├── config/                    # Configurações
│   ├── benchmark-phase2.json # Parâmetros da Fase 2
│   ├── database.php          # Configuração do banco
│   └── seeds-phase2.json     # Seeds determinísticos
└── results/                   # Resultados
    ├── raw/                  # Dados brutos
    ├── processed/            # Análises
    └── reports/              # Relatórios finais
```

## 📊 Métricas da Fase 2

### 1. Benchmarks Cycle ORM
- **Operações CRUD**: Create, Read, Update, Delete
- **Queries complexas**: Joins, agregações, subconsultas
- **Hydration performance**: Tempo de conversão entity
- **Lazy/Eager loading**: Estratégias de carregamento
- **Transações**: Performance transacional
- **Connection pooling**: Gerenciamento de conexões

### 2. Benchmarks ReactPHP
- **Event loop**: Performance do loop de eventos
- **Concorrência**: Requests simultâneas
- **WebSocket**: Conexões persistentes
- **Promises**: Operações assíncronas
- **Memory management**: Gerenciamento de memória contínua
- **Throughput sustentado**: Performance de longa duração

### 3. Comparação com Baseline
- **Overhead das extensões**: Custo adicional vs Core
- **Scaling characteristics**: Como cada extensão escala
- **Memory footprint**: Uso de memória adicional
- **Latency impact**: Impacto na latência base
- **Resource utilization**: CPU, memória, I/O

## 🔬 Metodologia Científica Fase 2

### Ambiente Controlado
- **Docker containers**: Isolamento total por extensão
- **Banco MySQL**: Para testes ORM realísticos
- **Redis**: Cache para otimizações
- **Recursos fixos**: 2 CPUs, 2GB RAM por container

### Medições Comparativas
- **Baseline reference**: Resultados da Fase 1 como referência
- **Extension overhead**: Medição do custo adicional
- **Performance regression**: Detecção de degradação
- **Scaling analysis**: Como performance varia com carga

### Validação Científica
- **Reprodutibilidade**: Seeds determinísticos mantidos
- **Statistical significance**: Múltiplas execuções
- **Confidence intervals**: Intervalos de confiança
- **Hypothesis testing**: Testes de significância

## 🚀 Status da Fase 2

- [x] **Ambiente Docker** - Containers para ORM e ReactPHP configurados
- [x] **Aplicação ORM** - PivotPHP + Cycle ORM implementado
- [x] **Aplicação ReactPHP** - PivotPHP + ReactPHP implementado
- [x] **Framework comparativo** - Análise vs baseline da Fase 1
- [x] **Scripts de execução** - Runner automatizado completo
- [x] **Makefile** - Comandos de desenvolvimento prontos
- [ ] **Banco de dados** - Schema e dados de teste (requer setup)
- [ ] **Execução Docker** - Build e teste dos containers
- [ ] **Benchmarks reais** - Execução com dados válidos
- [ ] **Análise estatística** - Comparações significativas
- [ ] **Relatórios finais** - Resultados científicos

## 🎯 Como Usar

### Setup Completo
```bash
# 1. Build dos containers
docker-compose -f docker/docker-compose.yml build

# 2. Iniciar ambiente completo
docker-compose -f docker/docker-compose.yml up -d

# 3. Setup do banco de dados
php benchmarks/setup-database.php

# 4. Executar benchmarks da Fase 2
php benchmarks/run-phase2.php
```

### Comandos Principais
```bash
# Benchmarks específicos
php benchmarks/orm-benchmarks.php        # Apenas ORM
php benchmarks/reactphp-benchmarks.php   # Apenas ReactPHP

# Comparações
php benchmarks/comparative-runner.php    # Comparar extensões
php benchmarks/baseline-comparison.php   # Comparar com Fase 1

# Análises
php benchmarks/performance-analysis.php  # Análise detalhada
php benchmarks/overhead-analysis.php     # Análise de overhead
```

### Validação Rápida
```bash
# Testar endpoints ORM
curl http://localhost:8081/orm/users
curl http://localhost:8081/orm/complex-query

# Testar endpoints ReactPHP  
curl http://localhost:8082/async/users
curl http://localhost:8082/websocket/test
```

## 📈 Expectativas de Resultados

### Cycle ORM
- **Overhead esperado**: 10-30% comparado ao Core
- **Queries simples**: 200-400 req/s
- **Queries complexas**: 50-150 req/s
- **Hydration**: 1000-5000 entities/s

### ReactPHP
- **Concorrência**: 1000+ requests simultâneas
- **Throughput**: 500-2000 req/s sustentado
- **Memory**: Uso eficiente em long-running
- **Latency**: <10ms para operações simples

### Objetivos da Fase 2
1. **Quantificar overhead** das extensões
2. **Identificar sweet spots** de performance
3. **Validar escalabilidade** de cada extensão
4. **Fornecer baseline** para Fase 3 (comparações externas)

## 🔗 Integração com Fase 1

- **Baseline reference**: Usa resultados da Fase 1 como linha de base
- **Metodologia consistente**: Mesma abordagem científica
- **Environment parity**: Configuração Docker similar
- **Seed consistency**: Seeds determinísticos compatíveis
- **Metrics alignment**: Mesmas métricas base + extensões específicas